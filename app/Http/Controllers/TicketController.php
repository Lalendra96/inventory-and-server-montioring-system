<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::query()
            ->with(['category', 'location', 'assignee'])
            ->where('is_active', true);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->string('q'));

            // Exact matching only: wildcard searches are intentionally avoided.
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('ticket_no', $term)
                    ->orWhere('subject', $term);
            });
        }

        return view('tickets.index', [
            'tickets' => $query
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => TicketCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:ticket_categories,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => [
                'required',
                Rule::in(['low', 'normal', 'high', 'critical']),
            ],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,txt,csv,log,doc,docx,xls,xlsx',
            ],
        ]);

        $ticket = DB::transaction(function () use ($request, $data): Ticket {
            $sla = SlaPolicy::query()
                ->where('priority', $data['priority'])
                ->where('is_active', true)
                ->first();

            $nextSequence = (Ticket::max('id') ?? 0) + 1;

            $ticket = Ticket::create([
                ...collect($data)->except('attachments')->all(),
                'ticket_no' => 'ICT-'.now()->format('Ymd').'-'.str_pad(
                    (string) $nextSequence,
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
                'created_by' => $request->user()->id,
                'response_due_at' => $sla
                    ? now()->addMinutes($sla->response_minutes)
                    : null,
                'resolution_due_at' => $sla
                    ? now()->addMinutes($sla->resolution_minutes)
                    : null,
            ]);

            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'actor_id' => $request->user()->id,
                'event' => 'created',
                'metadata' => ['priority' => $ticket->priority],
                'occurred_at' => now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("tickets/{$ticket->id}", 'local');

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'uploaded_by' => $request->user()->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                ]);
            }

            return $ticket;
        });

        $audit->record(
            'TICKET_CREATED',
            $ticket,
            [],
            $ticket->only(['ticket_no', 'subject', 'priority', 'status'])
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Support request created.');
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->is_active, 404);

        $ticket->load([
            'category',
            'location',
            'creator',
            'assignee',
            'comments' => fn ($query) => $query
                ->where('is_active', true)
                ->latest(),
            'attachments',
            'events' => fn ($query) => $query->latest('occurred_at'),
        ]);

        return view('tickets.show', [
            'ticket' => $ticket,
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function assign(
        Request $request,
        Ticket $ticket,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $oldValues = $ticket->only(['assigned_to', 'status']);

        $ticket->update([
            'assigned_to' => $data['assigned_to'],
            'status' => $ticket->status === 'open'
                ? 'assigned'
                : $ticket->status,
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id,
            'event' => 'assigned',
            'metadata' => ['assigned_to' => $data['assigned_to']],
            'occurred_at' => now(),
        ]);

        $audit->record(
            'TICKET_ASSIGNED',
            $ticket,
            $oldValues,
            $ticket->only(['assigned_to', 'status'])
        );

        return back()->with('success', 'Ticket assignment updated.');
    }

    public function status(
        Request $request,
        Ticket $ticket,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'open',
                    'assigned',
                    'acknowledged',
                    'in_progress',
                    'escalated',
                    'resolved',
                    'verified',
                ]),
            ],
        ]);

        $oldValues = $ticket->only([
            'status',
            'acknowledged_at',
            'started_at',
            'resolved_at',
            'verified_at',
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'acknowledged' && ! $ticket->acknowledged_at) {
            $updates['acknowledged_at'] = now();
        }

        if ($data['status'] === 'in_progress' && ! $ticket->started_at) {
            $updates['started_at'] = now();
        }

        if ($data['status'] === 'escalated') {
            $updates['escalated_at'] = now();
        }

        if ($data['status'] === 'resolved') {
            $updates['resolved_at'] = now();
        }

        if ($data['status'] === 'verified') {
            $updates['verified_at'] = now();
            $updates['verified_by'] = $request->user()->id;
        }

        $ticket->update($updates);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id,
            'event' => 'status_changed',
            'metadata' => ['status' => $data['status']],
            'occurred_at' => now(),
        ]);

        $audit->record(
            'TICKET_STATUS_CHANGED',
            $ticket,
            $oldValues,
            $ticket->fresh()->only(array_keys($oldValues))
        );

        return back()->with('success', 'Ticket status updated.');
    }

    public function comment(
        Request $request,
        Ticket $ticket,
        AuditService $audit
    ): RedirectResponse {
        $data = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
            'internal_only' => ['nullable', 'boolean'],
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'comment' => $data['comment'],
            'internal_only' => $request->boolean('internal_only', true),
        ]);

        $audit->record(
            'TICKET_COMMENT_ADDED',
            $comment,
            [],
            ['ticket_id' => $ticket->id]
        );

        return back()->with('success', 'Comment added.');
    }

    public function attachment(
        Ticket $ticket,
        TicketAttachment $attachment
    ): StreamedResponse {
        abort_unless(
            $attachment->ticket_id === $ticket->id && $attachment->is_active,
            404
        );

        return Storage::disk('local')->download(
            $attachment->stored_path,
            $attachment->original_name
        );
    }
}
