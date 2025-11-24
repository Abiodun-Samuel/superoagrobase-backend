<?php

use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Basic identity
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();

            // Authentication
            $table->string('password')->nullable();
            $table->enum('auth_provider', ['google', 'facebook', 'apple', 'local']);
            $table->timestamp('email_verified_at')->nullable();

            // Profile
            $table->string('avatar')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable()->index();

            // Status (using your Status enum)
            $table->enum('status', Status::values())->index()->default(Status::ACTIVE->value);

            // Location — useful for checkout & personalization
            $table->string('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable()->index();

            // E-commerce specific
            $table->boolean('is_marketing_subscribed')->default(false);
            $table->timestamp('last_login_at')->nullable()->index();

            // Vendor fields (support marketplace model)
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_website')->nullable();

            // Billing & shipping (stores pre-filled values)
            $table->json('billing_details')->nullable();
            $table->json('shipping_details')->nullable();

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
