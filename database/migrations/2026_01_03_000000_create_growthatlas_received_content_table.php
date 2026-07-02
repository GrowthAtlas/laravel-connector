<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks content received from GrowthAtlas so the admin page can list every
 * article that was published/updated on this site, link to it locally and
 * back to the originating draft inside the GrowthAtlas dashboard.
 *
 * Publish with: php artisan vendor:publish --tag=growthatlas-connector-migrations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growthatlas_received_content', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('growthatlas_draft_id')->nullable()->index();
            $table->unsignedBigInteger('growthatlas_brief_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('title')->nullable();
            $table->string('url', 1024)->nullable();
            $table->string('growthatlas_url', 1024)->nullable();
            $table->string('status', 32)->nullable();
            $table->unsignedInteger('seo_score')->nullable();
            $table->unsignedInteger('update_count')->default(0);
            $table->timestamp('last_action_at')->nullable();
            $table->timestamps();

            $table->unique('growthatlas_draft_id', 'ga_received_draft_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growthatlas_received_content');
    }
};
