<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = 'api_watcher_keys';

        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'scopes')) {
                    $table->json('scopes')->nullable()->after('token');
                }
            });
        }
    }

    public function down(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = 'api_watcher_keys';

        if (Schema::connection($connection)->hasTable($table)) {
            Schema::connection($connection)->table($table, function (Blueprint $table) {
                $table->dropColumn(['scopes']);
            });
        }
    }
};
