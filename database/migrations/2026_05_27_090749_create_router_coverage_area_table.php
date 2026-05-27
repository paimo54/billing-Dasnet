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
        Schema::create('router_coverage_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->foreignId('coverage_area_id')->constrained('coverage_areas')->onDelete('cascade');

            // Priority for this router in this coverage area
            $table->integer('priority')->default(1);
            $table->boolean('is_primary')->default(false);

            // Signal strength estimation
            $table->enum('signal_strength', ['excellent', 'good', 'fair', 'poor'])->default('good');

            $table->timestamps();

            // Unique constraint
            $table->unique(['router_id', 'coverage_area_id']);

            // Indexes
            $table->index('priority');
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_coverage_area');
    }
};
