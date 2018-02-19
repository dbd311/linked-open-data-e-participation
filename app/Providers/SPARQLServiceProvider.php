<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\SPARQL\SPARQLEngine;

class SPARQLServiceProvider extends ServiceProvider {

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton('SPARQLEngine', function ($app) {
            return new SPARQLEngine;
        });
    }

}
