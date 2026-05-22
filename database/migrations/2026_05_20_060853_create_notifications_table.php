<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\NotificationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('batch_id')
                ->constrained('notification_batches')
                ->cascadeOnDelete();

            $table->foreignId('subscriber_id')
                ->constrained('subscribers')
                ->cascadeOnDelete();

            $table->string('method', 20);
            $table->string('priority', 20);
            $table->text('message');

            $table->string('status', 30)->default(NotificationStatus::Queued->value);

            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('dropped_at')->nullable();

            $table->timestamps();

            $table->unique(['batch_id', 'subscriber_id']);

            $table->index('subscriber_id');
            $table->index('batch_id');
            $table->index('status');
            $table->index(['priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
