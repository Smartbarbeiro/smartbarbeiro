<?php

namespace App\Http\Controllers;

use App\Models\ServicePlanSubscriptionPayment;
use App\Services\ServicePlanNotaFiscalPdfService;
use Illuminate\Http\Request;

class ServicePlanSubscriptionPaymentController extends Controller
{
    public function downloadNotaFiscal(
        ServicePlanSubscriptionPayment $payment,
        Request $request,
        ServicePlanNotaFiscalPdfService $pdfService,
    ) {
        $this->authorize('downloadNotaFiscal', $payment);

        return $pdfService->download($payment, $request->user());
    }
}
