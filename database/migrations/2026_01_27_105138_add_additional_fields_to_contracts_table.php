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
        Schema::table('contracts', function (Blueprint $table) {
            // KSK fields - amount and reason
            $table->decimal('ksk_amount', 10, 2)->nullable()->after('ksk_liable');
            $table->text('ksk_reason')->nullable()->after('ksk_amount');

            // Foreign tax fields (Auslandsteuer)
            $table->boolean('foreign_tax')->default(false)->after('resident_abroad');
            $table->decimal('foreign_tax_amount', 10, 2)->nullable()->after('foreign_tax');
            $table->text('foreign_tax_reason')->nullable()->after('foreign_tax_amount');

            // Reverse Charge amount
            $table->decimal('reverse_charge_amount', 10, 2)->nullable()->after('foreign_tax_reason');

            // Deadline date (Stichtag) - inherited from project or specified
            $table->date('deadline_date')->nullable()->after('reverse_charge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'ksk_amount',
                'ksk_reason',
                'foreign_tax',
                'foreign_tax_amount',
                'foreign_tax_reason',
                'reverse_charge_amount',
                'deadline_date',
            ]);
        });
    }
};
