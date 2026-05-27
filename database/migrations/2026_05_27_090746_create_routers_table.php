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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('identity', 100)->unique();
            $table->string('host', 50);
            $table->integer('port')->default(8728);
            $table->string('username', 50);
            $table->string('password');
            $table->string('region', 100)->nullable();
            $table->string('location', 200)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Capacity & Status
            $table->integer('max_capacity')->default(1000);
            $table->integer('current_users')->default(0);
            $table->enum('status', ['active', 'inactive', 'maintenance', 'error'])->default('active');
            $table->timestamp('last_check')->nullable();
            $table->text('last_error')->nullable();

            // RADIUS Configuration
            $table->string('radius_secret', 100)->default('testing123');
            $table->boolean('use_radius')->default(true);

            // Priority & Load Balancing
            $table->integer('priority')->default(1);
            $table->boolean('auto_assign')->default(true);

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('region');
            $table->index('status');
            $table->index(['status', 'auto_assign']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
