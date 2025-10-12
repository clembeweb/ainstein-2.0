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
        Schema::create('tenant_oauth_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('provider'); // 'google', 'facebook'
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable(); // Encrypted
            $table->string('redirect_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('scopes')->nullable(); // Additional scopes if needed
            $table->json('settings')->nullable(); // Additional provider-specific settings
            $table->timestamp('last_tested_at')->nullable();
            $table->string('test_status')->nullable(); // 'success', 'failed', 'not_tested'
            $table->text('test_message')->nullable();
            $table->timestamps();

            // Unique constraint: one provider per tenant
            $table->unique(['tenant_id', 'provider']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_oauth_providers');
    }
};