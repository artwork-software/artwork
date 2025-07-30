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

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'The :attribute field must be a date after :date.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'The :attribute field must be :digits digits.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'The :attribute field must be a valid email address.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute field format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute field must match :other.',
    'size' => [
        'array' => 'The :attribute field must contain :size items.',
        'file' => 'The :attribute field must be :size kilobytes.',
        'numeric' => 'The :attribute field must be :size.',
        'string' => 'The :attribute field must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => 'The :attribute field must be a string.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'The :attribute field must be a valid URL.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'The :attribute field must be a valid UUID.',

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
            'required_if' => 'Event name is required if it is marked as mandatory.',
        ],
        'projectId' => [
            'required_if' => 'A project must be assigned when required by the event type.',
        ],
        'projectName' => [
            'required_unless' => 'Project name is required unless "creating project" is false.',
        ],
        'start' => [
            'required' => 'Start date is required.',
            'date'     => 'Start must be a valid date.',
        ],
        'end' => [
            'required' => 'End date is required.',
            'date'     => 'End must be a valid date.',
            'after'    => 'End date must be after the start date.',
        ],
        'roomId' => [
            'exists' => 'The selected room does not exist.',
        ],
        'declinedRoomId' => [
            'exists' => 'The declined room does not exist.',
        ],
        'eventTypeId' => [
            'required' => 'Event type is required.',
            'exists'   => 'The selected event type is invalid.',
        ],
        'eventStatusId' => [
            'exists' => 'The selected event status is invalid.',
        ],
        'projectIdMandatory' => [
            'required' => 'Project assignment requirement must be specified if the event type demands it.',
        ],
        'eventNameMandatory' => [
            'required' => 'Event name requirement must be specified if the event type demands it.',
        ],
        'creatingProject' => [
            'required' => 'It must be specified whether a new project is being created.',
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
            'required' => 'The user email field is required',
            'array' => 'The user email field must be an array',
            'email' => 'The user email field must contain valid email addresses',
            'unique' => 'The user email “:user” already exists in the system',
        ],
        'permissions' => [
            'array' => 'The authorization field must be an array',
        ],
        'role' => [
            'sometimes' => 'The role field is not required',
        ],
    ],
    'file_upload' => [
        'max_size' => 'The maximum shared file size is currently :size MB, either have an admin increase it or try a smaller file',
        'invalid_file_type' => 'This file format is not released :format, please ask an admin to release it or use another format',
    ],
    'timeline' => [
        'name_required' => 'The timeline name is required.',
        'name_string' => 'The name must be a string.',
        'name_max' => 'The name may not be greater than 255 characters.',
        'dataset_required' => 'The dataset is required.',
        'dataset_array' => 'The dataset must be an array.',
        'start_required' => 'The start time is required.',
        'start_date_format' => 'The start time must be in the format HH:MM.',
        'end_required' => 'The end time is required.',
        'end_date_format' => 'The end time must be in the format HH:MM.',
        'end_after_start' => 'The end time must be after the start time.',
        'description_string' => 'The description must be a string.',
        'description_max' => 'The description may not exceed 500 characters.',
    ],
];
