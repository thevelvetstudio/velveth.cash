<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('justification')->nullable();
            $table->date('expected_at')->nullable();
            $table->string('priority')->default('Media');
            $table->string('status')->default('Borrador');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_projection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_projection_id')->constrained()->cascadeOnDelete();
            $table->string('item');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_projection_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_projection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_projection_attachments');
        Schema::dropIfExists('financial_projection_items');
        Schema::dropIfExists('financial_projections');
    }
};
