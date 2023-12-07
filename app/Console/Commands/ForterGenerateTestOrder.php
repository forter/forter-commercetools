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
use App\Helpers\Forter\ForterTestHelper;
use App\Helpers\UtilsHelper;

class ForterGenerateTestOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forter:generate-test-order  {forter-decision} {--payment-interface} {--payment-method} {--payment-additional-info}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Forter test order by duplicating pre-prepared admin order template (see docs)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        UtilsHelper::throwIfForterIsDisabled();

        // Disable async queue for this runtime
        config('forter.use_async_queue_for_jobs', false);

        $forterDecision = $this->argument('forter-decision');
        $paymentInterface = $this->option('payment-interface') ?: 'stripe';
        $paymentMethod = $this->option('payment-method') ?: 'credit_card';

        $paymentAdditionalInfo = $this->option('payment-additional-info') ?
            UtilsHelper::toArrayRecursive($this->option('payment-additional-info')) :
            [
                'cardBin' => '42424242',
                'cardBank' => 'Chase',
                'cardBrand' => 'VISA',
                'cardType' => 'CREDIT',
                'countryOfIssuance' => 'US',
                'cardExpMonth' => '03',
                'cardExpYear' => '2030',
                'cardLastFour' => '4242',
                'fingerprint' => 'Xt5EWLLDS7FJjR1c',
                'fullResponsePayload' => '',
                'nameOnCard' => null, // Will be generated from billing address
                'paymentProcessorData' => '{"processorMerchantId":"ncxwe5490asjdf","processorName":"Chase Paymentech","processorTransactionId":"fjdsS46sdklFd20"}',
                'verificationResults' => '{"authenticationMethodType":"THREE_DS","authorizationCode":"A33244","avsFullResult":"Y","avsNameResult":"M","cvvResult":"M","processorResponseCode":"D23","processorResponseText":"Stolen card"}',
            ];

        $order = ForterTestHelper::generateForterTestOrder(
            $forterDecision,
            $paymentInterface,
            $paymentMethod,
            $paymentAdditionalInfo
        );

        $this->line(json_encode($order, JSON_PRETTY_PRINT));
    }
}
