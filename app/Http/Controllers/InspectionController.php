<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionSchedule;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(): View
    {
        return view('inspections.index', [
            'inspections'=>Inspection::latest('due_on')->paginate(20),
            'schedules'=>InspectionSchedule::where('is_active',true)->orderBy('next_due_on')->limit(15)->get(),
        ]);
    }

    public function complete(Request $request, Inspection $inspection, AuditService $audit): RedirectResponse
    {
        $data=$request->validate(['remarks'=>['nullable','string','max:5000']]);
        $old=$inspection->toArray();
        $inspection->update(['status'=>'completed','performed_at'=>now(),'performed_by'=>$request->user()->id,'remarks'=>$data['remarks']??null]);
        $audit->record('INSPECTION_COMPLETED',$inspection,$old,$inspection->fresh()->toArray());
        return back()->with('success','Inspection marked complete.');
    }
}
