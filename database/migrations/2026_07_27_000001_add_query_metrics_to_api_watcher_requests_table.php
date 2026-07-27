<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = config('api-watcher.storage.table', 'api_watcher_requests');

        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'query_count')) {
                    $table->integer('query_count')->nullable()->after('memory_usage_kb');
                }
                if (! Schema::hasColumn($table->getTable(), 'query_time_ms')) {
                    $table->float('query_time_ms')->nullable()->after('query_count');
                }
            });
        }
    }

    public function down(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = config('api-watcher.storage.table', 'api_watcher_requests');

        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->table($table, function (Blueprint $table) {
                $table->dropColumn(['query_count', 'query_time_ms']);
            });
        }
    }
};
