<?php

namespace jakeoandasan\office365api;

use Illuminate\Support\ServiceProvider;

class Office365APIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //register our controller
        $this->app->make('jakeoandasan\office365api\MicrosoftGraphController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
