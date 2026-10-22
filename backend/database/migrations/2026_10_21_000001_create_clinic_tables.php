<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description');
            $table->longText('content')->nullable();
            $table->string('image', 2048)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('specialists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('specialty', 150);
            $table->text('description');
            $table->string('photo', 2048)->nullable();
            $table->json('credentials')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('valuation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 254);
            $table->string('phone', 30);
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $table->date('preferred_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->boolean('consent');
            $table->timestamp('consented_at');
            $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 254);
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->boolean('consent');
            $table->timestamp('consented_at');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamps();
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valuation_request_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('time');
            $table->string('status', 30)->default('pending')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('user')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('valuation_requests');
        Schema::dropIfExists('specialists');
        Schema::dropIfExists('treatments');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
