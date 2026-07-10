<?php

namespace App\Http\Controllers;

use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StripeConnectController extends Controller
{
    public function start(Request $request, StripeConnectService $connect): RedirectResponse|HttpResponse
    {
        $user = $request->user();

        abort_unless($user?->isBarbershop(), 403);

        if (! $connect->isEnabled()) {
            return redirect()
                ->route('profile.edit')
                ->withErrors(['stripe_connect' => __('messages.payments_not_configured')]);
        }

        try {
            return Inertia::location($connect->createOnboardingUrl($user));
        } catch (ApiErrorException|\RuntimeException|\InvalidArgumentException $exception) {
            Log::warning('Stripe Connect onboarding failed', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('profile.edit')
                ->withErrors(['stripe_connect' => $exception->getMessage()]);
        }
    }

    public function return(Request $request, StripeConnectService $connect): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isBarbershop(), 403);

        try {
            $connect->syncAccount($user);
        } catch (\Throwable $exception) {
            Log::warning('Stripe Connect return sync failed', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        $user->refresh();

        $status = $user->isStripeConnectReady()
            ? 'stripe-connect-ready'
            : 'stripe-connect-pending';

        return redirect()
            ->route('profile.edit')
            ->with('status', $status)
            ->with('statusMessage', $user->isStripeConnectReady()
                ? __('messages.stripe_connect_ready')
                : __('messages.stripe_connect_pending'));
    }

    public function refresh(Request $request, StripeConnectService $connect): RedirectResponse
    {
        return $this->start($request, $connect);
    }
}
