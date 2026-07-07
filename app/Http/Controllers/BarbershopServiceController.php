<?php

namespace App\Http\Controllers;

use App\Services\BarbershopServicePlanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarbershopServiceController extends Controller
{
    public function index(Request $request, BarbershopServicePlanService $servicePlanService): Response
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        return Inertia::render('Services/Index', [
            'servicePlans' => [
                'packages' => $servicePlanService->packagesPayload($user),
                'addons' => $servicePlanService->addonsPayload($user),
            ],
        ]);
    }
}
