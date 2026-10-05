<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            if (!Schema::hasColumn('invitations', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('roles');
            }
        });

        // Altbestand: Ablauf ab dem letzten (Neu-)Versand nachziehen.
        foreach (DB::table('invitations')->whereNull('expires_at')->get(['id', 'created_at', 'updated_at']) as $row) {
            $sentAt = $row->updated_at ?? $row->created_at;
            DB::table('invitations')->where('id', $row->id)->update([
                'expires_at' => ($sentAt ? Carbon::parse($sentAt) : Carbon::now())->addDays(7),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            if (Schema::hasColumn('invitations', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
