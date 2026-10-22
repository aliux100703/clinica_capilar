<?php

use App\Models\Faq;
use App\Models\Post;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'clinica_capilar') {
    fwrite(STDERR, "This check requires the local clinica_capilar database.\n");
    exit(1);
}

$options = getopt('', ['frontend-url:']);
$base = rtrim($options['frontend-url'] ?? 'http://127.0.0.1:3000', '/');
if (! in_array($base, ['http://127.0.0.1:3000', 'http://localhost:3000', 'http://127.0.0.1:3100', 'http://localhost:3100'], true)) {
    fwrite(STDERR, "Use a local frontend URL on port 3000 or 3100.\n");
    exit(1);
}
config(['cms.revalidate_url' => $base.'/api/revalidate']);
$marker = 'CMS_CHECK_'.str_replace('-', '', (string) Str::uuid());
$faq = null;
$post = null;
$success = false;
function checkCms(bool $condition, string $label): void
{
    if (! $condition) {
        throw new RuntimeException('CMS check failed: '.$label);
    }
    echo 'OK: '.$label.PHP_EOL;
}
try {
    checkCms(Http::timeout(15)->get($base)->successful(), 'Next.js server available at '.$base);
    $unauthorized = Http::post($base.'/api/revalidate');
    checkCms($unauthorized->status() === 401, 'revalidation rejects missing token');
    $faq = Faq::create(['question' => $marker.'_INITIAL', 'answer' => 'Contenido técnico ficticio, sin información médica.', 'status' => 'published']);
    checkCms(! Cache::has('cms.revalidation_pending'), 'save confirmed cache invalidation');
    checkCms(str_contains(Http::get('http://127.0.0.1:8000/api/v1/faqs')->body(), $marker), 'published FAQ available through Laravel HTTP API');
    $body = Http::timeout(20)->get($base)->body();
    checkCms(str_contains($body, $marker.'_INITIAL'), 'published FAQ rendered in Next.js');
    $faq->update(['question' => $marker.'_EDITED']);
    $body = Http::timeout(20)->get($base)->body();
    checkCms(str_contains($body, $marker.'_EDITED') && ! str_contains($body, $marker.'_INITIAL'), 'edited FAQ refreshed without rebuild');
    $faq->update(['status' => 'draft']);
    checkCms(! str_contains(Http::timeout(20)->get($base)->body(), $marker), 'unpublished FAQ removed from Next.js');
    $post = Post::create(['title' => $marker.'_ARTICLE', 'slug' => 'cms-check-'.Str::uuid(), 'excerpt' => 'Fixture técnica no médica', 'content' => '<p>Contenido técnico ficticio.</p>', 'category' => 'Pruebas', 'author' => 'Autor ficticio', 'requires_medical_review' => false, 'published_at' => now()->subMinute(), 'status' => 'published']);
    $body = Http::timeout(20)->withHeaders(['User-Agent' => 'Twitterbot'])->get($base.'/blog/'.$post->slug)->body();
    checkCms(str_contains($body, '<title>'.$marker.'_ARTICLE</title>'), 'dynamic article SEO metadata');
    $post->update(['status' => 'draft']);
    $response = Http::timeout(20)->withHeaders(['User-Agent' => 'Twitterbot'])->get($base.'/blog/'.$post->slug);
    checkCms(! str_contains($response->body(), '<title>'.$marker.'_ARTICLE</title>') && str_contains($response->body(), 'noindex'), 'unpublished article renders not-found and noindex');
    $success = true;
} catch (Throwable $e) {
    if ($e instanceof RuntimeException && str_starts_with($e->getMessage(), 'CMS check failed: ')) {
        fwrite(STDERR, $e->getMessage().PHP_EOL);
    }
    fwrite(STDERR, "CMS HTTP check failed. Verify port 8000, frontend URL, matching server secrets and content rate limits.\n");
} finally {
    $faq?->delete();
    $post?->delete();
    echo "Temporary CMS fixtures removed.\n";
}
exit($success ? 0 : 1);
