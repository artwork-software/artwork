<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * notifications.data->created_by enthielt das komplette User-Modell der handelnden Person
 * (E-Mail, Telefon, Stundenkonto, Login-IDs …) und ging so an alle Empfänger*innen. Bestand
 * auf das reduzieren, was die Kopfzeile braucht (wie NotificationService::creatorSummary).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "UPDATE notifications
             SET data = JSON_SET(data, '$.created_by', JSON_OBJECT(
                 'id', JSON_EXTRACT(data, '$.created_by.id'),
                 'first_name', JSON_EXTRACT(data, '$.created_by.first_name'),
                 'last_name', JSON_EXTRACT(data, '$.created_by.last_name'),
                 'profile_photo_url', JSON_EXTRACT(data, '$.created_by.profile_photo_url')
             ))
             WHERE JSON_TYPE(JSON_EXTRACT(data, '$.created_by')) = 'OBJECT'
               AND JSON_LENGTH(JSON_EXTRACT(data, '$.created_by')) > 4"
        );
    }

    public function down(): void
    {
        // bewusst nicht umkehrbar: die entfernten Felder sollen nicht zurück
    }
};
