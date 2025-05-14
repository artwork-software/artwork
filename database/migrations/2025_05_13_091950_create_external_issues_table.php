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
        Schema::create('external_issues', function (Blueprint $table) {
            $table->id();
            $table->decimal('material_value', 10, 2);
            $table->foreignId('issued_by_id')->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('issue_date');
            $table->date('return_date');
            $table->text('return_remarks')->nullable();

            $table->string('external_name');
            $table->string('external_address')->nullable();
            $table->string('external_email')->nullable();
            $table->string('external_phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_issues');
    }
};
