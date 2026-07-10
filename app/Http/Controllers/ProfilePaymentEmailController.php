<?php

namespace App\Http\Controllers;

use App\Services\PaymentEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfilePaymentEmailController extends Controller
{
    public function update(Request $request, PaymentEmailService $paymentEmailService): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['billing', 'account'])],
            'payer_email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $user = $request->user();
        $payerEmail = $paymentEmailService->normalize($validated['payer_email']);

        abort_unless(
            $payerEmail !== null
            && $paymentEmailService->userHasSubscriptionWithPayerEmail($user, $payerEmail),
            403,
        );

        if ($validated['action'] === 'billing') {
            $user->update(['billing_email' => $payerEmail]);

            return back()->with('status', 'payment-email-billing-saved');
        }

        $request->validate([
            'payer_email' => [
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update([
            'email' => $payerEmail,
            'billing_email' => null,
        ]);

        return back()->with('status', 'payment-email-account-updated');
    }
}
