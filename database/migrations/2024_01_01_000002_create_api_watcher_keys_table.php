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

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique(); // Hashed token
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('api-watcher.storage.connection');
        $table = 'api_watcher_keys';

        Schema::connection($connection)->dropIfExists($table);
    }
};
