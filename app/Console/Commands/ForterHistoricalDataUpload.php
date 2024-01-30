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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\UtilsHelper;
use App\Models\Forter\ForterOrder;
use App\Services\Commercetools\CommercetoolsOrdersService;
use App\Services\Forter\SchemaBuilders\ForterSchemaBuilder;

class ForterHistoricalDataUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forter:historical-data-upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates historical orders data for upload to Forter, in order to optimize the model\'s accuracy.';

    /**
     * Progress bar
     */
    private $progressBar;

    /**
     * @method createProgressBar
     * @param  integer  $maxNumberOfTasks
     * @return void
     */
    protected function createProgressBar($maxNumberOfTasks = 0)
    {
        $this->progressBar = $this->output->createProgressBar($maxNumberOfTasks);
    }

    protected function advanceProgressBar()
    {
        $this->progressBar->advance();
    }

    protected function lineSepartor()
    {
        $this->newLine();
        $this->line('------------------------------------------');
        $this->newLine();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::info("[Commands\ForterHistoricalDataUpload::handle] [START]");

            $this->printHeading();

            $filename = 'historical-data_generated-on-' . Carbon::now()->format('Y-m-d') . '.jsonl';
            // Delete previously generated file.
            Storage::disk('local_forter')->delete($filename);

            $expand = ['paymentInfo.payments[*]', 'cart'];
            $fromDate = Carbon::now()->subMonths(12)->format('Y-m-d\TH:i:s.000\Z');

            // Check how many historical orders exists
            $request = CommercetoolsOrdersService::get(null, 1, true);
            $request = $request->withWhere(sprintf('createdAt > "%s"', $fromDate));
            $totalOrders = $request->execute()->getTotal();
            $this->comment("Found {$totalOrders} orders.");

            // If found orders
            if ($totalOrders) {
                $this->comment("Processing...");
                $this->createProgressBar($totalOrders);

                $bulkLimit = 100;
                $lastId = null;
                $continue = true;
                $skippedAlreadySent = [];
                $prepared = [];

                while ($continue) {
                    sleep(1);

                    if ($lastId === null) {
                        $request = CommercetoolsOrdersService::get($expand, $bulkLimit, true);
                        $request = $request
                            ->withSort('id asc')
                            ->withWithTotal('false')
                            ->withWhere(sprintf('createdAt > "%s"', $fromDate));
                    } else {
                        $request = CommercetoolsOrdersService::get($expand, $bulkLimit, true);
                        $request = $request
                            ->withSort('id asc')
                            ->withWithTotal('false')
                            ->withWhere(sprintf('createdAt > "%s" and id > "%s"', $fromDate, $lastId));
                    }

                    $response = $request->execute();
                    $results = $response->getResults();
                    $continue = $response->getCount() == $bulkLimit;

                    if (!empty($results)) {
                        $lastId = $results->end()->getId();

                        foreach ($results as $order) {
                            try {
                                //$this->line("  Preparing Commercetools order ID: {$order->getId()}");
                                $orderModel = ForterOrder::getInstance($order);
                                if (!empty($orderModel->getForterResponse())) {
                                    $skippedAlreadySent[] = $order->getId();
                                } else {
                                    $orderSchema = ForterSchemaBuilder::buildHistoricalOrderSchema($orderModel);
                                    $jsonLine = \json_encode($orderSchema);
                                    if (! Storage::disk('local_forter')->append($filename, $jsonLine)) {
                                        throw new \Exception("Couldn't write data to local file ('forter/{$filename}')");
                                    }
                                    $prepared[] = $order->getId();
                                }
                            } catch (\Exception $e) {
                                $this->error(" [ERROR] " . $e->getMessage());
                                Log::error("[Commands\ForterHistoricalDataUpload::handle] [ERROR] while preparing order (ID) {$order->getId()} | " . $e->getMessage(), ['exception' => $e]);
                            }

                            $this->advanceProgressBar();
                            //$this->newLine();
                        }
                    }
                }

                $this->newLine();

                if ($skippedAlreadySent) {
                    $this->lineSepartor();
                    $this->comment("Some of the orders have already been sent to the Forter and therefore have been skipped:");
                    $this->line(implode(',', $skippedAlreadySent));
                }

                $this->lineSepartor();

                if (Storage::disk('local_forter')->exists($filename) && $prepared) {
                    $this->comment("Orders on file: " . count($prepared));
                    $this->newLine();

                    $this->info("The file in ready and can be found on: " . Storage::disk('local_forter')->path($filename));
                    $this->info("Please proceed by uploading it manually to your dedicated folder on Forter's AWS (as instructed on your Forter Portal)");
                } else {
                    $this->comment("No file has been created.");
                }
            }

            // Setup Completed
            $this->printEnding();

            Log::info("[Commands\ForterHistoricalDataUpload::handle] [END]");
        } catch (\Exception $e) {
            Log::error("[Commands\ForterHistoricalDataUpload::handle] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            if ($filename && Storage::disk('local_forter')->exists($filename)) {
                Storage::disk('local_forter')->delete($filename);
            }

            throw $e;
        }
    }

    protected function printHeading()
    {
        $this->newLine();
        $this->info("***********************************************************");
        $this->info("***  Forter Commercetools App - Historical Data Upload  ***");
        $this->info("***********************************************************");
        $this->newLine(2);
    }

    protected function printEnding()
    {
        $this->newLine();
        $this->info("***************************************************************");
        $this->info("***  Forter historical data upload completed successfully!  ***");
        $this->info("***************************************************************");
        $this->newLine(2);
    }
}
