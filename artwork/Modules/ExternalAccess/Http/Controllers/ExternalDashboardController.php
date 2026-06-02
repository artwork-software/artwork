<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ExternalDashboardController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Dashboard');
    }
}
