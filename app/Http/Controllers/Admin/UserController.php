<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\DeleteUserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private DeleteUserAccountService $deleteUserAccount,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'profile_photo_url' => $user->profile_photo_url,
                'profile_url' => $user->profileUrl(),
                'created_at' => $user->created_at->format('M j, Y'),
                'can_delete' => $request->user()->id !== $user->id,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Admin/Users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => $user->isAdmin(),
                'profile_photo_url' => $user->profile_photo_url,
                'profile_url' => $user->profileUrl(),
                'storage_path' => 'storage/app/users/'.$user->id,
                'created_at' => $user->created_at->format('M j, Y g:i A'),
                'subscribers_count' => $user->subscribers()->count(),
                'subscriptions_count' => $user->profileSubscriptions()->count(),
                'has_subscription_plan' => $user->subscriptionPlan()->exists(),
            ],
            'canDelete' => request()->user()->can('delete', $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->fill($request->safe()->only(['name', 'username', 'email']));

        if ($request->user()->id !== $user->id) {
            $user->is_admin = $request->boolean('is_admin');
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

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->deleteUserAccount->delete($user);

        return Redirect::route('admin.users.index')
            ->with('status', 'user-deleted');
    }
}
