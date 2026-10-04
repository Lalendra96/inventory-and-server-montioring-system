<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->paginate(25),
            'roles' => Role::where('is_active', true)->orderBy('label')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required','alpha_dash','max:80','unique:users,username'],
            'staff_no' => ['nullable','string','max:80','unique:users,staff_no'],
            'name' => ['required','string','max:160'],
            'email' => ['required','email','max:190','unique:users,email'],
            'unit_code' => ['nullable','string','max:80'],
            'password' => ['required','string','min:10','max:255'],
            'roles' => ['required','array','min:1'],
            'roles.*' => ['integer','exists:roles,id'],
        ]);

        $user = User::create([
            ...collect($data)->except(['roles','password'])->all(),
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $user->roles()->sync($data['roles']);
        $audit->record('USER_CREATED', $user, [], ['username'=>$user->username,'roles'=>$data['roles']]);

        return back()->with('success','User account created.');
    }

    public function updateRoles(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['roles'=>['required','array','min:1'],'roles.*'=>['integer','exists:roles,id']]);
        $old = $user->roles()->pluck('roles.id')->all();
        $user->roles()->sync($data['roles']);
        $audit->record('USER_ROLES_CHANGED',$user,['roles'=>$old],['roles'=>$data['roles']]);
        return back()->with('success','User roles updated.');
    }

    public function disable(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot disable your own signed-in account.');
        $data=$request->validate(['reason'=>['required','string','max:1000']]);
        $user->update(['is_active'=>false,'disabled_at'=>now(),'disabled_by'=>$request->user()->id,'disable_reason'=>$data['reason']]);
        $audit->record('USER_DISABLED',$user,[],['reason'=>$data['reason']]);
        return back()->with('success','User disabled. Historical records were retained.');
    }

    public function enable(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        $user->update(['is_active'=>true,'disabled_at'=>null,'disabled_by'=>null,'disable_reason'=>null]);
        $audit->record('USER_REENABLED',$user);
        return back()->with('success','User re-enabled.');
    }

    public function resetPassword(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['password'=>['required','string','min:10','max:255']]);
        $user->update(['password'=>Hash::make($data['password']),'must_change_password'=>true]);
        $audit->record('USER_PASSWORD_RESET_BY_ADMIN',$user);
        return back()->with('success','Temporary password set. User must change it at next login.');
    }
}
