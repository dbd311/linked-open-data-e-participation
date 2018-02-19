<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Elastic\ElasticSearchEngine;

class ElasticSearchServiceProvider extends ServiceProvider {

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
        $this->app->singleton('ElasticSearchEngine', function ($app) {
            return new ElasticSearchEngine;
        });
    }

}
