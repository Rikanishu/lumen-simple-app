<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
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
        Validator::extend('more_than_zero', function($attribute, $value, $parameters, $validator) {
          return ($value > 0);
        });
    }
}
