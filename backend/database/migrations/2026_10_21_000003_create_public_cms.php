<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $t) {
            $t->json('gallery')->nullable();
            $t->unsignedInteger('sort_order')->default(0)->index();
            $t->string('seo_title', 180)->nullable();
            $t->text('seo_description')->nullable();
        });
        Schema::table('specialists', fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index());
        Schema::create('media_assets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('path');
            $t->string('mime', 80);
            $t->unsignedInteger('width');
            $t->unsignedInteger('height');
            $t->unsignedBigInteger('size');
            $t->timestamps();
        });
        Schema::create('home_pages', function (Blueprint $t) {
            $t->id();
            $t->string('key')->default('main')->unique();
            $t->string('title', 200);
            $t->text('subtitle')->nullable();
            $t->string('cover_image', 2048)->nullable();
            $t->string('image_alt')->nullable();
            $t->json('buttons')->nullable();
            $t->text('presentation')->nullable();
            $t->json('sections')->nullable();
            $t->json('copy')->nullable();
            $t->string('seo_title', 180)->nullable();
            $t->text('seo_description')->nullable();
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->default('main')->unique();
            $t->string('trade_name', 150);
            $t->string('tagline')->nullable();
            $t->string('logo', 2048)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('whatsapp', 30)->nullable();
            $t->string('email', 254)->nullable();
            $t->text('address')->nullable();
            $t->json('social_links')->nullable();
            $t->json('opening_hours')->nullable();
            $t->text('contact_information')->nullable();
            $t->json('navigation')->nullable();
            $t->text('footer_text')->nullable();
            $t->json('pages')->nullable();
            $t->string('seo_title', 180)->nullable();
            $t->text('seo_description')->nullable();
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('treatment_id')->constrained()->restrictOnDelete();
            $t->string('before_image', 2048);
            $t->string('after_image', 2048);
            $t->text('description');
            $t->string('evolution_time', 150);
            $t->boolean('consent_granted')->default(false);
            $t->string('consent_document')->nullable();
            $t->date('consent_recorded_at')->nullable();
            $t->date('consent_expires_at')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('posts', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('slug', 180)->unique();
            $t->text('excerpt');
            $t->longText('content');
            $t->string('image', 2048)->nullable();
            $t->string('category', 100);
            $t->string('author', 150);
            $t->foreignId('medical_reviewer_id')->nullable()->constrained('specialists')->restrictOnDelete();
            $t->boolean('requires_medical_review')->default(true);
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamp('published_at')->nullable()->index();
            $t->string('seo_title', 180)->nullable();
            $t->text('seo_description')->nullable();
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('testimonials', function (Blueprint $t) {
            $t->id();
            $t->string('public_name', 150);
            $t->text('quote');
            $t->string('photo', 2048)->nullable();
            $t->boolean('consent_granted')->default(false);
            $t->string('consent_document')->nullable();
            $t->date('consent_recorded_at')->nullable();
            $t->date('consent_expires_at')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('question', 300);
            $t->text('answer');
            $t->string('category', 100)->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->string('status', 30)->default('draft')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['faqs', 'testimonials', 'posts', 'results', 'site_settings', 'home_pages', 'media_assets'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('treatments', fn (Blueprint $t) => $t->dropColumn(['gallery', 'sort_order', 'seo_title', 'seo_description']));
        Schema::table('specialists', fn (Blueprint $t) => $t->dropColumn('sort_order'));
    }
};
