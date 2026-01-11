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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, completed, cancelled
            $table->integer('votes_required');
            $table->integer('votes_approve')->default(0);
            $table->integer('votes_reject')->default(0);
            $table->timestamp('voting_deadline');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'status']);
            $table->index(['status', 'voting_deadline']);
        });

        Schema::create('withdrawal_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('withdrawal_id')->constrained('withdrawals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('approved');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['withdrawal_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_votes');
        Schema::dropIfExists('withdrawals');
    }
};
