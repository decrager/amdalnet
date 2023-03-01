<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            if ($this->app->environment('local')) {
                return true;
            }

            return  $entry->isReportableException() ||
                    ($entry->isRequest() && Str::startsWith($entry->content['uri'], '/api')) ||
                    $entry->isFailedRequest() ||
                    $entry->isFailedJob() ||
                    $entry->isScheduledTask() ||
                    $entry->hasMonitoredTag() ||
                    ($entry->type === EntryType::LOG && $entry->content['level'] !== 'warning') ||
                    $entry->type === EntryType::QUERY ||
                    $entry->type === EntryType::NOTIFICATION;
        });

        Telescope::tag(function (IncomingEntry $entry) {
            return $entry->type === 'request'
                ? ['status:' . $entry->content['response_status']]
                : [];
        });

        Telescope::tag(function (IncomingEntry $entry) {
            if ($entry->type === 'request') {
                $customTag = Str::startsWith($entry->content['uri'], '/api') ? ['request:api'] : [];
                if (Str::startsWith($entry->content['uri'], '/api/auth')) {
                    $customTag = array_merge($customTag, ['api:auth']);
                }
                if (Str::startsWith($entry->content['uri'], '/api/auth/sso')) {
                    $customTag = array_merge($customTag, ['api:auth-sso']);
                }
                if (Str::startsWith($entry->content['uri'], '/api/oss')) {
                    $customTag = array_merge($customTag, ['api:oss']);
                }
                return $customTag;
            }
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                'admin@amdalnet.dev',
            ]);
        });
    }

    protected function authorization()
    {
//        $this->gate();

        Telescope::auth(function ($request) {
            return app()->environment(['local']) || (!empty($request->user()) && $request->user()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->count());
        });
    }
}
