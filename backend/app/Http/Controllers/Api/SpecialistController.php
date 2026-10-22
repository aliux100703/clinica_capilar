<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialist;
use Illuminate\Http\JsonResponse;

class SpecialistController extends Controller
{
    public function index(): JsonResponse
    {
        return app(CmsContentController::class)->listing(Specialist::class);
    }
}
