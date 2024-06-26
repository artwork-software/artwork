<?php

declare(strict_types=1);

return [
    'attributes' => [
        'address'                  => 'Adresse',
        'affiliate_url'            => 'Affiliate-URL',
        'age'                      => 'Alter',
        'amount'                   => 'Höhe',
        'area'                     => 'Gebiet',
        'available'                => 'Verfügbar',
        'birthday'                 => 'Geburtstag',
        'body'                     => 'Körper',
        'city'                     => 'Stadt',
        'content'                  => 'Inhalt',
        'country'                  => 'Land',
        'created_at'               => 'Erstellt am',
        'creator'                  => 'Ersteller',
        'currency'                 => 'Währung',
        'current_password'         => 'Derzeitiges Passwort',
        'customer'                 => 'Kunde',
        'date'                     => 'Datum',
        'date_of_birth'            => 'Geburtsdatum',
        'day'                      => 'Tag',
        'deleted_at'               => 'Gelöscht am',
        'description'              => 'Beschreibung',
        'district'                 => 'Bezirk',
        'duration'                 => 'Dauer',
        'email'                    => 'E-Mail-Adresse',
        'excerpt'                  => 'Auszug',
        'filter'                   => 'Filter',
        'first_name'               => 'Vorname',
        'gender'                   => 'Geschlecht',
        'group'                    => 'Gruppe',
        'hour'                     => 'Stunde',
        'image'                    => 'Bild',
        'is_subscribed'            => 'ist abonniert',
        'items'                    => 'Artikel',
        'last_name'                => 'Nachname',
        'lesson'                   => 'Lektion',
        'line_address_1'           => 'Adresszeile 1',
        'line_address_2'           => 'Adresszeile 2',
        'message'                  => 'Nachricht',
        'middle_name'              => 'Zweitname',
        'minute'                   => 'Minute',
        'mobile'                   => 'Handynummer',
        'month'                    => 'Monat',
        'name'                     => 'Name',
        'national_code'            => 'Länderkennung',
        'number'                   => 'Nummer',
        'password'                 => 'Passwort',
        'password_confirmation'    => 'Passwortbestätigung',
        'phone'                    => 'Telefonnummer',
        'photo'                    => 'Foto',
        'postal_code'              => 'Postleitzahl',
        'preview'                  => 'Vorschau',
        'price'                    => 'Preis',
        'product_id'               => 'Produkt ID',
        'product_uid'              => 'Produkt-UID',
        'product_uuid'             => 'Produkt-UUID',
        'promo_code'               => 'Aktionscode',
        'province'                 => 'Provinz',
        'quantity'                 => 'Menge',
        'recaptcha_response_field' => 'Captcha-Feld',
        'remember'                 => 'Erinnern',
        'restored_at'              => 'Wiederhergestellt am',
        'result_text_under_image'  => 'Ergebnistext unter Bild',
        'role'                     => 'Rolle',
        'second'                   => 'Sekunde',
        'sex'                      => 'Geschlecht',
        'shipment'                 => 'Sendung',
        'short_text'               => 'Kurzer Text',
        'size'                     => 'Größe',
        'state'                    => 'Bundesland',
        'street'                   => 'Straße',
        'student'                  => 'Schüler/Student',
        'subject'                  => 'Gegenstand',
        'teacher'                  => 'Lehrer',
        'terms'                    => 'Bedingungen',
        'test_description'         => 'Test Beschreibung',
        'test_locale'              => 'Test Region',
        'test_name'                => 'Testname',
        'text'                     => 'Text',
        'time'                     => 'Uhrzeit',
        'title'                    => 'Titel',
        'updated_at'               => 'Aktualisiert am',
        'user'                     => 'Benutzer',
        'username'                 => 'Benutzername',
        'year'                     => 'Jahr',
        'user_emails.*'              => '',
    ],
    'invitations' => [
        'user_emails' => [
            'required' => 'Das Benutzer-E-Mail-Feld ist erforderlich.',
            'array' => 'Das Benutzer-E-Mail-Feld muss ein Array sein.',
            'email' => 'Das Benutzer-E-Mail-Feld muss gültige E-Mail-Adressen enthalten.',
            'unique' => 'Die Benutzer-E-Mail ":user" existiert bereits im System.',
        ],
        'permissions' => [
            'array' => 'Das Berechtigungsfeld muss ein Array sein.',
        ],
        'role' => [
            'sometimes' => 'Das Rollenfeld ist nicht erforderlich.',
        ],
    ],
];
