<?php

namespace App\Http\Controllers;

use App\Services\BarbershopCommissionReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarbershopCommissionController extends Controller
{
    public function index(Request $request, BarbershopCommissionReportService $reportService): Response
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        $month = $request->string('month')->toString() ?: null;

        return Inertia::render('Commissions/Index', [
            'report' => $reportService->reportPayload($user, $month),
        ]);
    }
}
