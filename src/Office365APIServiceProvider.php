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
		
		//set our .env variables
        $variables =
            "\n\n" .
            "\nOAUTH_APP_ID=" .
            "\nOAUTH_APP_PASSWORD=" .
            "\nOAUTH_REDIRECT_URI=" .
            "\nOAUTH_SCOPES=" .
            "\nOAUTH_AUTHORITY=" .
            "\nOAUTH_AUTHORIZE_ENDPOINT=" .
            "\nOAUTH_TOKEN_ENDPOINT=";
        file_put_contents($this->app->environmentFilePath(), $variables, FILE_APPEND);
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
