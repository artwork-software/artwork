<?php

namespace Artwork\Modules\Mail\Settings;

use Spatie\LaravelSettings\Settings;

class MailSettings extends Settings
{
    public ?string $host;
    public ?int $port;
    public ?string $encryption;
    public ?string $username;
    public ?string $password;
    public ?string $from_address;
    public ?string $from_name;

    public static function group(): string
    {
        return 'mail';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['password'];
    }
}
