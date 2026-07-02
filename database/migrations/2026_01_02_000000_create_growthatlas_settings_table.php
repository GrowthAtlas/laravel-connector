<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value settings for the GrowthAtlas connector.
 *
 * Lets the API key, signing secret and logging flag be managed from the
 * Filament admin page instead of editing the .env file. When a key is present
 * here it overrides the matching env/config value.
 *
 * Publish with: php artisan vendor:publish --tag=growthatlas-connector-migrations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growthatlas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growthatlas_settings');
    }
};
