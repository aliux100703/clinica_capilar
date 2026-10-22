<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RevalidateContent
{
    public function send(): bool
    {
        Cache::forever('cms.revalidation_pending', true);
        $url = config('cms.revalidate_url');
        $secret = config('cms.revalidate_secret');
        if (! $url || ! $secret) {
            return false;
        }
        try {
            $response = Http::timeout(3)->connectTimeout(1)->withToken($secret)->post($url, ['tag' => 'cms']);
            if ($response->successful()) {
                Cache::forget('cms.revalidation_pending');

                return true;
            }
        } catch (\Throwable $e) { /* No content, personal data or secrets in logs. */
        }

        return false;
    }
}
