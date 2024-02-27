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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('advertiser_id');
            $table->string('ad_name');
            $table->string('publish_date')->nullable();
            $table->string('location')->nullable();
            $table->string('mile_radius')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip')->nullable();
            $table->json('duration_price')->nullable();
            $table->string('expired')->nullable()->default(0);
            $table->timestamp('expired_at')->nullable();
            $table->string('renew')->nullable()->default(0);
            $table->timestamp('renew_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
