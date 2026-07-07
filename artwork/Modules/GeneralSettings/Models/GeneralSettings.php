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
    public string $event_start_time;
    public bool $event_all_day_default;

    public string $start_night_time;
    public string $end_night_time;

    public string $playing_time_window_start;
    public string $playing_time_window_end;

    public bool $shift_commit_workflow_enabled = false;
    public bool $warn_multiple_assignments = false;
    public float $breakfast_deduction_per_day = 5.60;

    /**
     * Default state of the "do not save artist in database" checkbox when creating an artist residency.
     */
    public bool $artist_residency_do_not_save_default = false;

    /**
     * Default value pre-filled into the "daily allowance" (Tagegeld) field of a new artist residency.
     */
    public float $artist_residency_daily_allowance_default = 0.0;

    // Ordered, toggleable name columns shown at the front of the artist residency table.
    // Each entry: ['key' => 'name'|'first_name'|'last_name', 'enabled' => bool]
    public array $artist_residency_name_columns = [
        ['key' => 'name', 'enabled' => true],
        ['key' => 'first_name', 'enabled' => false],
        ['key' => 'last_name', 'enabled' => false],
    ];

    public string $letterhead_name = '';
    public string $letterhead_street = '';
    public string $letterhead_zip_code = '';
    public string $letterhead_city = '';
    public string $letterhead_email = '';

    public int $per_diem_export_counter = 0;

    public bool $inventory_detailed_articles_always_quantity_one = false;

    public bool $inventory_show_inventory_number_as_name = false;

    public string $inventory_number_prefix = '';

    public static function group(): string
    {
        return 'general';
    }
}
