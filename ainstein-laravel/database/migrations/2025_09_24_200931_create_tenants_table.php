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
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('domain')->unique()->nullable();
            $table->string('subdomain')->unique();
            $table->string('plan_type')->default('starter');
            $table->integer('tokens_monthly_limit')->default(10000);
            $table->integer('tokens_used_current')->default(0);
            $table->string('status')->default('active');
            $table->json('theme_config')->nullable();
            $table->json('brand_config')->nullable();
            $table->string('features')->default('');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
