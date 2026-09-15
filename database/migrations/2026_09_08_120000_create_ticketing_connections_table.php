<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticketing_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('tickets_url');
            $table->string('organization_id');
            $table->string('organization_slug');
            $table->string('dashboard_url');
            // Verschlüsselt über den Model-Cast; Zugriffe laufen über das Model.
            $table->text('api_key');
            // Passport-Client (client_credentials), mit dem tickets sich hier Tokens holt. String, weil
            // oauth_clients je nach Installation numerische oder UUID-Schlüssel trägt.
            $table->string('oauth_client_id', 36);
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_connections');
    }
};
