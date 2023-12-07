<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\MessagingServices\MessagingServicesManager;

class PullAndRouteCommercetoolsMessages implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    //public $backoff = 5;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    //public $uniqueFor = 240;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    //public $deleteWhenMissingModels = true;

    /**
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug('[PullAndRouteSubscriptionMessages::handle] [START]');

        $processedMessages = MessagingServicesManager::pullAndProcessMessages();

        Log::debug('[PullAndRouteSubscriptionMessages::handle] [END]');

        return $processedMessages;
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed($e)
    {
        Log::error('PullAndRouteCommercetoolsMessages Job [Exception]:' . $e->getMessage(), ['trace' => $e->getTrace()]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            new WithoutOverlapping(),
        ];
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return 'PullAndRouteCommercetoolsMessages';
    }
}
