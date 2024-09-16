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
        Schema::create('user_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->constrained();
            $table->string('sign_up_ip')->nullable();
            $table->text('android_fcm_regid')->nullable();
            $table->text('ios_fcm_token')->nullable();
            $table->text('android_msg_fcm_regid')->nullable();
            $table->text('ios_msg_fcm_regid')->nullable();
            $table->integer('referrer')->nullable();
            $table->integer('purchases_count')->nullable();
            $table->integer('referrals_count')->nullable();
            $table->integer('state')->nullable();
            $table->string('country', 100)->nullable();
            $table->integer('country_id')->nullable();
            $table->string('city', 100)->nullable();
            $table->integer('city_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_infos');
    }
};
