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
        Schema::create('destinations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('location_url')->nullable(); // Google Maps link
            $table->date('visit_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('order')->default(0);
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->string('category')->default('attraction'); // attraction, food, transport, accommodation, other
            $table->timestamps();

            $table->index(['trip_id', 'visit_date', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
