<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\CommercetoolsExtensionsHandlers\OrderCreateHandler;
use App\Helpers\UtilsHelper;

class CommercetoolsExtensionsController extends Controller
{
    /**
     * @param  Request  $request
     * @return Response
     */
    public function handleRequest(Request $request)
    {
        try {
            Log::debug('POST /commercetools/api/extensions [CommercetoolsExtensionsController::handleRequest]', ['correlation_id' => $request->header('x-correlation-id'), 'request_body' => $request->getContent(), 'request_headers' => $request->header()]);
            UtilsHelper::throwIfForterIsDisabled();
            $payload = $request->json()->all();
            $action = ucfirst(strtolower($payload['action']));
            $resource = ucfirst(strtolower($payload['resource']['typeId']));
            $resourceId = $payload['resource']['id'];
            $trigger = "{$resource}/{$action}";
            Log::info("[CommercetoolsExtensionsController::handleRequest] Trigger:{$trigger} | Resource ID:{$resourceId} | Correlation ID:{$request->header('x-correlation-id')}");

            switch ($trigger) {
                case 'Order/Create':
                    return OrderCreateHandler::handleRequest($request);
                    break;

                default:
                    Log::notice("[CommercetoolsExtensionsController::handleRequest] No handler for trigger `{$trigger}` - doing nothing | Correlation ID:{$request->header('x-correlation-id')}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("[CommercetoolsExtensionsController::handleRequest] [ERROR] {$e->getMessage()}", ['exception' => $e, 'request_body' => $request->getContent(), 'request_headers' => $request->header()]);
        }

        return response()->noContent(200);
    }
}
