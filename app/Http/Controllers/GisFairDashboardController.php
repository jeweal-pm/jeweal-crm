<?php

namespace App\Http\Controllers;

use App\Http\Requests\GisFairDashboardRequest;
use App\Services\GisFair\GisFairDashboardService;

class GisFairDashboardController extends Controller
{
    public function __invoke(GisFairDashboardRequest $request, GisFairDashboardService $dashboard)
    {
        return view('administrator.gis-fair.dashboard', $dashboard->data($request->validated()));
    }
}
