<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit-log table for inbound GrowthAtlas requests.
 * Only used when growthatlas-connector.log_inbound = true.
 * Powers the Filament ConnectorStatus admin page.
 *
 * Publish with: php artisan vendor:publish --tag=growthatlas-connector-migrations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growthatlas_inbound_requests', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 64);
            $table->unsignedSmallInteger('status_code');
            $table->boolean('signature_valid')->nullable();
            $table->json('payload_summary')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growthatlas_inbound_requests');
    }
};
