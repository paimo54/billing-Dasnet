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
        Schema::create('coverage_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('region', 100);
            $table->text('description')->nullable();

            // Geographic Data
            $table->json('polygon_coordinates')->nullable(); // GeoJSON polygon
            $table->decimal('center_latitude', 10, 8)->nullable();
            $table->decimal('center_longitude', 11, 8)->nullable();
            $table->integer('radius_meters')->nullable(); // For circular coverage

            // Service Availability
            $table->boolean('is_active')->default(true);
            $table->date('service_start_date')->nullable();
            $table->integer('estimated_capacity')->nullable();
            $table->integer('current_subscribers')->default(0);

            // Coverage Quality
            $table->enum('signal_quality', ['excellent', 'good', 'fair', 'poor'])->default('good');
            $table->text('coverage_notes')->nullable();

            // Display Settings
            $table->string('color_hex', 7)->default('#3498db'); // Map polygon color
            $table->integer('display_order')->default(0);
            $table->boolean('show_on_map')->default(true);

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('region');
            $table->index('is_active');
            $table->index(['is_active', 'show_on_map']);
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coverage_areas');
    }
};
