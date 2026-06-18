<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarbershopMessageRequest;
use App\Models\BarbershopMessage;
use App\Services\BarbershopClientAudienceService;
use App\Services\BarbershopMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BarbershopMessageController extends Controller
{
    public function __construct(
        private BarbershopMessageService $messageService,
        private BarbershopClientAudienceService $audienceService,
    ) {}

    public function inbox(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->isBarbershop()) {
            return redirect()->route('messages.compose');
        }

        return Inertia::render('Messages/Inbox', [
            'messages' => $this->messageService->inboxFor($user)->map(
                fn (BarbershopMessage $message) => $this->toInboxArray($message, $user->id),
            ),
        ]);
    }

    public function compose(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        $clients = $this->audienceService->clientsFor($user);

        return Inertia::render('Messages/Compose', [
            'clients' => $clients->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ]),
            'sentMessages' => $this->messageService->sentFor($user)->map(
                fn (BarbershopMessage $message) => [
                    'id' => $message->id,
                    'subject' => $message->subject,
                    'recipients_count' => $message->recipients_count,
                    'send_email' => $message->send_email,
                    'created_at' => $message->created_at?->toIso8601String(),
                ],
            ),
        ]);
    }

    public function store(StoreBarbershopMessageRequest $request): RedirectResponse
    {
        try {
            $message = $this->messageService->send(
                barbershop: $request->user(),
                subject: $request->string('subject')->toString(),
                body: $request->string('body')->toString(),
                sendEmail: $request->boolean('send_email', true),
                audience: $request->string('audience')->toString(),
                recipientIds: $request->input('recipient_ids', []),
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['body' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('messages.show', $message)
            ->with('status', 'message-sent');
    }

    public function show(Request $request, BarbershopMessage $message): Response
    {
        $this->authorize('view', $message);

        $user = $request->user();
        $recipientRow = $message->recipients()
            ->where('recipient_user_id', $user->id)
            ->first();

        if ($recipientRow) {
            $recipientRow->markAsRead();
        }

        $message->load(['barbershop:id,name,username', 'recipients.recipient:id,name,email']);

        return Inertia::render('Messages/Show', [
            'message' => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'send_email' => $message->send_email,
                'created_at' => $message->created_at?->toIso8601String(),
                'is_sender' => $message->barbershop_user_id === $user->id,
                'barbershop' => [
                    'name' => $message->barbershop->name,
                    'username' => $message->barbershop->username,
                    'profile_url' => $message->barbershop->profileUrl(),
                ],
                'recipients' => $message->recipients->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->recipient->name,
                    'email' => $row->recipient->email,
                    'read_at' => $row->read_at?->toIso8601String(),
                    'email_sent_at' => $row->email_sent_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toInboxArray(BarbershopMessage $message, int $userId): array
    {
        $recipientRow = $message->recipients->first(
            fn ($row) => $row->recipient_user_id === $userId,
        );

        return [
            'id' => $message->id,
            'subject' => $message->subject,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
            'read_at' => $recipientRow?->read_at?->toIso8601String(),
            'barbershop' => [
                'name' => $message->barbershop->name,
                'username' => $message->barbershop->username,
            ],
        ];
    }
}
