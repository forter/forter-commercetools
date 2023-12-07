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
use Illuminate\Support\Facades\Log;
use App\Services\Forter\ForterSetupService;
use App\Helpers\UtilsHelper;

class ForterSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forter:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forter Commercetools app setup (make sure to run this command after every change in code or configuration).';

    /**
     * Progress bar
     */
    private $progressBar;

    /**
     * @method createProgressBar
     * @param  integer           $maxNumberOfTasks
     * @return void
     */
    protected function createProgressBar($maxNumberOfTasks = 0)
    {
        $this->progressBar = $this->output->createProgressBar($maxNumberOfTasks);
    }

    protected function advanceProgressBar()
    {
        $this->newLine();
        $this->progressBar->advance();
        $this->newLine();
        $this->lineSepartor();
        $this->newLine();
    }

    protected function lineSepartor()
    {
        $this->line('------------------------------------------');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::info("[Commands\ForterSetup::handle] [START]");

            $this->printHeading();
            $this->createProgressBar(7); // Nubmer of tasks

            // Setup Tasks
            $this->printCurrectAppConfig();
            $this->forterCredentialsCheck();
            $this->commercetoolsCredentialsCheck();
            $this->messagingServicePrepare();
            $this->customTypeSetup();
            $this->apiExtensionSetup();
            $this->subscriptionSetup();

            // Setup Completed
            $this->printEnding();

            Log::info("[Commands\ForterSetup::handle] [END]");
        } catch (\Exception $e) {
            Log::error("[Commands\ForterSetup::handle] [ERROR] " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

    protected function printHeading()
    {
        $this->info("******************************************");
        $this->info("***  Forter Commercetools App - Setup  ***");
        $this->info("******************************************");
        $this->newLine(2);
    }

    protected function printEnding()
    {
        $this->info("**********************************************");
        $this->info("***  Forter setup completed successfully!  ***");
        $this->info("**********************************************");
        $this->newLine(2);
    }

    protected function printCurrectAppConfig()
    {
        $this->info("Current app (main) configuration:");
        foreach (ForterSetupService::getCurrentConfigSummary() as $group) {
            foreach ($group as $configLabel => $configValue) {
                $this->comment("    {$configLabel}: {$configValue}");
            }
            $this->newLine();
        }
        $this->advanceProgressBar();
    }

    protected function forterCredentialsCheck()
    {
        $this->info("Checking Forter cerdentials...");
        if (ForterSetupService::isValidForterCredentials()) {
            $this->info("[✓] Forter cerdentials are valid.");
        } else {
            $this->error("[x] Forter cerdentials are invalid. Please correct it and try again.");
            return;
        }
        $this->advanceProgressBar();
    }

    protected function commercetoolsCredentialsCheck()
    {
        $this->info("Checking Commercetools cerdentials...");
        if (ForterSetupService::isValidForterCredentials()) {
            $this->info("[✓] Commercetools cerdentials are valid.");
        } else {
            $this->error("[x] Commercetools cerdentials are invalid. Please correct it and try again.");
            return;
        }
        $this->advanceProgressBar();
    }

    protected function messagingServicePrepare()
    {
        $messagingService = UtilsHelper::getMessagingServiceType();
        $this->info("Preparing messaging service ({$messagingService})...");
        switch (UtilsHelper::getMessagingServiceType()) {
            case 'sqs':
                $awsResponse = ForterSetupService::prepareAwsSqs();
                $this->info("[✓] Amazon SQS is prepared.");
                break;
            // Additional cases/services may be added here in the future
        }
        $this->advanceProgressBar();
    }

    protected function customTypeSetup()
    {
        $customTypeKey = ForterSetupService::FORTER_CUSTOM_TYPE_KEY;
        $this->info("Creating Commercetools '{$customTypeKey}' type custom fields...");
        $customType = ForterSetupService::getCustomType();
        if ($customType && !empty($customType['key'])) {
            $this->comment("Found existing Forter custom type/fields, updating...");
            $customType = ForterSetupService::createOrUpdateCustomType($customType);
            $this->info("[✓] Custom fields of type '{$customTypeKey}' updated.");
        } else {
            $customType = ForterSetupService::createOrUpdateCustomType();
            $this->info("[✓] Custom fields of type '{$customTypeKey}' created.");
        }
        $customTypeSummary = ForterSetupService::extractCustomTypeSummary($customType);
        $this->comment('    Custom fields: '. $customTypeSummary['fields']);
        $this->advanceProgressBar();
    }

    protected function apiExtensionSetup()
    {
        $this->info("Setting up Commercetools API extension...");
        $extension = ForterSetupService::getApiExtension();
        if (!UtilsHelper::isForterPreOrderValidationEnabled()) {
            $this->warn("Found existing Forter API extension, while Forter pre-auth order validation is disabled on config, deleting...");
            ForterSetupService::deleteApiExtension();
            $this->warn("[✓] API extension deleted (API extension is unnecessary when pre-auth order validation is disabled).");
        } else {
            if ($extension && !empty($extension['key'])) {
                $this->comment("Found existing Forter API extension, updating...");
                $extension = ForterSetupService::createOrUpdateApiExtension($extension);
                $this->info("[✓] API extension updated.");
            } else {
                $extension = ForterSetupService::createOrUpdateApiExtension();
                $this->info("[✓] API extension created.");
            }
            $extensionSummary = ForterSetupService::extractExtensionSummary($extension);
            $this->comment('    Extension Destination: '. $extensionSummary['destination']);
            $this->comment('    Extension Triggers: '. $extensionSummary['triggers']);
        }
        $this->advanceProgressBar();
    }

    protected function subscriptionSetup()
    {
        $this->info("Subscribing to Commercetools messages...");
        $subscription = ForterSetupService::getSubscription();
        if ($subscription && !empty($subscription['key'])) {
            $this->comment("Found existing Forter subscription, updating...");
            $subscription = ForterSetupService::createOrUpdateSubscription($subscription);
            $this->info("[✓] Subscription updated.");
        } else {
            $subscription = ForterSetupService::createOrUpdateSubscription();
            $this->info("[✓] Subscription created.");
        }
        $subscriptionSummary = ForterSetupService::extractSubscriptionSummary($subscription);
        $this->comment('    Subscription Destination: '. $subscriptionSummary['destination']);
        $this->comment('    Subscription Message Types: '. $subscriptionSummary['messages']);
        $this->advanceProgressBar();
    }
}
