<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BarbershopMembership;
use App\Models\User;
use App\Rules\UniqueTaxDocument;
use App\Services\UsernameGenerator;
use App\Support\TaxDocument;
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

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($isCustomerSignup) {
            $rules['cpf'] = ['required', 'string', 'cpf', new UniqueTaxDocument];
        } else {
            $rules['cpf_cnpj'] = ['required', 'string', 'cpf_ou_cnpj', new UniqueTaxDocument];
            $rules['username'] = [
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique(User::class),
            ];
        }

        $validated = $request->validate($rules);

        $taxDocument = TaxDocument::normalize(
            $isCustomerSignup ? $validated['cpf'] : $validated['cpf_cnpj'],
        );

        if ($isCustomerSignup) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => null,
                'email' => $validated['email'],
                'tax_document' => $taxDocument,
                'password' => Hash::make($validated['password']),
                'is_barbershop' => false,
            ]);

            $this->attachCustomerToBarbershop($user, $request);
        } else {
            $username = app(UsernameGenerator::class)->uniqueFrom(
                $validated['name'],
                $validated['username'],
            );

            $user = User::create([
                'name' => $validated['name'],
                'username' => $username,
                'email' => $validated['email'],
                'tax_document' => $taxDocument,
                'password' => Hash::make($validated['password']),
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
