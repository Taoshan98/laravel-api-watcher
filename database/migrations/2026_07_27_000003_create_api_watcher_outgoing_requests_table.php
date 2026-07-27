<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = 'api_watcher_outgoing_requests';

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('parent_request_id')->nullable()->index(); // Correlation with incoming API request
            $table->string('domain', 255)->index();
            $table->string('method', 10);
            $table->text('url');
            $table->integer('status_code')->index();
            $table->integer('duration_ms');
            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('exception_info')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = 'api_watcher_outgoing_requests';

        Schema::connection($connection)->dropIfExists($table);
    }
};
