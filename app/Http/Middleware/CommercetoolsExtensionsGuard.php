<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\UtilsHelper;

class CommercetoolsExtensionsGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Return 401 if authorization header is missing or incorrect
        if (!UtilsHelper::checkCommercetoolsApiExtensionsBasicAuthSecret($request->header('authorization'))) {
            Log::error('POST /commercetools/api/extensions [CommercetoolsExtensionsGuard] 401 unauthorized', ['authorizationHeader' => $request->header('authorization'), 'content' => $request->getContent(), "params" => $request->all(), 'request' => $request]);
            return response()->noContent(401);
        }

        // Return 200 if app is disabled
        if (!UtilsHelper::isForterEnabled()) {
            Log::warning('POST /commercetools/api/extensions [CommercetoolsExtensionsGuard] [SKIPPING] ' . UtilsHelper::APP_IS_DISABLED_MSG, ['authorizationHeader' => $request->header('authorization'), 'content' => $request->getContent(), 'request' => $request]);
            return response()->noContent(200);
        }

        // Return 200 if pre-auth is disabled
        if (!UtilsHelper::isForterPreOrderValidationEnabled()) {
            Log::warning('POST /commercetools/api/extensions [CommercetoolsExtensionsGuard] [SKIPPING] Forter pre-auth order validation is currently disabled by configuration.', ['authorizationHeader' => $request->header('authorization'), 'content' => $request->getContent(), 'request' => $request]);
            return response()->noContent(200);
        }

        // If test request is detected - maybe set runtime config
        if ($request->header('x-correlation-id') === 'forter-commercetools-app-test') {
            $testData = $request->input('_test') ? \json_decode(decrypt($request->input('_test')), true) : [];
            Log::debug('POST /commercetools/api/extensions [CommercetoolsExtensionsGuard] [TEST] ', ['test_request_input' => $testData, 'content' => $request->getContent(), 'request' => $request]);
            if (!empty($testData['config'])) {
                config($testData['config']);
            }
        }

        return $next($request);
    }
}
