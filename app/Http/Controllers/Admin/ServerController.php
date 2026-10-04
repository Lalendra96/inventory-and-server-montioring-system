<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServerNode;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $servers = ServerNode::query()->orderBy('is_active', 'desc')->orderBy('name')->get();
        return view('admin.servers.index', compact('servers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['allowed_services'] = $this->lines($request->string('allowed_services_text')->toString());
        $data['protected_process_patterns'] = $this->lines($request->string('protected_process_patterns_text')->toString());
        $data['allow_process_control'] = $request->boolean('allow_process_control');
        $data['allow_service_control'] = $request->boolean('allow_service_control');
        $data['is_active'] = true;

        $server = ServerNode::create($data);
        $this->audit->record('server.created', $server, [], $server->only([
            'code', 'name', 'host', 'ssh_port', 'ssh_user', 'environment', 'system_group', 'allow_process_control', 'allow_service_control',
        ]));

        return back()->with('success', 'Server monitoring profile created. Add and verify its SSH host key before testing connectivity.');
    }

    public function update(Request $request, ServerNode $server): RedirectResponse
    {
        $old = $server->only([
            'code', 'name', 'host', 'ssh_port', 'ssh_user', 'ssh_key_path', 'environment', 'system_group', 'os_hint',
            'allowed_services', 'protected_process_patterns', 'allow_process_control', 'allow_service_control',
            'poll_interval_seconds', 'notes', 'is_active',
        ]);

        $data = $this->validated($request, $server);
        $data['allowed_services'] = $this->lines($request->string('allowed_services_text')->toString());
        $data['protected_process_patterns'] = $this->lines($request->string('protected_process_patterns_text')->toString());
        $data['allow_process_control'] = $request->boolean('allow_process_control');
        $data['allow_service_control'] = $request->boolean('allow_service_control');

        $server->update($data);
        $this->audit->record('server.updated', $server, $old, $server->fresh()->only(array_keys($old)));

        return back()->with('success', 'Server monitoring profile updated.');
    }

    public function disable(Request $request, ServerNode $server): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $old = $server->only(['is_active', 'disabled_at', 'disabled_by', 'disable_reason']);
        $server->update([
            'is_active' => false,
            'disabled_at' => now(),
            'disabled_by' => auth()->id(),
            'disable_reason' => $data['reason'],
        ]);
        $this->audit->record('server.disabled', $server, $old, $server->only(array_keys($old)));
        return back()->with('success', 'Server monitoring profile disabled. Historical data was retained.');
    }

    public function enable(ServerNode $server): RedirectResponse
    {
        $old = $server->only(['is_active', 'disabled_at', 'disabled_by', 'disable_reason']);
        $server->update(['is_active' => true, 'disabled_at' => null, 'disabled_by' => null, 'disable_reason' => null]);
        $this->audit->record('server.enabled', $server, $old, $server->only(array_keys($old)));
        return back()->with('success', 'Server monitoring profile enabled.');
    }

    private function validated(Request $request, ?ServerNode $server = null): array
    {
        $id = $server?->id;
        return $request->validate([
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:server_nodes,code,'.$id],
            'name' => ['required', 'string', 'max:160'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'ssh_port' => ['required', 'integer', 'between:1,65535'],
            'ssh_user' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z_][A-Za-z0-9_-]*$/'],
            'ssh_key_path' => ['required', 'string', 'max:500'],
            'environment' => ['required', 'string', 'max:40'],
            'system_group' => ['required', 'in:hims,lims,ris_pacs,infrastructure,other'],
            'os_hint' => ['nullable', 'string', 'max:120'],
            'poll_interval_seconds' => ['required', 'integer', 'between:5,60'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r?\n/', $value))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
