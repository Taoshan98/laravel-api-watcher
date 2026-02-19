<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = config('api-watcher.storage.table', 'api_watcher_requests');

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method', 10);
            $table->text('url');
            $table->integer('status_code');
            $table->integer('duration_ms');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('controller_action')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('exception_info')->nullable(); // JSON or text
            $table->json('tags')->nullable();
            $table->integer('memory_usage_kb')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();

            $table->index('method');
            $table->index('status_code');
            $table->index('user_id');
            $table->index('ip_address');
            $table->index('route_name');
            $table->index(['created_at', 'duration_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = config('api-watcher.storage.table', 'api_watcher_requests');

        Schema::connection($connection)->dropIfExists($table);
    }
};
