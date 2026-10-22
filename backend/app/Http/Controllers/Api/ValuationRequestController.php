<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreValuationRequest;
use App\Models\ValuationRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class ValuationRequestController extends Controller
{
    public function store(StoreValuationRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['website', 'idempotency_key']);
        $data['consent'] = true;
        $data['consented_at'] = now();
        $data['status'] = 'pending';
        $record = new ValuationRequest;
        $record->idempotency_key = $request->validated('idempotency_key');
        $record->fill($data);
        try {
            $record->save();
        } catch (UniqueConstraintViolationException $e) {
            if (! ValuationRequest::where('idempotency_key', $record->idempotency_key)->exists()) {
                throw $e;
            }
        }

        return response()->json(['data' => null, 'message' => 'Solicitud recibida. La clínica contactará contigo; esto no confirma una cita.'], 201);
    }
}
