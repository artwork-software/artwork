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

    'accepted' => ':attribute moet worden geaccepteerd.',
    'accepted_if' => ':attribute moet worden geaccepteerd wanneer :other gelijk is aan :value.',
    'active_url' => ':attribute moet een geldige URL zijn.',
    'after' => ':attribute moet een datum na :date zijn.',
    'after_or_equal' => ':attribute moet een datum op of na :date zijn.',
    'alpha' => ':attribute mag alleen letters bevatten.',
    'alpha_dash' => ':attribute mag alleen letters, cijfers, streepjes en underscores bevatten.',
    'alpha_num' => ':attribute mag alleen letters en cijfers bevatten.',
    'array' => ':attribute moet een lijst zijn.',
    'ascii' => ':attribute mag alleen ASCII-letters, cijfers en symbolen bevatten.',
    'before' => ':attribute moet een datum vóór :date zijn.',
    'before_or_equal' => ':attribute moet een datum op of vóór :date zijn.',
    'between' => [
        'array' => ':attribute moet tussen :min en :max items bevatten.',
        'file' => ':attribute moet tussen :min en :max kilobyte groot zijn.',
        'numeric' => ':attribute moet tussen :min en :max liggen.',
        'string' => ':attribute moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => ':attribute moet waar of onwaar zijn.',
    'can' => ':attribute bevat een niet-toegestane waarde.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => ':attribute moet een geldige datum zijn.',
    'date_equals' => ':attribute moet gelijk zijn aan :date.',
    'date_format' => ':attribute moet het formaat :format hebben.',
    'decimal' => ':attribute moet :decimal decimalen hebben.',
    'declined' => ':attribute moet worden geweigerd.',
    'declined_if' => ':attribute moet worden geweigerd wanneer :other gelijk is aan :value.',
    'different' => ':attribute en :other moeten verschillend zijn.',
    'digits' => ':attribute moet uit :digits cijfers bestaan.',
    'digits_between' => ':attribute moet tussen :min en :max cijfers bevatten.',
    'dimensions' => 'De afmetingen van de afbeelding bij :attribute zijn ongeldig.',
    'distinct' => ':attribute bevat een dubbele waarde.',
    'doesnt_end_with' => ':attribute mag niet eindigen op een van de volgende waarden: :values.',
    'doesnt_start_with' => ':attribute mag niet beginnen met een van de volgende waarden: :values.',
    'email' => ':attribute moet een geldig e-mailadres zijn.',
    'ends_with' => ':attribute moet eindigen op een van de volgende waarden: :values.',
    'enum' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'exists' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'extensions' => ':attribute moet een van de volgende extensies hebben: :values.',
    'file' => ':attribute moet een bestand zijn.',
    'filled' => ':attribute mag niet leeg zijn.',
    'gt' => [
        'array' => ':attribute moet meer dan :value items bevatten.',
        'file' => ':attribute moet groter zijn dan :value kilobyte.',
        'numeric' => ':attribute moet groter zijn dan :value.',
        'string' => ':attribute moet meer dan :value tekens bevatten.',
    ],
    'gte' => [
        'array' => ':attribute moet minimaal :value items bevatten.',
        'file' => ':attribute moet minimaal :value kilobyte groot zijn.',
        'numeric' => ':attribute moet minimaal :value zijn.',
        'string' => ':attribute moet minimaal :value tekens bevatten.',
    ],
    'hex_color' => ':attribute moet een geldige hexadecimale kleur zijn.',
    'image' => ':attribute moet een afbeelding zijn.',
    'in' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'in_array' => ':attribute moet voorkomen in :other.',
    'integer' => ':attribute moet een geheel getal zijn.',
    'ip' => ':attribute moet een geldig IP-adres zijn.',
    'ipv4' => ':attribute moet een geldig IPv4-adres zijn.',
    'ipv6' => ':attribute moet een geldig IPv6-adres zijn.',
    'json' => ':attribute moet een geldige JSON-tekst zijn.',
    'lowercase' => ':attribute mag alleen kleine letters bevatten.',
    'lt' => [
        'array' => ':attribute moet minder dan :value items bevatten.',
        'file' => ':attribute moet kleiner zijn dan :value kilobyte.',
        'numeric' => ':attribute moet kleiner zijn dan :value.',
        'string' => ':attribute moet minder dan :value tekens bevatten.',
    ],
    'lte' => [
        'array' => ':attribute mag maximaal :value items bevatten.',
        'file' => ':attribute mag maximaal :value kilobyte groot zijn.',
        'numeric' => ':attribute mag maximaal :value zijn.',
        'string' => ':attribute mag maximaal :value tekens bevatten.',
    ],
    'mac_address' => ':attribute moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => ':attribute mag maximaal :max items bevatten.',
        'file' => ':attribute mag niet groter zijn dan :max kilobyte.',
        'numeric' => ':attribute mag niet groter zijn dan :max.',
        'string' => ':attribute mag maximaal :max tekens bevatten.',
    ],
    'max_digits' => ':attribute mag maximaal :max cijfers bevatten.',
    'mimes' => ':attribute moet een bestand zijn van het type: :values.',
    'mimetypes' => ':attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'array' => ':attribute moet minimaal :min items bevatten.',
        'file' => ':attribute moet minimaal :min kilobyte groot zijn.',
        'numeric' => ':attribute moet minimaal :min zijn.',
        'string' => ':attribute moet minimaal :min tekens bevatten.',
    ],
    'min_digits' => ':attribute moet minimaal :min cijfers bevatten.',
    'missing' => ':attribute mag niet aanwezig zijn.',
    'missing_if' => ':attribute mag niet aanwezig zijn wanneer :other gelijk is aan :value.',
    'missing_unless' => ':attribute mag alleen aanwezig zijn wanneer :other gelijk is aan :value.',
    'missing_with' => ':attribute mag niet aanwezig zijn wanneer :values aanwezig is.',
    'missing_with_all' => ':attribute mag niet aanwezig zijn wanneer :values aanwezig zijn.',
    'multiple_of' => ':attribute moet een veelvoud van :value zijn.',
    'not_in' => 'De geselecteerde waarde voor :attribute is ongeldig.',
    'not_regex' => 'De opmaak van :attribute is ongeldig.',
    'numeric' => ':attribute moet een getal zijn.',
    'password' => [
        'letters' => ':attribute moet minimaal één letter bevatten.',
        'mixed' => ':attribute moet minimaal één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':attribute moet minimaal één cijfer bevatten.',
        'symbols' => ':attribute moet minimaal één symbool bevatten.',
        'uncompromised' => 'De opgegeven waarde voor :attribute is aangetroffen in een datalek. Kies een andere waarde voor :attribute.',
    ],
    'present' => ':attribute moet aanwezig zijn.',
    'present_if' => ':attribute moet aanwezig zijn wanneer :other gelijk is aan :value.',
    'present_unless' => ':attribute moet aanwezig zijn tenzij :other gelijk is aan :value.',
    'present_with' => ':attribute moet aanwezig zijn wanneer :values aanwezig is.',
    'present_with_all' => ':attribute moet aanwezig zijn wanneer :values aanwezig zijn.',
    'prohibited' => ':attribute is niet toegestaan.',
    'prohibited_if' => ':attribute is niet toegestaan wanneer :other gelijk is aan :value.',
    'prohibited_unless' => ':attribute is niet toegestaan tenzij :other voorkomt in :values.',
    'prohibits' => 'Als :attribute aanwezig is, mag :other niet aanwezig zijn.',
    'regex' => 'De opmaak van :attribute is ongeldig.',
    'required' => ':attribute is verplicht.',
    'required_array_keys' => ':attribute moet gegevens bevatten voor: :values.',
    'required_if' => ':attribute is verplicht wanneer :other gelijk is aan :value.',
    'required_if_accepted' => ':attribute is verplicht wanneer :other is geaccepteerd.',
    'required_unless' => ':attribute is verplicht tenzij :other voorkomt in :values.',
    'required_with' => ':attribute is verplicht wanneer :values aanwezig is.',
    'required_with_all' => ':attribute is verplicht wanneer :values aanwezig zijn.',
    'required_without' => ':attribute is verplicht wanneer :values niet aanwezig is.',
    'required_without_all' => ':attribute is verplicht wanneer geen van :values aanwezig is.',
    'same' => ':attribute moet overeenkomen met :other.',
    'size' => [
        'array' => ':attribute moet :size items bevatten.',
        'file' => ':attribute moet :size kilobyte groot zijn.',
        'numeric' => ':attribute moet gelijk zijn aan :size.',
        'string' => ':attribute moet :size tekens bevatten.',
    ],
    'starts_with' => ':attribute moet beginnen met een van de volgende waarden: :values.',
    'string' => ':attribute moet tekst zijn.',
    'timezone' => ':attribute moet een geldige tijdzone zijn.',
    'unique' => ':attribute is al in gebruik.',
    'uploaded' => 'Uploaden van :attribute is mislukt.',
    'uppercase' => ':attribute mag alleen hoofdletters bevatten.',
    'url' => ':attribute moet een geldige URL zijn.',
    'ulid' => ':attribute moet een geldige ULID zijn.',
    'uuid' => ':attribute moet een geldige UUID zijn.',

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
            'required_if' => 'De naam van de activiteit is verplicht als deze als verplicht is ingesteld.',
        ],
        'projectId' => [
            'required_if' => 'Een project moet worden toegewezen als het activiteitstype dat vereist.',
        ],
        'projectName' => [
            'required_unless' => 'Een projectnaam is verplicht, tenzij \'project aanmaken\' is uitgeschakeld.',
        ],
        'start' => [
            'required' => 'De begindatum is verplicht.',
            'date'     => 'De begindatum moet een geldige datum zijn.',
        ],
        'end' => [
            'required' => 'De einddatum is verplicht.',
            'date'     => 'De einddatum moet een geldige datum zijn.',
            'after'    => 'De einddatum moet na de begindatum liggen.',
        ],
        'roomId' => [
            'exists' => 'De geselecteerde ruimte bestaat niet.',
        ],
        'declinedRoomId' => [
            'exists' => 'De afgewezen ruimte bestaat niet.',
        ],
        'eventTypeId' => [
            'required' => 'Een activiteitstype is verplicht.',
            'exists'   => 'Het geselecteerde activiteitstype is ongeldig.',
        ],
        'eventStatusId' => [
            'exists' => 'De geselecteerde activiteitsstatus is ongeldig.',
        ],
        'projectIdMandatory' => [
            'required' => 'Geef aan of projecttoewijzing verplicht is wanneer het activiteitstype dat vereist.',
        ],
        'eventNameMandatory' => [
            'required' => 'Geef aan of de activiteitsnaam verplicht is wanneer het activiteitstype dat vereist.',
        ],
        'creatingProject' => [
            'required' => 'Geef aan of er een nieuw project wordt aangemaakt.',
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
        'address' => 'adres',
        'affiliate_url' => 'partner-URL',
        'age' => 'leeftijd',
        'amount' => 'bedrag',
        'area' => 'gebied',
        'available' => 'beschikbaar',
        'birthday' => 'verjaardag',
        'body' => 'inhoud',
        'city' => 'plaats',
        'content' => 'inhoud',
        'country' => 'land',
        'created_at' => 'aangemaakt op',
        'creator' => 'aangemaakt door',
        'currency' => 'valuta',
        'current_password' => 'huidig wachtwoord',
        'customer' => 'klant',
        'date' => 'datum',
        'date_of_birth' => 'geboortedatum',
        'day' => 'dag',
        'deleted_at' => 'verwijderd op',
        'description' => 'beschrijving',
        'district' => 'wijk',
        'duration' => 'duur',
        'email' => 'e-mailadres',
        'excerpt' => 'samenvatting',
        'filter' => 'filter',
        'first_name' => 'voornaam',
        'gender' => 'gender',
        'group' => 'groep',
        'hour' => 'uur',
        'image' => 'afbeelding',
        'is_subscribed' => 'is geabonneerd',
        'items' => 'items',
        'last_name' => 'achternaam',
        'lesson' => 'les',
        'line_address_1' => 'adresregel 1',
        'line_address_2' => 'adresregel 2',
        'message' => 'bericht',
        'middle_name' => 'tussenvoegsel',
        'minute' => 'minuut',
        'mobile' => 'mobiel nummer',
        'month' => 'maand',
        'name' => 'naam',
        'national_code' => 'landcode',
        'number' => 'nummer',
        'password' => 'wachtwoord',
        'password_confirmation' => 'wachtwoordbevestiging',
        'phone' => 'telefoonnummer',
        'photo' => 'foto',
        'postal_code' => 'postcode',
        'preview' => 'voorbeeld',
        'price' => 'prijs',
        'product_id' => 'product-ID',
        'product_uid' => 'product-UID',
        'product_uuid' => 'product-UUID',
        'promo_code' => 'promotiecode',
        'province' => 'provincie',
        'quantity' => 'aantal',
        'recaptcha_response_field' => 'reCAPTCHA-antwoordveld',
        'remember' => 'onthouden',
        'restored_at' => 'hersteld op',
        'result_text_under_image' => 'resultaattekst onder afbeelding',
        'role' => 'rol',
        'second' => 'seconde',
        'sex' => 'geslacht',
        'shipment' => 'verzending',
        'short_text' => 'korte tekst',
        'size' => 'grootte',
        'state' => 'staat',
        'street' => 'straat',
        'student' => 'leerling',
        'subject' => 'onderwerp',
        'teacher' => 'docent',
        'terms' => 'voorwaarden',
        'test_description' => 'testbeschrijving',
        'test_locale' => 'testtaal',
        'test_name' => 'testnaam',
        'text' => 'tekst',
        'time' => 'tijd',
        'title' => 'titel',
        'updated_at' => 'bijgewerkt op',
        'user' => 'gebruiker',
        'username' => 'gebruikersnaam',
        'year' => 'jaar',
    ],
    'invitations' => [
        'user_emails' => [
            'required' => 'Het e-mailadres van de gebruiker is verplicht.',
            'array' => 'Het e-mailadresveld van gebruikers moet een lijst zijn.',
            'email' => 'Het e-mailadresveld van gebruikers moet geldige e-mailadressen bevatten.',
            'unique' => 'Het e-mailadres van gebruiker “:user” bestaat al in het systeem.',
        ],
        'permissions' => [
            'array' => 'Het autorisatieveld moet een lijst zijn.',
        ],
        'role' => [
            'sometimes' => 'Het rolveld is niet verplicht.',
        ],
    ],
    'file_upload' => [
        'server_max_size' => 'Het bestand overschrijdt de serverlimiet van :size MB. Kies een kleiner bestand of vraag de beheerder de limiet te verhogen.',
        'failed' => 'Het uploaden is mislukt. Probeer het opnieuw.',
        'max_size' => 'De maximale grootte van gedeelde bestanden is momenteel :size MB. Vraag een beheerder de limiet te verhogen of kies een kleiner bestand.',
        'invalid_file_type' => 'Dit bestandsformaat (:format) is niet toegestaan. Vraag een beheerder het toe te staan of gebruik een ander formaat.',
        'denied_file_type' => 'Bestanden van dit type (:format) worden om veiligheidsredenen nooit geaccepteerd, ook niet wanneer alle formaten zijn toegestaan. Gebruik een ander formaat, zoals PDF, PNG of JPG.',
    ],
    'timeline' => [
        'name_required' => 'De naam van de tijdlijn is verplicht.',
        'name_string' => 'De naam moet tekst zijn.',
        'name_max' => 'De naam mag maximaal 255 tekens bevatten.',
        'dataset_required' => 'De gegevensset is verplicht.',
        'dataset_array' => 'De gegevensset moet een lijst zijn.',
        'start_required' => 'De begintijd is verplicht.',
        'start_date_format' => 'De begintijd moet het formaat HH:MM hebben.',
        'end_required' => 'De eindtijd is verplicht.',
        'end_date_format' => 'De eindtijd moet het formaat HH:MM hebben.',
        'end_after_start' => 'De eindtijd moet na de begintijd liggen.',
        'description_string' => 'De beschrijving moet tekst zijn.',
        'description_max' => 'De beschrijving mag maximaal 500 tekens bevatten.',
    ],
];
