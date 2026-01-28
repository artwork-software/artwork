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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();

            // User who creates the request
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');

            // User who should fulfill the request
            $table->foreignId('requested_id')->constrained('users')->onDelete('cascade');

            // Optional project association
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');

            // Link to uploaded contract when fulfilled
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');

            // Request details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');

            // Pre-filled metadata for the document
            $table->string('contract_partner')->nullable();
            $table->decimal('contract_value', 10, 2)->nullable();

            // KSK fields
            $table->boolean('ksk_liable')->default(false);
            $table->decimal('ksk_amount', 10, 2)->nullable();
            $table->text('ksk_reason')->nullable();

            // Foreign tax fields (Auslandsteuer)
            $table->boolean('foreign_tax')->default(false);
            $table->decimal('foreign_tax_amount', 10, 2)->nullable();
            $table->text('foreign_tax_reason')->nullable();

            // Reverse Charge
            $table->decimal('reverse_charge_amount', 10, 2)->nullable();

            // Deadline date (Stichtag)
            $table->date('deadline_date')->nullable();

            // Contract type and company type references
            $table->foreignId('contract_type_id')->nullable()->constrained('contract_types')->onDelete('set null');
            $table->foreignId('company_type_id')->nullable()->constrained('company_types')->onDelete('set null');

            // Additional comment
            $table->text('comment')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
