<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminBroadcastMessageRequest;
use App\Models\User;
use App\Services\AdminBroadcastMessageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class AdminBroadcastMessageController extends Controller
{
    public function __construct(
        private AdminBroadcastMessageService $messageService,
    ) {}

    public function index(): Response
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $barbershops = User::query()
            ->regularUsers()
            ->barbershopAccounts()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $clients = User::query()
            ->regularUsers()
            ->customers()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Messages/Index', [
            'barbershops' => $barbershops,
            'clients' => $clients,
            'sentMessages' => $this->messageService->sentMessages()->map(fn ($message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'audience' => $message->audience,
                'recipients_count' => $message->recipients_count,
                'send_email' => $message->send_email,
                'created_at' => $message->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(StoreAdminBroadcastMessageRequest $request): RedirectResponse
    {
        try {
            $this->messageService->send(
                admin: $request->user(),
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
            ->route('admin.messages.index')
            ->with('status', 'admin-message-sent');
    }
}
