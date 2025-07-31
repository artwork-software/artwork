<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Das :attribute-Feld muss akzeptiert werden.',
    'accepted_if' => 'Das :attribute-Feld muss akzeptiert werden, wenn :other :value ist.',
    'active_url' => 'Das :attribute-Feld muss eine gültige URL sein.',
    'after' => 'Das :attribute-Feld muss ein Datum nach :date sein.',
    'after_or_equal' => 'Das :attribute-Feld muss ein Datum nach oder gleich :date sein.',
    'alpha' => 'Das :attribute-Feld darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das Feld :attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num' => 'Das :attribute-Feld darf nur Buchstaben und Zahlen enthalten.',
    'array' => 'Das :attribute-Feld muss ein Array sein.',
    'ascii' => 'Das :attribute-Feld darf nur alphanumerische Einzelbyte-Zeichen und Symbole enthalten.',
    'before' => 'Das :attribute-Feld muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute-Feld muss ein Datum vor oder gleich :date sein.',
    'between' => [
    'array' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
        'file' => 'Das :attribute-Feld muss zwischen :min und :max kilobytes liegen.',
        'numeric' => 'Das :attribute-Feld muss zwischen :min und :max liegen.',
        'string' => 'Das :attribute-Feld muss zwischen :min und :max Zeichen haben.',
    ],
    'boolean' => 'Das :attribute-Feld muss true oder false sein.',
    'can' => 'Das :attribute-Feld enthält einen unzulässigen Wert.',
    'confirmed' => 'Das :attribute-Feld confirmation stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => 'Das :attribute-Feld muss ein gültiges Datum sein.',
    'date_equals' => 'Das :attribute-Feld muss ein Datum sein, das gleich dem :date ist.',
    'date_format' => 'Das :attribute-Feld muss dem Format :format entsprechen.',
    'decimal' => 'Das :attribute-Feld muss :decimal Nachkommastellen haben.',
    'declined' => 'Das :attribute-Feld muss abgelehnt werden.',
    'declined_if' => 'Das :attribute-Feld muss abgelehnt werden, wenn :other :value ist.',
    'different' => 'Das :attribute-Feld und :other müssen unterschiedlich sein.',
    'digits' => 'Das :attribute-Feld muss :digits Ziffern sein.',
    'digits_between' => 'Das :attribute-Feld muss zwischen :min und :max Ziffern liegen.',
    'dimensions' => 'Das :attribute-Feld hat ungültige Bildabmessungen.',
    'distinct' => 'Das :attribute-Feld hat einen doppelten Wert.',
    'doesnt_end_with' => 'Das :attribute-Feld darf nicht mit einem der folgenden enden: :values.',
    'doesnt_start_with' => 'Das :attribute-Feld darf nicht mit einem der folgenden beginnen: :values.',
    'email' => 'Das :attribute-Feld muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => 'Das :attribute-Feld muss mit einem der folgenden enden: :values.',
    'enum' => 'Das ausgewählte :attribute ist ungültig.',
    'exists' => 'Das gewählte :attribute ist ungültig.',
    'extensions' => 'Das :attribute-Feld muss eine der folgenden Erweiterungen haben: :values.',
    'file' => 'Das :attribute-Feld muss eine Datei sein.',
    'filled' => 'Das Feld :attribute muss einen Wert haben.',
    'gt' => [
        'array' => 'Das :attribute-Feld muss mehr als :value Elemente haben.',
        'file' => 'Das :attribute-Feld muss größer sein als :value kilobytes.',
        'numeric' => 'Das :attribute-Feld muss größer als :value sein.',
        'string' => 'Das Feld :attribute muss größer sein als :value Zeichen.',
    ],
    'gte' => [
        'array' => 'Das :attribute-Feld muss :value-Elemente oder mehr haben.',
        'file' => 'Das :attribute-Feld muss größer oder gleich dem :value kilobytes sein.',
        'numeric' => 'Das :attribute-Feld muss größer oder gleich :value sein.',
        'string' => 'Das Feld :attribute muss größer oder gleich :value Zeichen sein.',
    ],
    'hex_color' => 'Das :attribute-Feld muss eine gültige hexadezimale Farbe sein',
    'image' => 'Das :attribute-Feld muss ein Bild sein.',
    'in' => 'Das ausgewählte :attribute ist ungültig.',
    'in_array' => 'Das :attribute-Feld muss in :other vorhanden sein.',
    'integer' => 'Das :attribute-Feld muss eine ganze Zahl sein.',
    'ip' => 'Das :attribute-Feld muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das :attribute-Feld muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das :attribute-Feld muss eine gültige IPv6-Adresse sein.',
    'json' => 'Das :attribute-Feld muss ein gültiger JSON-String sein.',
    'lowercase' => 'Das :attribute-Feld muss klein geschrieben sein.',
    'lt' => [
    'array' => 'Das :attribute-Feld muss weniger als :value-Elemente haben.',
        'file' => 'Das :attribute-Feld muss weniger als :value kilobytes haben.',
        'numeric' => 'Das Feld :attribute muss kleiner sein als :value.',
        'string' => 'Das Feld :attribute muss kleiner sein als :value Zeichen.',
    ],
    'lte' => [
        'array' => 'Das :attribute-Feld darf nicht mehr als :value-Elemente haben.',
        'file' => 'Das :attribute field muss kleiner oder gleich dem :value kilobytes sein.',
        'numeric' => 'Das :attribute field muss kleiner oder gleich :value sein.',
        'string' => 'Das Feld :attribute muss kleiner oder gleich dem Wert :value Zeichen sein.',
    ],
    'mac_address' => 'Das :attribute-Feld muss eine gültige MAC-Adresse sein.',
    'max' => [
    'array' => 'Das :attribute-Feld darf nicht mehr als :max Elemente haben.',
        'file' => 'Das :attribute-Feld darf nicht größer sein als :max kilobytes.',
        'numeric' => 'Das :attribute-Feld darf nicht größer als :max sein.',
        'string' => 'Das :attribute-Feld darf nicht größer als :max Zeichen sein.',
    ],
    'max_digits' => 'Das :attribute-Feld darf nicht mehr als :max Ziffern haben.',
    'mimes' => 'Das :attribute-Feld muss eine Datei vom Typ :values sein.',
    'mimetypes' => 'Das :attribute-Feld muss eine Datei des Typs :values sein.',
    'min' => [
        'array' => 'Das :attribute-Feld muss mindestens :min items haben.',
        'file' => 'Das :attribute-Feld muss mindestens :min Kilobytes haben.',
        'numeric' => 'Das :attribute-Feld muss mindestens :min sein.',
        'string' => 'Das :attribute-Feld muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits' => 'Das :attribute-Feld muss mindestens :min Ziffern haben.',
    'missing' => 'Das :attribute-Feld muss fehlen.',
    'missing_if' => 'Das :attribute-Feld muss fehlen, wenn :other :value ist.',
    'missing_unless' => 'Das Feld :attribute muss fehlen, es sei denn :other ist :value.',
    'missing_with' => 'Das Feld :attribute muss fehlen, wenn :values vorhanden ist.',
    'missing_with_all' => 'Das :attribute-Feld muss fehlen, wenn :values vorhanden ist.',
    'multiple_of' => 'Das :attribute-Feld muss ein Vielfaches von :value sein.',
    'not_in' => 'Das ausgewählte :attribute ist ungültig.',
    'not_regex' => 'Das :attribute-Feldformat ist ungültig.',
    'numeric' => 'Das :attribute-Feld muss eine Zahl sein.',
    'passwort' => [
        'letters' => 'Das :attribute field muss mindestens einen Buchstaben enthalten.',
        'mixed' => 'Das :attribute-Feld muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
            'numbers' => 'Das :attribute-Feld muss mindestens eine Zahl enthalten.',
            'symbols' => 'Das :attribute-Feld muss mindestens ein Symbol enthalten.',
            'uncompromised' => 'Das angegebene :attribute ist in einem Datenleck aufgetaucht. Bitte wählen Sie ein anderes :Attribut.',
    ],
    'present' => 'Das :attribute-Feld muss vorhanden sein.',
    'present_if' => 'Das :attribute-Feld muss vorhanden sein, wenn :other :value ist.',
    'present_unless' => 'Das :attribute-Feld muss vorhanden sein, es sei denn :other ist :value.',
    'present_with' => 'Das :attribute-Feld muss vorhanden sein, wenn :values vorhanden ist.',
    'present_with_all' => 'Das :attribute-Feld muss vorhanden sein, wenn :values vorhanden ist.',
    'prohibited' => 'Das :attribute-Feld ist verboten.',
    'prohibited_if' => 'Das :attribute field ist verboten, wenn :other :value ist.',
    'prohibited_unless' => 'Das :attribute-Feld ist verboten, wenn :other nicht in :values enthalten ist.',
    'prohibits' => 'Das :attribute-Feld verbietet das Vorhandensein von :other.',
    'regex' => 'Das Format des :attribute-Feld ist ungültig.',
    'required' => 'Das :attribute-Feld ist erforderlich.',
    'required_array_keys' => 'Das :attribute-Feld muss Einträge enthalten für: :values.',
    'required_if' => 'Das :attribute-Feld ist erforderlich, wenn :other :value ist.',
    'required_if_accepted' => 'Das :attribute-Feld ist erforderlich, wenn :other akzeptiert wird.',
    'required_unless' => 'Das :attribute-Feld ist erforderlich, wenn :other nicht in :values steht.',
    'required_with' => 'Das :attribute-Feld ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => 'Das :attribute-Feld ist erforderlich, wenn :values vorhanden ist.',
    'required_without' => 'Das Feld :attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => 'Das Feld :attribute ist erforderlich, wenn keines von :values vorhanden ist.',
    'same' => 'Das :attribute-Feld muss mit :other übereinstimmen.',
    'size' => [
        'array' => 'Das :attribute-Feld muss :size-Elemente enthalten.',
        'file' => 'Das :attribute field muss :size kilobytes sein.',
        'numeric' => 'Das :attribute field muss :size sein.',
        'string' => 'Das :attribute-Feld muss :size Zeichen haben.',
    ],
    'starts_with' => 'Das :attribute field muss mit einem der folgenden beginnen: :values.',
    'string' => 'Das :attribute-Feld muss ein String sein.',
    'timezone' => 'Das :attribute-Feld muss eine gültige Zeitzone sein.',
    'unique' => 'Das :attribute wurde bereits vergeben.',
    'uploaded' => 'Das :attribute konnte nicht hochgeladen werden.',
    'uppercase' => 'Das :attribute-Feld muss in Großbuchstaben geschrieben sein.',
    'url' => 'Das :attribute-Feld muss eine gültige URL sein.',
    'ulid' => 'Das :attribute-Feld muss eine gültige ULID sein.',
    'uuid' => 'Das :attribute-Feld muss eine gültige UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'eventName' => [
            'required_if' => 'Der Veranstaltungsname ist erforderlich, wenn das Feld als Pflichtfeld markiert ist.',
        ],
        'projectId' => [
            'required_if' => 'Ein Projekt muss zugewiesen werden, wenn der Event-Typ es verlangt.',
        ],
        'projectName' => [
            'required_unless' => 'Der Projektname ist erforderlich, außer "Projekt wird erstellt" ist deaktiviert.',
        ],
        'start' => [
            'required' => 'Das Startdatum ist erforderlich.',
            'date'     => 'Das Startdatum muss ein gültiges Datum sein.',
        ],
        'end' => [
            'required' => 'Das Enddatum ist erforderlich.',
            'date'     => 'Das Enddatum muss ein gültiges Datum sein.',
            'after'    => 'Das Enddatum muss nach dem Startdatum liegen.',
        ],
        'roomId' => [
            'exists' => 'Der ausgewählte Raum existiert nicht.',
        ],
        'declinedRoomId' => [
            'exists' => 'Der abgelehnte Raum existiert nicht.',
        ],
        'eventTypeId' => [
            'required' => 'Ein Veranstaltungstyp ist erforderlich.',
            'exists'   => 'Der ausgewählte Veranstaltungstyp ist ungültig.',
        ],
        'eventStatusId' => [
            'exists' => 'Der ausgewählte Veranstaltungsstatus ist ungültig.',
        ],
        'projectIdMandatory' => [
            'required' => 'Die Projektzuweisung muss angegeben werden, wenn der Event-Typ dies verlangt.',
        ],
        'eventNameMandatory' => [
            'required' => 'Die Verpflichtung zum Veranstaltungsnamen muss angegeben werden, wenn der Event-Typ dies verlangt.',
        ],
        'creatingProject' => [
            'required' => 'Es muss angegeben werden, ob ein neues Projekt erstellt wird.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'address' => 'address',
        'affiliate_url' => 'affiliate URL',
        'age' => 'age',
        'amount' => 'amount',
        'area' => 'area',
        'available' => 'available',
        'birthday' => 'birthday',
        'body' => 'body',
        'city' => 'city',
        'content' => 'content',
        'country' => 'country',
        'created_at' => 'created at',
        'creator' => 'creator',
        'currency' => 'currency',
        'current_password' => 'current password',
        'customer' => 'customer',
        'date' => 'date',
        'date_of_birth' => 'date of birth',
        'day' => 'day',
        'deleted_at' => 'deleted at',
        'description' => 'description',
        'district' => 'district',
        'duration' => 'duration',
        'email' => 'email',
        'excerpt' => 'excerpt',
        'filter' => 'filter',
        'first_name' => 'first name',
        'gender' => 'gender',
        'group' => 'group',
        'hour' => 'hour',
        'image' => 'image',
        'is_subscribed' => 'is subscribed',
        'items' => 'items',
        'last_name' => 'last name',
        'lesson' => 'lesson',
        'line_address_1' => 'line address 1',
        'line_address_2' => 'line address 2',
        'message' => 'message',
        'middle_name' => 'middle name',
        'minute' => 'minute',
        'mobile' => 'mobile',
        'month' => 'month',
        'name' => 'name',
        'national_code' => 'national code',
        'number' => 'number',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'phone' => 'phone',
        'photo' => 'photo',
        'postal_code' => 'postal code',
        'preview' => 'preview',
        'price' => 'price',
        'product_id' => 'product ID',
        'product_uid' => 'product UID',
        'product_uuid' => 'product UUID',
        'promo_code' => 'promo code',
        'province' => 'province',
        'quantity' => 'quantity',
        'recaptcha_response_field' => 'recaptcha response field',
        'remember' => 'remember',
        'restored_at' => 'restored at',
        'result_text_under_image' => 'result text under image',
        'role' => 'role',
        'second' => 'second',
        'sex' => 'sex',
        'shipment' => 'shipment',
        'short_text' => 'short text',
        'size' => 'size',
        'state' => 'state',
        'street' => 'street',
        'student' => 'student',
        'subject' => 'subject',
        'teacher' => 'teacher',
        'terms' => 'terms',
        'test_description' => 'test description',
        'test_locale' => 'test locale',
        'test_name' => 'test name',
        'text' => 'text',
        'time' => 'time',
        'title' => 'title',
        'updated_at' => 'updated at',
        'user' => 'user',
        'username' => 'username',
        'year' => 'year',
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
    'file_upload' => [
        'max_size' => 'Die maximal freigegebene Dateigröße ist aktuell :size MB, lasse es entweder von einem Admin hochsetzen oder versuche es mit einer kleineren Datei',
        'invalid_file_type' => 'Dieses Dateiformat :format ist nicht freigegeben, bitte einen Admin es freizugeben oder nutze ein anderes Format',
    ],
    'timeline' => [
        'name_required' => 'Der Name des Zeitplans ist erforderlich.',
        'name_string' => 'Der Name muss eine Zeichenkette sein.',
        'name_max' => 'Der Name darf maximal 255 Zeichen enthalten.',
        'dataset_required' => 'Das Datenset ist erforderlich.',
        'dataset_array' => 'Das Datenset muss ein Array sein.',
        'start_required' => 'Die Startzeit ist erforderlich.',
        'start_date_format' => 'Die Startzeit muss im Format HH:MM angegeben werden.',
        'end_required' => 'Die Endzeit ist erforderlich.',
        'end_date_format' => 'Die Endzeit muss im Format HH:MM angegeben werden.',
        'end_after_start' => 'Die Endzeit muss nach der Startzeit liegen.',
        'description_string' => 'Die Beschreibung muss eine Zeichenkette sein.',
        'description_max' => 'Die Beschreibung darf maximal 500 Zeichen enthalten.',
    ],
];
