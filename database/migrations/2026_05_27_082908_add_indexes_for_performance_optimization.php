<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes for performance optimization on frequently queried columns
     * This is critical for handling thousands of customers efficiently
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Index untuk filter by package
            $table->index('package_id', 'idx_customers_package_id');

            // Index untuk filter by creator/technician
            $table->index('created_by', 'idx_customers_created_by');

            // Index untuk filter active/inactive customers
            $table->index('is_active', 'idx_customers_is_active');

            // Index untuk billing date (untuk generate invoice)
            $table->index('billing_date', 'idx_customers_billing_date');

            // Composite index untuk query yang sering digunakan
            $table->index(['created_by', 'is_active'], 'idx_customers_creator_active');
            $table->index(['package_id', 'is_active'], 'idx_customers_package_active');
        });

        Schema::table('invoices', function (Blueprint $table) {
            // Index untuk filter by customer
            $table->index('customer_id', 'idx_invoices_customer_id');

            // Index untuk filter by package
            $table->index('package_id', 'idx_invoices_package_id');

            // Index untuk filter by status (paid/unpaid)
            $table->index('status', 'idx_invoices_status');

            // Index untuk filter by invoice date
            $table->index('invoice_date', 'idx_invoices_invoice_date');

            // Index untuk filter by due date (untuk reminder)
            $table->index('due_date', 'idx_invoices_due_date');

            // Index untuk filter by creator
            $table->index('created_by', 'idx_invoices_created_by');

            // Composite index untuk query laporan keuangan
            $table->index(['invoice_date', 'status'], 'idx_invoices_date_status');
            $table->index(['created_by', 'status'], 'idx_invoices_creator_status');
            $table->index(['customer_id', 'status'], 'idx_invoices_customer_status');

            // Index untuk print status tracking
            $table->index('is_printed_admin', 'idx_invoices_printed_admin');
            $table->index('is_printed_technician', 'idx_invoices_printed_technician');
        });

        Schema::table('packages', function (Blueprint $table) {
            // Index untuk filter active packages
            $table->index('is_active', 'idx_packages_is_active');

            // Index untuk filter by type
            $table->index('type', 'idx_packages_type');

            // Composite index
            $table->index(['type', 'is_active'], 'idx_packages_type_active');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index untuk filter by role
            $table->index('role_id', 'idx_users_role_id');

            // Index untuk email lookup (jika belum ada unique index)
            if (!Schema::hasColumn('users', 'email_index')) {
                $table->index('email', 'idx_users_email');
            }
        });

        Schema::table('mitra_reports', function (Blueprint $table) {
            // Index untuk filter by technician
            $table->index('technician_id', 'idx_mitra_reports_technician_id');

            // Index untuk periode
            $table->index('periode_awal', 'idx_mitra_reports_periode_awal');
            $table->index('periode_akhir', 'idx_mitra_reports_periode_akhir');

            // Index untuk payment status (is_paid)
            $table->index('is_paid', 'idx_mitra_reports_is_paid');

            // Composite index untuk query laporan
            $table->index(['technician_id', 'periode_awal'], 'idx_mitra_reports_tech_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_package_id');
            $table->dropIndex('idx_customers_created_by');
            $table->dropIndex('idx_customers_is_active');
            $table->dropIndex('idx_customers_billing_date');
            $table->dropIndex('idx_customers_creator_active');
            $table->dropIndex('idx_customers_package_active');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_customer_id');
            $table->dropIndex('idx_invoices_package_id');
            $table->dropIndex('idx_invoices_status');
            $table->dropIndex('idx_invoices_invoice_date');
            $table->dropIndex('idx_invoices_due_date');
            $table->dropIndex('idx_invoices_created_by');
            $table->dropIndex('idx_invoices_date_status');
            $table->dropIndex('idx_invoices_creator_status');
            $table->dropIndex('idx_invoices_customer_status');
            $table->dropIndex('idx_invoices_printed_admin');
            $table->dropIndex('idx_invoices_printed_technician');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex('idx_packages_is_active');
            $table->dropIndex('idx_packages_type');
            $table->dropIndex('idx_packages_type_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_id');
            if (Schema::hasColumn('users', 'email')) {
                $table->dropIndex('idx_users_email');
            }
        });

        Schema::table('mitra_reports', function (Blueprint $table) {
            $table->dropIndex('idx_mitra_reports_technician_id');
            $table->dropIndex('idx_mitra_reports_periode_awal');
            $table->dropIndex('idx_mitra_reports_periode_akhir');
            $table->dropIndex('idx_mitra_reports_is_paid');
            $table->dropIndex('idx_mitra_reports_tech_periode');
        });
    }
};
