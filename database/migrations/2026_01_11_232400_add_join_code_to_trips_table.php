<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Trip;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('join_code', 8)->unique()->nullable()->after('status');
        });

        // Generate join codes for existing trips
        Trip::whereNull('join_code')->each(function ($trip) {
            $trip->update(['join_code' => $this->generateUniqueCode()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('join_code');
        });
    }

    /**
     * Generate a unique 8-character code.
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Trip::where('join_code', $code)->exists());

        return $code;
    }
};
