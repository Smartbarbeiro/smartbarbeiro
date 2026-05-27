<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    use RedirectsAfterAuth;

    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'redirect' => $request->query('redirect'),
            'isCustomerSignup' => $this->isCustomerSignup($request),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $isCustomerSignup = $this->isCustomerSignup($request);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique(User::class),
            ],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($isCustomerSignup) {
            $user = User::create([
                'name' => $request->name,
                'username' => null,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_barbershop' => false,
            ]);

            $this->attachCustomerToBarbershop($user, $request);
        } else {
            $username = $request->username
                ? app(UsernameGenerator::class)->uniqueFrom($request->name, $request->username)
                : app(UsernameGenerator::class)->uniqueFrom($request->name);

            $user = User::create([
                'name' => $request->name,
                'username' => $username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_barbershop' => true,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect($this->redirectAfterAuth($request));
    }

    private function attachCustomerToBarbershop(User $user, Request $request): void
    {
        $barbershopUsername = $this->barbershopUsernameFromRedirect($request);

        if ($barbershopUsername === null) {
            return;
        }

        $barbershop = User::query()
            ->where('username', $barbershopUsername)
            ->where('is_barbershop', true)
            ->first();

        if ($barbershop === null) {
            return;
        }

        BarbershopMembership::firstOrCreate([
            'barbershop_user_id' => $barbershop->id,
            'member_user_id' => $user->id,
        ]);
    }
}
