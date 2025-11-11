<?php

namespace App\Providers;

use App\Http\Middleware\UpdateUserStatus;
use App\Listeners\UpdateUserOnLogout;
use Artwork\Modules\Workflow\Events\WorkflowTriggered;
use Artwork\Modules\Workflow\Listeners\AutoStartWorkflowListener;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Logout::class => [
            UpdateUserOnLogout::class,
        ],
        WorkflowTriggered::class => [
            AutoStartWorkflowListener::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
