<?php

namespace Artwork\Modules\GeneralSettings\Models;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $page_title;

    public string $company_name;

    public bool $setup_finished;

    public string $banner_path;

    public string $big_logo_path;

    public string $small_logo_path;

    public string $impressum_link;

    public string $business_name;

    public string $privacy_link;

    public string $email_footer;

    public string $invitation_email;

    public string $business_email;

    public bool $budget_account_management_global;

    public array $allowed_project_file_mimetypes;
    public array $allowed_room_file_mimetypes;
    public array $allowed_branding_file_mimetypes;
    public array $allowed_contract_file_mimetypes;
    public int $allowed_project_file_size;
    public int $allowed_room_file_size;
    public int $allowed_branding_file_size;
    public int $allowed_contract_file_size;
    public int $event_time_length_minutes;

    public string $start_night_time;
    public string $end_night_time;

    public string $playing_time_window_start;
    public string $playing_time_window_end;

    public bool $shift_commit_workflow_enabled = false;
    public bool $warn_multiple_assignments = false;

    public static function group(): string
    {
        return 'general';
    }
}
