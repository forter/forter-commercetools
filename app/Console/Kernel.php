<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\PullAndRouteCommercetoolsMessages;
use App\Helpers\UtilsHelper;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Pull and route messages from configured messaging service
        if (UtilsHelper::getMessagingServicePullEnabled() && UtilsHelper::isForterEnabled()) {
            $schedule->call(function () {
                UtilsHelper::dispatchJob(PullAndRouteCommercetoolsMessages::class);
            })->cron(UtilsHelper::getMessagingServicePullFrequency());
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
