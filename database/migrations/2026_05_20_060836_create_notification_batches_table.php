<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('idempotency_key')->nullable()->unique();

            $table->string('method', 20);
            $table->string('priority', 20);
            $table->text('message');
            $table->unsignedInteger('total_count');

            $table->timestamps();

            $table->index(['method', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_batches');
    }
};
