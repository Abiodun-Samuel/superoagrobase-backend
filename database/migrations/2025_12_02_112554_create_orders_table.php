<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique()->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->json('delivery_details');
            $table->enum('delivery_method', ['pickup', 'waybill'])->default('pickup')->index();

            $table->enum('payment_method', ['card', 'online', 'bank_account', 'bank_transfer', 'ussd', 'wallet', 'pos', 'cash_on_delivery', 'later'])->default('later')->index();

            $table->enum('payment_status', PaymentStatus::values())->default(PaymentStatus::PENDING->value)->index();
            $table->enum('status', OrderStatus::values())->default(OrderStatus::PENDING->value)->index();

            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
