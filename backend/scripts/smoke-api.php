<?php

use App\Models\ContactMessage;
use App\Models\Treatment;
use App\Models\ValuationRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

// Local integration check using only temporary synthetic records owned by this run.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local') || config('database.default') !== 'mysql'
    || config('database.connections.mysql.database') !== 'clinica_capilar'
    || config('database.connections.mysql.host') !== '127.0.0.1') {
    fwrite(STDERR, "This smoke test requires the local clinica_capilar MySQL database.\n");
    exit(1);
}

$contactKey = (string) Str::uuid();
$valuationKey = (string) Str::uuid();
$treatment = null;
$success = false;

function requestApi(string $path, ?array $payload = null): array
{
    $curl = curl_init('http://127.0.0.1:8000/api/v1/'.$path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Origin: http://localhost:3000'],
    ]);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $body = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    return [$code, json_decode($body ?: '', true)];
}

function verify(bool $condition, string $label): void
{
    if (! $condition) {
        throw new RuntimeException('Check failed: '.$label);
    }
    echo 'OK: '.$label.PHP_EOL;
}

try {
    $treatment = Treatment::create([
        'name' => 'Prueba técnica temporal (no es un servicio)',
        'slug' => 'prueba-tecnica-'.Str::uuid(),
        'description' => 'Registro sintético para comprobar la integración; se elimina al terminar.',
        'status' => 'published',
    ]);
    [$code, $body] = requestApi('treatments/'.$treatment->slug);
    verify($code === 200 && ($body['data']['id'] ?? null) === $treatment->id, 'published catalog detail');
    $common = ['name' => 'Persona ficticia de integración', 'email' => 'integration@example.test', 'phone' => '+52 555 000 0000', 'consent' => true];
    $contact = $common + ['message' => 'Prueba técnica ficticia; sin datos médicos.', 'idempotency_key' => $contactKey];
    [$code, $body] = requestApi('contact-messages', $contact);
    verify($code === 201 && array_key_exists('data', $body) && $body['data'] === null, 'contact HTTP response without personal data');
    [$code] = requestApi('contact-messages', $contact);
    verify($code === 201 && ContactMessage::where('idempotency_key', $contactKey)->count() === 1, 'contact retry saved exactly once in MySQL');
    [$code] = requestApi('valuation-requests', $common + ['idempotency_key' => $valuationKey, 'treatment_id' => $treatment->id]);
    $valuation = ValuationRequest::where('idempotency_key', $valuationKey)->first();
    verify($code === 201 && $valuation?->status === 'pending', 'valuation stored pending in MySQL');
    verify($valuation->appointments()->count() === 0, 'no appointment created');
    $success = true;
} catch (Throwable $e) {
    // No payloads, credentials, or database exception messages in output.
    fwrite(STDERR, "Smoke test failed. Check API availability and rate limits.\n");
} finally {
    ContactMessage::where('idempotency_key', $contactKey)->delete();
    ValuationRequest::where('idempotency_key', $valuationKey)->delete();
    $treatment?->delete();
    echo "Temporary synthetic records cleaned up.\n";
}
exit($success ? 0 : 1);
