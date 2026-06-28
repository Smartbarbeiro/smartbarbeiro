<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarbershopSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:50'],
        ]);

        $term = trim($validated['q']);
        $likeTerm = '%'.$term.'%';

        $results = User::query()
            ->where('is_barbershop', true)
            ->where('is_frozen', false)
            ->whereNotNull('username')
            ->where(function ($query) use ($term, $likeTerm) {
                $query->where('username', 'like', $term.'%')
                    ->orWhere('name', 'like', $likeTerm);
            })
            ->orderByRaw('CASE WHEN username LIKE ? THEN 0 ELSE 1 END', [$term.'%'])
            ->orderBy('username')
            ->limit(25)
            ->get()
            ->filter(fn (User $barbershop) => $barbershop->hasPublicProfile())
            ->take(10)
            ->map(fn (User $barbershop) => [
                'username' => $barbershop->username,
                'name' => $barbershop->name,
                'profile_photo_url' => $barbershop->profile_photo_url,
            ])
            ->values();

        return response()->json([
            'profile_base_url' => rtrim($request->getSchemeAndHttpHost(), '/').'/barbearias/',
            'results' => $results,
        ]);
    }
}
