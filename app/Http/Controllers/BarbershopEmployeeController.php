<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarbershopEmployeeRequest;
use App\Http\Requests\UpdateBarbershopEmployeeRequest;
use App\Models\BarbershopEmployee;
use App\Services\BarbershopEmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BarbershopEmployeeController extends Controller
{
    public function index(Request $request, BarbershopEmployeeService $employeeService): Response
    {
        $user = $request->user();

        abort_unless($user->isBarbershop(), 403);

        return Inertia::render('Employees/Index', [
            'employees' => $employeeService->payloadFor($user),
        ]);
    }

    public function store(
        StoreBarbershopEmployeeRequest $request,
        BarbershopEmployeeService $employeeService,
    ): RedirectResponse {
        $employeeService->createFor($request->user(), $request->validated());

        return back()->with('status', 'employee-created');
    }

    public function update(
        UpdateBarbershopEmployeeRequest $request,
        BarbershopEmployee $employee,
        BarbershopEmployeeService $employeeService,
    ): RedirectResponse {
        $employeeService->update($employee, $request->validated());

        return back()->with('status', 'employee-updated');
    }

    public function destroy(
        Request $request,
        BarbershopEmployee $employee,
        BarbershopEmployeeService $employeeService,
    ): RedirectResponse {
        abort_unless($request->user()->can('delete', $employee), 403);

        $employeeService->delete($employee);

        return back()->with('status', 'employee-deleted');
    }
}
