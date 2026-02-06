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
        Schema::create('vendor_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number');

            // Company Information
            $table->string('company_name');
            $table->string('company_email');
            $table->string('company_phone');
            $table->text('company_address');
            $table->string('company_website')->nullable();

            // Request Status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->longText('rejection_reason')->nullable();

            // Review Information
            $table->timestamp('reviewed_at')->nullable();

            // Related User (created after approval)
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('email');
            $table->index('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_requests');
    }
};
