<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = $this->sanitizePath($path);

        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($path);

        return response()->file($absolutePath, [
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (str_contains($path, '..')) {
            abort(404);
        }

        return $path;
    }
}
