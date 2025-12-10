<?php

use App\Enums\PaymentStatus;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('reference')->unique();
            $table->string('transaction_reference')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::PENDING->value)->index();
            $table->string('channel')->nullable(); // card, bank, ussd, qr, bank_transfer
            $table->string('currency', 3)->default('NGN');
            $table->json('metadata')->nullable();
            $table->text('transaction_response')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['reference', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
