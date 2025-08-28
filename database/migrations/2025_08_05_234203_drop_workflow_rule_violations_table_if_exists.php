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
        // Drop workflow_rule_violations table if it exists
        Schema::dropIfExists('workflow_rule_violations');
        
        // Drop any other workflow-related violation tables that might exist
        Schema::dropIfExists('workflow_violations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't recreate the old tables in down() since we're moving to the new ShiftRule system
        // The old table structure can be found in the original migration if needed for reference
    }
};
