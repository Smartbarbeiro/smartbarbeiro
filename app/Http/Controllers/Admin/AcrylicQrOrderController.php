<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcrylicQrOrder;
use App\Services\AcrylicQrOrderService;
use App\Services\BarbershopProfileQrPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AcrylicQrOrderController extends Controller
{
    public function __construct(
        private AcrylicQrOrderService $acrylicQrOrderService,
        private BarbershopProfileQrPdfService $barbershopProfileQrPdfService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AcrylicQrOrder::class);

        $status = $request->string('status')->trim()->toString();

        return Inertia::render('Admin/AcrylicQrOrders/Index', [
            'filters' => [
                'status' => $status !== '' ? $status : null,
            ],
            'orders' => $this->acrylicQrOrderService
                ->adminList($status !== '' ? $status : null)
                ->map(fn (AcrylicQrOrder $order) => [
                    ...$order->toSummaryArray(),
                    'barbershop' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'username' => $order->user->username,
                        'email' => $order->user->email,
                        'profile_url' => $order->user->profileUrl(),
                    ],
                ]),
        ]);
    }

    public function update(Request $request, AcrylicQrOrder $acrylicQrOrder): RedirectResponse
    {
        $this->authorize('update', $acrylicQrOrder);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['printed', 'shipped'])],
        ]);

        try {
            if ($validated['action'] === 'printed') {
                $this->acrylicQrOrderService->markPrinted($acrylicQrOrder);
            } else {
                $this->acrylicQrOrderService->markShipped($acrylicQrOrder);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        return back()->with('status', 'acrylic-qr-order-updated');
    }

    public function downloadPdf(AcrylicQrOrder $acrylicQrOrder): HttpResponse
    {
        $this->authorize('update', $acrylicQrOrder);

        $barbershop = $acrylicQrOrder->user()->firstOrFail();

        return $this->barbershopProfileQrPdfService->download(
            $barbershop,
            sprintf(
                'qrcode-%s-pedido-%d.pdf',
                $barbershop->username ?? $barbershop->id,
                $acrylicQrOrder->id,
            ),
            $acrylicQrOrder,
        );
    }
}
