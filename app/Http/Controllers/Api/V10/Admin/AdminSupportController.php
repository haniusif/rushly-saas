<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\SupportStatus;
use App\Http\Controllers\Controller;
use App\Models\Backend\Support;
use App\Models\Backend\SupportChat;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSupportController extends Controller
{
    use ApiReturnFormatTrait;

    public function index(Request $request)
    {
        $query = Support::query()
            ->with(['user', 'department'])
            ->latest();

        if (!is_null($status = $request->query('status'))) {
            $query->where('status', (int) $status);
        }
        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('subject', 'like', "%$q%")
                  ->orWhereHas('user', fn ($u) =>
                      $u->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%")
                  );
            });
        }

        $per = max(10, min(100, (int) $request->query('per_page', 25)));
        $tickets = $query->paginate($per);

        return $this->responseWithSuccess('admin.support', [
            'tickets' => $tickets->through(fn ($t) => $this->transform($t)),
        ], 200);
    }

    public function show($id)
    {
        $ticket = Support::with(['user', 'department'])->findOrFail($id);
        $replies = SupportChat::where('support_id', $ticket->id)
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'message'    => $c->message ?? $c->description,
                'author'     => optional($c->user)->name,
                'author_type'=> (int) (optional($c->user)->user_type ?? 0),
                'created_at' => optional($c->created_at)->toIso8601String(),
            ]);

        $payload = $this->transform($ticket);
        $payload['replies'] = $replies;

        return $this->responseWithSuccess('admin.support.show', ['ticket' => $payload], 200);
    }

    public function reply($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('admin.support.reply', ['message' => $validator->errors()], 422);
        }

        $ticket = Support::findOrFail($id);

        $chat = new SupportChat();
        $chat->support_id = $ticket->id;
        $chat->user_id    = auth()->id();
        // Fall back to whichever message column the schema actually has.
        if (in_array('message', $chat->getFillable() ?: [], true) || \Schema::hasColumn('support_chats', 'message')) {
            $chat->message = $request->message;
        }
        if (\Schema::hasColumn('support_chats', 'description')) {
            $chat->description = $request->message;
        }
        $chat->save();

        if ((int) $ticket->status === SupportStatus::PENDING) {
            $ticket->status = SupportStatus::PROCESSING;
            $ticket->save();
        }

        return $this->responseWithSuccess('admin.support.replied', ['ticket_id' => $ticket->id], 200);
    }

    public function close($id, Request $request)
    {
        $ticket = Support::findOrFail($id);
        $ticket->status = SupportStatus::CLOSED;
        $ticket->save();

        return $this->responseWithSuccess('admin.support.closed', ['ticket_id' => $ticket->id], 200);
    }

    private function transform(Support $t): array
    {
        return [
            'id'          => $t->id,
            'subject'     => $t->subject,
            'description' => $t->description ?? $t->message,
            'status'      => (int) $t->status,
            'priority'    => $t->priority,
            'service'     => $t->service,
            'department'  => optional($t->department)->title,
            'date'        => optional($t->created_at)->format('d M Y'),
            'user' => [
                'id'    => optional($t->user)->id,
                'name'  => optional($t->user)->name,
                'email' => optional($t->user)->email,
                'phone' => (string) optional($t->user)->mobile,
                'user_type' => (int) (optional($t->user)->user_type ?? 0),
            ],
        ];
    }
}
