<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcrylicQrOrderRequest;
use App\Services\AcrylicQrOrderService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class AcrylicQrOrderController extends Controller
{
    public function __construct(
        private AcrylicQrOrderService $acrylicQrOrderService,
    ) {}

    public function store(StoreAcrylicQrOrderRequest $request): RedirectResponse
    {
        try {
            $this->acrylicQrOrderService->create(
                $request->user(),
                $request->validated(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['recipient_name' => $exception->getMessage()])
                ->withInput();
        }

        return back()->with('status', 'acrylic-qr-order-created');
    }
}
