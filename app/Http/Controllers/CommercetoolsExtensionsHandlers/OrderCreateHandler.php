<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Http\Controllers\CommercetoolsExtensionsHandlers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\Forter\ForterApiService;
use App\Services\Forter\SchemaBuilders\ForterDecisionActionsBuilder;
use App\Models\Forter\ForterOrder;
use App\Helpers\UtilsHelper;
use App\Models\Forter\ForterCommercetoolsMessage;
use App\Services\Forter\Processors\OrderValidation\ForterOrderPreDecisionProcessor;

class OrderCreateHandler
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public static function handleRequest(Request $request)
    {
        try {
            Log::info("[Commercetools\ExtensionsHandlers\OrderCreateHandler::handleRequest] [START] | Correlation ID:{$request->header('x-correlation-id')}");

            // Check if pre auth is enabled on config before movig foreward.
            if (!UtilsHelper::isForterEnabled()) {
                Log::warning("[CommercetoolsMessagesHandlers\HandleOrderCreatedMessage] [SKIPPING] " . UtilsHelper::APP_IS_DISABLED_MSG);
                return response()->noContent(200);
            }

            // Check if pre auth is enabled on config before movig foreward.
            if (!UtilsHelper::isForterPreOrderValidationEnabled()) {
                Log::warning("[CommercetoolsMessagesHandlers\HandleOrderCreatedMessage] [SKIPPING] Forter pre auth is currently disabled. Skipping order validation at this point.");
                return response()->noContent(200);
            }

            $messageModel = new ForterCommercetoolsMessage($request->json()->all());

            // Handle order pre auth request
            $processor = new ForterOrderPreDecisionProcessor($messageModel);
            $processorResult = $processor->process();

            // If configured - block order on decline decision (only on pre auth).
            if (($responseErrors = $processorResult->getPreparedCommercetoolsErrors())) {
                Log::debug(
                    "[Commercetools\ExtensionsHandlers\OrderCreateHandler::handleRequest] {$messageModel->getMessageTrigger()} response errors",
                    ['correlation_id' => $request->header('x-correlation-id'), 'response_errors' => $responseErrors, 'order_id' => $processorResult->getForterOrderId(), 'trigger' => $processorResult->getMessageTrigger(), 'auth_step' => $processorResult->getAuthStep(), 'forter_decision' => $processorResult->getForterDecision(), 'forter_config_decision_action' => $processorResult->getConfiguredForterDecisionAction()]
                );
                // Respond with errors
                return response()->json([
                    "errors" => $responseErrors,
                ], 400);
            }

            Log::debug(
                "[Commercetools\ExtensionsHandlers\OrderCreateHandler::handleRequest] {$messageModel->getMessageTrigger()} response actions",
                ['correlation_id' => $request->header('x-correlation-id'), 'response_actions' => $processorResult->getPreparedCommercetoolsUpdateActions(), 'order_id' => $processorResult->getForterOrderId(), 'trigger' => $processorResult->getMessageTrigger(), 'auth_step' => $processorResult->getAuthStep(), 'forter_decision' => $processorResult->getForterDecision(), 'forter_config_decision_action' => $processorResult->getConfiguredForterDecisionAction()]
            );
            // Respond with actions.
            return response()->json([
                "actions" => $processorResult->getPreparedCommercetoolsUpdateActions(),
            ], 200);
        } catch (\Exception $e) {
            Log::error("[Commercetools\ExtensionsHandlers\OrderCreateController::handleRequest] [ERROR] {$e->getMessage()}", ['exception' => $e, 'request_body' => $request->getContent(), 'request_headers' => $request->header()]);
        }

        return response()->noContent(200);
    }
}
