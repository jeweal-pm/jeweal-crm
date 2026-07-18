<?php

namespace App\Providers;

use App\Models\Enquiry;
use App\Models\GisEnquiry;
use App\Models\GmsStoneEnquiry;
use App\Policies\EnquiryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Enquiry::class => EnquiryPolicy::class,
        GisEnquiry::class => EnquiryPolicy::class,
        GmsStoneEnquiry::class => EnquiryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(fn ($user) => $user->hasRole('root') || $user->primaryRoleName() === 'root' ? true : null);
    }
}
