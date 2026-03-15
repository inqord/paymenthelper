<?php

namespace Inqord\PaymentHelper;

use Illuminate\Support\ServiceProvider;

class PaymentHelperServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/paymenthelper.php', 'paymenthelper');

        $this->app->singleton('paymenthelper', function ($app) {
            return new PaymentManager($app);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/paymenthelper.php' => config_path('paymenthelper.php'),
            ], 'paymenthelper-config');

            $this->commands([
                \Inqord\PaymentHelper\Console\InstallCommand::class,
            ]);
        }
    }
}
