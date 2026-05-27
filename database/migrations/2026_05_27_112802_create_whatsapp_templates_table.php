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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 100)->unique();
            $table->text('message');
            $table->boolean('is_active')->default(true);
            $table->json('variables')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index('is_active');
        });

        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->onDelete('set null');
            $table->string('phone', 20);
            $table->text('message');
            $table->string('template_type', 100)->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending');
            $table->string('provider', 50)->default('fonnte');
            $table->string('message_id')->nullable();
            $table->text('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('template_id');
            $table->index('phone');
            $table->index('status');
            $table->index('template_type');
            $table->index('sent_at');
            $table->index('created_at');
        });

        Schema::create('whatsapp_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('provider', 50)->unique();
            $table->string('api_key')->nullable();
            $table->string('api_url')->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('config')->nullable();
            $table->integer('daily_limit')->default(1000);
            $table->integer('daily_sent')->default(0);
            $table->date('last_reset_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('provider');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_providers');
    }
};
