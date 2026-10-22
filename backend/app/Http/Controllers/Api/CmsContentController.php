<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\HomePage;
use App\Models\Post;
use App\Models\Result;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Support\CmsPublicData;
use App\Support\PublicMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsContentController extends Controller
{
    public function listing(string $model): JsonResponse
    {
        $query = $model::published();
        $query->orderBy($model === Post::class ? 'published_at' : 'sort_order', $model === Post::class ? 'desc' : 'asc')->orderBy('id');
        $items = $query->paginate(12);
        $data = $items->getCollection()->filter(fn ($record) => ! ($record instanceof Result || $record instanceof Testimonial) || PublicMedia::consentValid($record))->map(fn ($record) => CmsPublicData::make($record))->values();

        return response()->json(['data' => $data, 'message' => 'Contenido publicado.', 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]], 200, ['Cache-Control' => 'no-store']);
    }

    public function index(Request $request): JsonResponse
    {
        $model = match ($request->route('type')) {
            'results' => Result::class,'posts' => Post::class,'testimonials' => Testimonial::class,'faqs' => Faq::class
        };

        return $this->listing($model);
    }

    public function home(): JsonResponse
    {
        return $this->singleton(HomePage::class);
    }

    public function settings(): JsonResponse
    {
        return $this->singleton(SiteSetting::class);
    }

    private function singleton(string $model): JsonResponse
    {
        $record = $model::published()->where('key', 'main')->first();

        return response()->json(['data' => $record ? CmsPublicData::make($record) : null, 'message' => 'Contenido publicado.'], 200, ['Cache-Control' => 'no-store']);
    }

    public function post(string $slug): JsonResponse
    {
        $record = Post::published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => CmsPublicData::make($record), 'message' => 'Artículo publicado.'], 200, ['Cache-Control' => 'no-store']);
    }
}
