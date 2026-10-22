<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use App\Support\CmsPublicData;
use Illuminate\Http\JsonResponse;

class TreatmentController extends Controller
{
    public function index(): JsonResponse
    {
        return app(CmsContentController::class)->listing(Treatment::class);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json(['data' => CmsPublicData::make(Treatment::published()->where('slug', $slug)->firstOrFail()), 'message' => 'Tratamiento publicado.'], 200, ['Cache-Control' => 'no-store']);
    }
}
