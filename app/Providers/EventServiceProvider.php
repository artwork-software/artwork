<?php

namespace App\Providers;

use App\Http\Middleware\UpdateUserStatus;
use App\Listeners\UpdateUserOnLogout;
use Artwork\Modules\Budget\Events\BudgetUpdated;
use Artwork\Modules\Budget\Listeners\BudgetModelObserver;
use Artwork\Modules\Budget\Listeners\InvalidateBudgetCache;
use Artwork\Modules\Budget\Listeners\StaticLookupObserver;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SageNotAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\CollectingSociety\Models\CollectingSociety;
use Artwork\Modules\CompanyType\Models\CompanyType;
use Artwork\Modules\Contract\Models\ContractType;
use Artwork\Modules\Currency\Models\Currency;
use Artwork\Modules\MoneySource\Models\MoneySource;
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
        ],
        BudgetUpdated::class => [
            InvalidateBudgetCache::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        // Budget model observers for automatic cache invalidation
        $budgetObserver = BudgetModelObserver::class;
        ColumnCell::observe($budgetObserver);
        SubPositionRow::observe($budgetObserver);
        SubPosition::observe($budgetObserver);
        MainPosition::observe($budgetObserver);
        Column::observe($budgetObserver);
        Table::observe($budgetObserver);
        SageAssignedData::observe($budgetObserver);
        SageNotAssignedData::observe($budgetObserver);

        // Static lookup observers
        $staticObserver = StaticLookupObserver::class;
        ContractType::observe($staticObserver);
        CompanyType::observe($staticObserver);
        Currency::observe($staticObserver);
        CollectingSociety::observe($staticObserver);
        MoneySource::observe($staticObserver);
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
