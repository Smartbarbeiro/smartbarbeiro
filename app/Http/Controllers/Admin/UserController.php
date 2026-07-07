<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ProfileSubscription;
use App\Models\User;
use App\Services\AcrylicQrOrderService;
use App\Services\BarbershopProfileQrPdfService;
use App\Services\DeleteUserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserController extends Controller
{
    public function __construct(
        private DeleteUserAccountService $deleteUserAccount,
        private BarbershopProfileQrPdfService $barbershopProfileQrPdfService,
        private AcrylicQrOrderService $acrylicQrOrderService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $activeStatuses = ProfileSubscription::activeStatuses();

        $admins = $this->adminUsersQuery($request, $activeStatuses, $search)
            ->administrators()
            ->get()
            ->map(fn (User $user) => $this->mapUserForAdmin($user, $request));

        $barbershops = $this->adminUsersQuery($request, $activeStatuses, $search, includeMemberSearch: true)
            ->regularUsers()
            ->barbershopAccounts()
            ->with([
                'barbershopMembers' => fn ($query) => $query->latest(),
                'barbershopMembers.member' => fn ($query) => $query->withCount([
                    'subscribers',
                    'subscribers as active_subscribers_count' => fn ($subscribers) => $subscribers->whereIn('status', $activeStatuses),
                    'barbershopMembers',
                ]),
            ])
            ->paginate(10, ['*'], 'barbershop_page')
            ->withQueryString()
            ->through(fn (User $user) => $this->mapBarbershopForAdmin($user, $request));

        $unassignedClients = $this->adminUsersQuery($request, $activeStatuses, $search)
            ->regularUsers()
            ->customers()
            ->whereDoesntHave('barbershopSignups')
            ->paginate(15, ['*'], 'client_page')
            ->withQueryString()
            ->through(fn (User $user) => $this->mapUserForAdmin($user, $request));

        return Inertia::render('Admin/Users/Index', [
            'admins' => $admins,
            'barbershops' => $barbershops,
            'unassignedClients' => $unassignedClients,
            'barbershopAccountsCount' => User::countBarbershopAccounts(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    private function adminUsersQuery(
        Request $request,
        array $activeStatuses,
        string $search,
        bool $includeMemberSearch = false,
    ) {
        return User::query()
            ->withCount([
                'subscribers',
                'subscribers as active_subscribers_count' => fn ($query) => $query->whereIn('status', $activeStatuses),
                'barbershopMembers',
            ])
            ->when($search !== '', function ($query) use ($search, $includeMemberSearch) {
                $query->where(function ($query) use ($search, $includeMemberSearch) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    if ($includeMemberSearch) {
                        $query->orWhereHas('barbershopMembers.member', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                });
            })
            ->orderByDesc('created_at');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBarbershopForAdmin(User $user, Request $request): array
    {
        return [
            ...$this->mapUserForAdmin($user, $request),
            'members' => $user->barbershopMembers
                ->map(fn ($membership) => [
                    ...$this->mapUserForAdmin($membership->member, $request),
                    'joined_at' => $membership->created_at->translatedFormat('j M Y'),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUserForAdmin(User $user, Request $request): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'is_admin' => $user->isAdmin(),
            'is_barbershop' => $user->isBarbershop(),
            'is_frozen' => $user->isFrozen(),
            'platform_subscription_exempt' => $user->isBarbershopAccount()
                ? $user->isExemptFromPlatformSubscription()
                : null,
            'profile_photo_url' => $user->profile_photo_url,
            'profile_url' => $user->profileUrl(),
            'created_at' => $user->created_at->translatedFormat('j M Y'),
            'subscribers_count' => $user->subscribers_count,
            'active_subscribers_count' => $user->active_subscribers_count,
            'barbershop_members_count' => $user->barbershop_members_count,
            'can_delete' => $request->user()->can('delete', $user),
            'can_freeze' => $request->user()->can('freeze', $user),
        ];
    }

    public function edit(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'subscriptionPlan',
            'subscribers' => fn ($query) => $query
                ->with('subscriber:id,name,email,username')
                ->latest()
                ->limit(100),
            'barbershopMembers.member:id,name,email,username',
        ]);

        return Inertia::render('Admin/Users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'is_barbershop' => $user->isBarbershop(),
                'is_barbershop_account' => $user->isBarbershopAccount(),
                'is_frozen' => $user->isFrozen(),
                'platform_subscription_exempt' => $user->isBarbershopAccount()
                    ? $user->isExemptFromPlatformSubscription()
                    : null,
                'profile_photo_url' => $user->profile_photo_url,
                'profile_url' => $user->profileUrl(),
                'storage_path' => 'storage/app/users/'.$user->id,
                'created_at' => $user->created_at->translatedFormat('j M Y H:i'),
                'subscribers_count' => $user->subscribers()->count(),
                'active_subscribers_count' => $user->subscribers()
                    ->whereIn('status', ProfileSubscription::activeStatuses())
                    ->count(),
                'subscriptions_count' => $user->profileSubscriptions()->count(),
                'barbershop_members_count' => $user->barbershopMembers()->count(),
                'has_subscription_plan' => $user->subscriptionPlan()->exists(),
                'subscribers' => $user->subscribers->map(fn ($subscription) => [
                    ...$subscription->toSummaryArray(),
                    'subscriber' => [
                        'name' => $subscription->subscriber->name,
                        'email' => $subscription->subscriber->email,
                        'username' => $subscription->subscriber->username,
                    ],
                ]),
                'barbershop_members' => $user->barbershopMembers->map(fn ($membership) => [
                    'id' => $membership->id,
                    'member' => [
                        'name' => $membership->member->name,
                        'email' => $membership->member->email,
                        'username' => $membership->member->username,
                    ],
                    'joined_at' => $membership->created_at->translatedFormat('j M Y'),
                ]),
            ],
            'canDelete' => request()->user()->can('delete', $user),
            'canFreeze' => request()->user()->can('freeze', $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $fields = ['name', 'email'];

        if ($user->isBarbershop()) {
            $fields[] = 'username';
        }

        $user->fill($request->safe()->only($fields));

        if ($request->user()->id !== $user->id) {
            $user->is_admin = $request->boolean('is_admin');

            if ($user->is_admin) {
                $user->is_barbershop = false;
                $user->username = null;
                $user->platform_subscription_exempt = false;
            }

            if ($request->user()->can('freeze', $user)) {
                $user->is_frozen = $request->boolean('is_frozen');
            }

            if ($user->isBarbershopAccount() && $request->has('platform_subscription_exempt')) {
                $user->platform_subscription_exempt = $request->boolean(
                    'platform_subscription_exempt',
                );
            }
        }

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('admin.users.edit', $user)
            ->with('status', 'user-updated');
    }

    public function toggleFreeze(Request $request, User $user): RedirectResponse
    {
        $this->authorize('freeze', $user);

        $user->update([
            'is_frozen' => ! $user->isFrozen(),
        ]);

        return Redirect::back()
            ->with('status', $user->isFrozen() ? 'user-frozen' : 'user-unfrozen');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->deleteUserAccount->delete($user);

        return Redirect::route('admin.users.index')
            ->with('status', 'user-deleted');
    }

    public function downloadQrPdf(User $user): HttpResponse
    {
        $this->authorize('view', $user);

        $acrylicQrOrder = $user->acrylicQrOrders()->latest()->first();

        return $this->barbershopProfileQrPdfService->download(
            $user,
            acrylicQrOrder: $acrylicQrOrder,
        );
    }
}
