<?php

namespace App\Providers;

use App\Models\ApplicationRecord;
use App\Models\Client;
use App\Models\ClientPortalAccount;
use App\Models\Document;
use App\Models\FollowUp;
use App\Models\Payment;
use App\Policies\ApplicationRecordPolicy;
use App\Policies\ClientPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\FollowUpPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ApplicationRecord::class, ApplicationRecordPolicy::class);
        Gate::policy(FollowUp::class, FollowUpPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::define('manage-services', fn ($user) => $user->hasPermission('services.manage'));
        ResetPassword::createUrlUsing(fn ($user, $token) => rtrim((string) config('app.frontend_url'), '/').($user instanceof ClientPortalAccount ? '/client/reset-password' : '/reset-password').'?token='.$token.'&email='.urlencode($user->getEmailForPasswordReset()));
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(30)->by('login|'.$request->ip()),
            ];
        });
    }
}
