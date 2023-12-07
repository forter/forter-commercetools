<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\PullAndRouteCommercetoolsMessages;
use App\Helpers\UtilsHelper;

class PullAndRouteSubscriptionMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forter:pull-and-route-subscription-messages  {--s|synchronous}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull and Commercetools messages from configured messaging service and route to handlers.
                                {--s|synchronous : Force running syncronously (override forter.use_async_queue_for_jobs)}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        UtilsHelper::throwIfForterIsDisabled();
        if ($this->option('synchronous') || !config('forter.use_async_queue_for_jobs')) {
            $job = new PullAndRouteCommercetoolsMessages();
            $messages = (array) $job->handle();
            $this->info("Found " . count($messages) . " messages.");
            if ($messages) {
                $this->newLine(1);
                $this->line(print_r($messages, true));
            }
        } else {
            PullAndRouteCommercetoolsMessages::dispatch();
        }
    }
}
