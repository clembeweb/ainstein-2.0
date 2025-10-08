<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_brands', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();

            // Brand Identity
            $table->string('brand_name')->nullable();
            $table->text('brand_description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();

            // Color Scheme
            $table->string('primary_color')->default('#3b82f6');
            $table->string('secondary_color')->default('#64748b');
            $table->string('accent_color')->default('#f59e0b');
            $table->string('background_color')->default('#ffffff');
            $table->string('text_color')->default('#1f2937');

            // Typography
            $table->string('font_family')->default('Inter');
            $table->string('heading_font')->nullable();
            $table->decimal('font_scale', 3, 2)->default(1.00);

            // Layout & Style
            $table->enum('theme_mode', ['light', 'dark', 'auto'])->default('light');
            $table->string('border_radius')->default('8px');
            $table->json('custom_css')->nullable();

            // Contact & Social
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_links')->nullable();

            // Footer & Legal
            $table->text('footer_text')->nullable();
            $table->text('copyright_text')->nullable();
            $table->string('privacy_policy_url')->nullable();
            $table->string('terms_of_service_url')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_brands');
    }
};