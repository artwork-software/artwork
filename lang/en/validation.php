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

    'accepted' => 'Das :attribute sollte akzeptiert sein.',
    'accepted_if' => 'Das :attribute sollte akzeptiert sein,wenn :other :value ist.',
    'active_url' => 'Die URL :attribute ist ungültig.',
    'after' => ':attribute sollte ein Datum nach :date sein.',
    'after_or_equal' => ':attribute sollte ein Datum nach oder am :date sein.',
    'alpha' => ':attribute sollte nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute sollte nur Buchstaben, Nummern, Striche und Unterstriche enthalten.',
    'alpha_num' => ':attribute sollte nur Buchstaben und Nummern enthalten.',
    'array' => ':attribute muss ein Array sein.',
    'before' => ':attribute muss ein Datum vor :date sein.',
    'before_or_equal' => ':attribute sollte ein Datum vor oder am :date sein.',
    'between' => [
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'file' => ':attribute muss zwischen :min und :max kilobytes groß sein.',
        'string' => ':attribute muss zwischen :min und :max Zeichen lang sein.',
        'array' => ':attribute muss zwischen :min und :max Elemente haben.',
    ],
    'boolean' => ':attribute muss true oder false sein.',
    'confirmed' => ':attribute stimmen nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => ':attribute ist kein valides Datum.',
    'date_equals' => ':attribute sollte ein Datum sein und gleich :date sein.',
    'date_format' => ':attribute passt nicht zum Format :format.',
    'declined' => ':attribute muss deaktiviert sein.',
    'declined_if' => ':attribute muss deaktiviert sein, wenn :other :value ist.',
    'different' => ':attribute und :other sollten sich unterscheiden.',
    'digits' => ':attribute sollte :digits Ziffern enthalten.',
    'digits_between' => ':attribute sollte zwischen :min und :max Ziffern enthalten.',
    'dimensions' => ':attribute enthält ungültige Bildabmessungen.',
    'distinct' => ':attribute darf keine Duplikate enthalten.',
    'email' => ':attribute muss eine gültige E-Mail Adresse sein.',
    'ends_with' => ':attribute sollte mit den Werten :values enden.',
    'exists' => 'Die ausgewählte :attribute ist ungültig.',
    'file' => ':attribute muss eine Datei sein.',
    'filled' => ':attribute benötigt einen Wert.',
    'gt' => [
        'numeric' => ':attribute muss größer als :value sein.',
        'file' => ':attribute muss größer als :value kilobytes sein.',
        'string' => ':attribute muss länger als :value Zeichen sein.',
        'array' => ':attribute sollte mehr als :value Elemente enthalten.',
    ],
    'gte' => [
        'numeric' => ':attribute muss größer oder gleich :value sein.',
        'file' => ':attribute muss gleich oder größer als :value kilobytes sein.',
        'string' => ':attribute muss :value Zeichen oder länger sein.',
        'array' => ':attribute sollte :value oder mehr Elemente enthalten.',
    ],
    'image' => ':attribute muss ein Bild sein.',
    'in' => 'Das ausgewählte Attribut :attribute ist ungültig.',
    'in_array' => ':attribute existiert nicht in :other.',
    'integer' => ':attribute muss eine Nummer sein.',
    'ip' => ':attribute muss eine gültige IP Addresse sein.',
    'ipv4' => ':attribute muss eine gültige IPv4 Addresse sein.',
    'ipv6' => ':attribute muss eine gültige IPv6 Addresse sein.',
    'json' => ':attribute muss ein gültiger JSON string sein.',
    'lt' => [
        'numeric' => ':attribute sollte kleiner als :value sein.',
        'file' => ':attribute sollte kleiner als :value kilobytes sein.',
        'string' => ':attribute muss kürzer als :value Zeichen sein.',
        'array' => ':attribute sollte weniger als :value Elemente enthalten.',
    ],
    'lte' => [
        'numeric' => ':attribute muss gleich :value sein oder kleiner.',
        'file' => ':attribute muss gleich :value kilobytes sein oder kleiner.',
        'string' => ':attribute muss :value Zeichen oder kleiner sein.',
        'array' => ':attribute sollte :value oder weniger Elemente enthalten.',
    ],
    'max' => [
        'numeric' => ':attribute sollte nicht größer als :max sein.',
        'file' => ':attribute sollte nicht größer als :max kilobytes sein.',
        'string' => ':attribute sollte nicht länger als :max Zeichen lang sein.',
        'array' => ':attribute sollte nicht mehr als :max Elemente enthalten.',
    ],
    'mimes' => ':attribute sollte eine Datei vom Typ :values sein.',
    'mimetypes' => ':attribute sollte eine Datei vom Typ :values sein.',
    'min' => [
        'numeric' => ':attribute sollte mindestens :min sein.',
        'file' => ':attribute sollte mindestens :min kilobytes groß sein.',
        'string' => ':attribute sollte mindestens :min Zeichen lang sein.',
        'array' => ':attribute sollte mindestens :min Elemente enthalten.',
    ],
    'multiple_of' => ':attribute muss ein Vielfaches von :value sein.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => ':attribute muss eine Nummer sein.',
    'password' => 'Das Kennwort ist nicht korrekt.',
    'present' => 'Das Feld attribute muss aktiv sein.',
    'prohibited' => 'Das Feld :attribute ist verboten.',
    'prohibited_if' => 'Das Feld :attribute ist verboten, wenn :other :value ist.',
    'prohibited_unless' => 'Das Feld :attribute ist verboten, außer :other ist :values.',
    'prohibits' => 'Das Feld :attribute verhindert, dass :other aktiv ist.',
    'regex' => 'Das :attribute sollte Großbuchstaben, Kleinbuchstaben, Zahlen, Sonderzeichen (z.B. !,$,#,_ oder %) enthalten und mindestens 10 Zeichen lang sein.',
    'required' => ':attribute ist ein Pflichtfeld.',
    'required_if' => ':attribute ist ein Pflichtfeld wenn :other :value ist .',
    'required_unless' => ':attribute ist ein Pflichtfeld außer :other ist in :values.',
    'required_with' => ':attribute ist ein Pflichtfeld wenn :values vorhanden ist.',
    'required_with_all' => ':attribute ist ein Pflichtfeld wenn alle :values vorhanden sind.',
    'required_without' => ':attribute ist ein Pflichtfeld :values nicht vorhanden ist.',
    'required_without_all' => ':attribute ist ein Pflichtfeld keine der Werte :values vorhanden sind.',
    'same' => ':attribute und :other müssen gleich sein.',
    'size' => [
        'numeric' => 'Das Attribut :attribute muss :size sein.',
        'file' => ':attribute muss :size Kilobytes groß sein.',
        'string' => ':attribute muss :size Zeichen lang sein.',
        'array' => ':attribute muss :size Elemente enthalten.',
    ],
    'starts_with' => ':attribute muss mit einem der folgenden Werte beginnen: :values.',
    'string' => ':attribute muss ein String sein.',
    'timezone' => ':attribute must eine a gültige Zeitzone sein.',
    'unique' => ':attribute existiert bereits.',
    'uploaded' => 'Der Upload von :attribute ist fehlgeschlagen.',
    'url' => ':attribute Format ist ungültig.',
    'uuid' => ':attribute muss eine gültige UUID sein.',

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
        'email' => 'Email',
        'password' => 'Passwort'
    ],

];
