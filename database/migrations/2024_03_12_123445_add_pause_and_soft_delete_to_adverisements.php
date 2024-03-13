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
        Schema::table('advertisements', function (Blueprint $table) {
            $table->softDeletes(); // Add soft delete column
            $table->tinyInteger('paused')->default(0); // Add paused column with default value 0
            $table->timestamp('paused_at')->nullable(); // Add paused_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Remove soft delete column
            $table->dropColumn('paused'); // Remove paused column
            $table->dropColumn('paused_at'); // Remove paused_at column
        });
    }
};
