<?php

namespace App\Http\Controllers;

use App\Services\BarbershopServicePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BarbershopServicePlanController extends Controller
{
    public function update(Request $request, BarbershopServicePlanService $planService): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        $validated = $request->validate([
            'packages' => ['required', 'array'],
            'packages.cut' => ['required', 'array'],
            'packages.cut.monthly_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'packages.cut.is_enabled' => ['required', 'boolean'],
            'packages.cut_beard' => ['required', 'array'],
            'packages.cut_beard.monthly_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'packages.cut_beard.is_enabled' => ['required', 'boolean'],
            'addons' => ['nullable', 'array'],
            'addons.*.id' => ['nullable', 'integer'],
            'addons.*.name' => ['required_with:addons.*.monthly_price', 'string', 'max:120'],
            'addons.*.monthly_price' => ['required_with:addons.*.name', 'numeric', 'min:0', 'max:99999'],
            'addons.*.is_enabled' => ['nullable', 'boolean'],
            'addons.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'deleted_addon_ids' => ['nullable', 'array'],
            'deleted_addon_ids.*' => ['integer'],
        ]);

        $planService->updateForUser($user, $validated);

        return back()->with('status', 'service-plans-updated');
    }
}
