<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_movements', function (Blueprint $table) {
            $table->string('supplier_name')->nullable();
            $table->string('supplier_tax_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('source')->nullable();
            $table->string('support_path')->nullable();
            $table->string('support_name')->nullable();
            $table->string('support_mime_type')->nullable();
        });
    }
    public function down(): void { Schema::table('financial_movements', fn (Blueprint $table) => $table->dropColumn(['supplier_name', 'supplier_tax_id', 'invoice_number', 'subtotal', 'tax_amount', 'discount_amount', 'currency', 'source', 'support_path', 'support_name', 'support_mime_type'])); }
};
