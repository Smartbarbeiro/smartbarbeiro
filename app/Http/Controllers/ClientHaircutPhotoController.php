<?php

namespace App\Http\Controllers;

use App\Models\ClientHaircutPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientHaircutPhotoController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user->isBarbershop(), 403);

        $photos = $user->clientHaircutPhotos()
            ->with('barbershop:id,name,username')
            ->latest()
            ->get()
            ->map(fn (ClientHaircutPhoto $photo) => [
                'id' => $photo->id,
                'photo_url' => $photo->photo_url,
                'created_at' => $photo->created_at?->toIso8601String(),
                'barbershop' => $photo->barbershop ? [
                    'name' => $photo->barbershop->name,
                    'username' => $photo->barbershop->username,
                ] : null,
            ]);

        return Inertia::render('Haircuts/Index', [
            'photos' => $photos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->isBarbershop(), 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $barbershop = $user->primaryBarbershop();

        $photo = $user->clientHaircutPhotos()->create([
            'barbershop_user_id' => $barbershop?->id,
            'photo_path' => $validated['photo']->store(
                'client-haircut-photos/'.$user->id,
                'public',
            ),
        ]);

        return redirect()
            ->route('haircuts.index')
            ->with('status', 'haircut-photo-uploaded');
    }

    public function destroy(ClientHaircutPhoto $clientHaircutPhoto, Request $request): RedirectResponse
    {
        $this->authorize('delete', $clientHaircutPhoto);

        $clientHaircutPhoto->deleteStoredPhoto();
        $clientHaircutPhoto->delete();

        return redirect()
            ->route('haircuts.index')
            ->with('status', 'haircut-photo-deleted');
    }
}
