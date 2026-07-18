<?php

namespace App\Providers;

use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Relation::enforceMorphMap([
            'enquiry' => Enquiry::class,
            'gis_enquiry' => GisEnquiry::class,
            'user' => User::class,
        ]);
    }
}
