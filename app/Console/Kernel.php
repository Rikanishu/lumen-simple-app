<?php

namespace App\Console;

use Laravel\Lumen\Application;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\Data'
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $app->singleton(
            \App\Console\Helper\InternalRequestClient::class,
            function() {
                return new \App\Console\Helper\InternalRequestClient();
            }
        );
    }


    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //

    }
}
