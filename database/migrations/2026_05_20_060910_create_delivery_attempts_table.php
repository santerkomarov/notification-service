<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('notification_id')
                ->constrained('notifications')
                ->cascadeOnDelete();

            $table->unsignedInteger('attempt_number');
            $table->string('provider', 50);
            $table->string('status', 30);
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_attempts');
    }
};
