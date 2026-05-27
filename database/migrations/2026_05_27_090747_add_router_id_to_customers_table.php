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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('router_id')->nullable()->after('package_id')->constrained('routers')->onDelete('set null');
            $table->foreignId('coverage_area_id')->nullable()->after('router_id')->constrained('coverage_areas')->onDelete('set null');

            // Network assignment tracking
            $table->timestamp('router_assigned_at')->nullable()->after('coverage_area_id');
            $table->string('pppoe_username', 100)->nullable()->after('router_assigned_at');
            $table->string('pppoe_password', 100)->nullable()->after('pppoe_username');

            // Indexes
            $table->index('router_id');
            $table->index('coverage_area_id');
            $table->index('pppoe_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['router_id']);
            $table->dropForeign(['coverage_area_id']);
            $table->dropIndex(['router_id']);
            $table->dropIndex(['coverage_area_id']);
            $table->dropIndex(['pppoe_username']);
            $table->dropColumn([
                'router_id',
                'coverage_area_id',
                'router_assigned_at',
                'pppoe_username',
                'pppoe_password'
            ]);
        });
    }
};
