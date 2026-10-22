<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['valuation_requests', 'contact_messages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->uuid('idempotency_key')->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        foreach (['valuation_requests', 'contact_messages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
