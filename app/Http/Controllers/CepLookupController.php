<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JeffersonGoncalves\Cep\Models\Cep;

class CepLookupController extends Controller
{
    public function __invoke(string $postalCode): JsonResponse
    {
        $cepData = Cep::findByCep($postalCode);

        if (empty($cepData['cep'])) {
            return response()->json([
                'message' => __('messages.cep_not_found'),
            ], 404);
        }

        return response()->json([
            'postal_code' => $this->formatPostalCode((string) $cepData['cep']),
            'street' => (string) ($cepData['street'] ?? ''),
            'neighborhood' => (string) ($cepData['neighborhood'] ?? ''),
            'city' => (string) ($cepData['city'] ?? ''),
            'state' => strtoupper((string) ($cepData['state'] ?? '')),
        ]);
    }

    private function formatPostalCode(string $cep): string
    {
        $digits = preg_replace('/\D/', '', $cep) ?? '';

        if (strlen($digits) !== 8) {
            return $cep;
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5);
    }
}
