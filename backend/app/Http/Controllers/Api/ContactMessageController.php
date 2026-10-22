<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['website', 'idempotency_key']);
        $data['consent'] = true;
        $data['consented_at'] = now();
        $data['status'] = 'pending';
        $record = new ContactMessage;
        $record->idempotency_key = $request->validated('idempotency_key');
        $record->fill($data);
        try {
            $record->save();
        } catch (UniqueConstraintViolationException $e) {
            if (! ContactMessage::where('idempotency_key', $record->idempotency_key)->exists()) {
                throw $e;
            }
        }

        return response()->json(['data' => null, 'message' => 'Mensaje recibido.'], 201);
    }
}
