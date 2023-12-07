<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\Forter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Forter\SchemaBuilders\ForterSchemaBuilder;
use App\Models\Forter\ForterOrder;
use App\Models\Forter\ForterResponse;

class ForterApiService
{
    public const FORTER_API_BASE_URL = 'https://%sapi.forter-secure.com/v2/';
    public const FORTER_CREDENTIALD_TEST_URL = "https://%sapi.forter-secure.com/credentials/test";
    public const FORTER_API_MAX_TRYOUTS = 3;

    public static function getForterApiUrl($path = '')
    {
        $siteId = config('forter.site_id', '');
        $siteId = $siteId ? $siteId . '.' : '';
        return \sprintf(self::FORTER_API_BASE_URL, $siteId) . $path;
    }

    public static function getForterCredentialsTestUrl($path = '')
    {
        $siteId = config('forter.site_id', '');
        $siteId = $siteId ? $siteId . '.' : '';
        return \sprintf(self::FORTER_CREDENTIALD_TEST_URL, $siteId) . $path;
    }

    public static function getForterApiClient()
    {
        return Http::retry(self::FORTER_API_MAX_TRYOUTS, 100)
            ->withBasicAuth(config('forter.secret_key'), '')
            ->acceptJson()
            ->withHeaders([
                'api-version' => config('forter.api_version'),
                'x-forter-siteid' => config('forter.site_id'),
                'x-forter-client' => 'commercetools',
                'x-forter-extver' => config('forter.extver', '2.1'),
            ]);
    }

    /**
     * @method validateCredentials
     * @return ForterResponse
     */
    public static function validateCredentials()
    {
        try {
            $url = self::getForterCredentialsTestUrl();
            $response = self::getForterApiClient()
                ->get($url)
                ->throw(function ($r, $e) {
                    throw new \Exception($e->response->getBody()->getContents());
                })->json();
            return new ForterResponse($response);
        } catch (\Exception $e) {
            $forterResponse = new ForterResponse(array_replace(
                [
                    'status' => 'failed',
                    'message' => 'An error occurred while sending the request to Forter, please check the logs for more details.',
                ],
                (array) \json_decode((string) $e->getMessage(), true)
            ));
            Log::error("[ForterApiService::validateCredentials] [ERROR]", ['url' => $url, 'exception' => $e, 'forterResponse' => $forterResponse->getData()]);
            return $forterResponse;
        }
    }

    /**
     * @method makePostRequest
     * @param  string          $url
     * @param  array|object    $payload
     * @return ForterResponse
     */
    public static function makePostRequest($url, $payload)
    {
        try {
            $response = self::getForterApiClient()
                ->post($url, $payload)
                ->throw(function ($r, $e) {
                    throw new \Exception($e->response->getBody()->getContents());
                })->json();

            return new ForterResponse($response);
        } catch (\Exception $e) {
            $forterResponse = new ForterResponse(array_replace(
                [
                    'status' => 'failed',
                    'message' => 'An error occurred while sending the request to Forter, please check the logs for more details.',
                ],
                (array) \json_decode((string) $e->getMessage(), true)
            ));
            Log::error("[ForterApiService::makePostRequest] [ERROR]", ['url' => $url, 'payload' => $payload, 'exception' => $e, 'forterResponse' => $forterResponse->getData()]);
            return $forterResponse;
        }
    }

    /**
     * Prepare and send order to v2/orders - Get Forter decision.
     * @method makeOrderValidationRequest
     * @param  ForterOrder           $order
     * @param  string          $authStep  ('pre' / 'post')
     * @return ForterResponse
     */
    public static function makeOrderValidationRequest(ForterOrder $order, $authStep)
    {
        $orderSchema = ForterSchemaBuilder::buildOrderSchema($order, $authStep);
        $forterResponse = self::makePostRequest(self::getForterApiUrl('orders/' . $order->getForterOrderId()), $orderSchema);
        Log::debug("[ForterApiService::makeOrderStatusRequest] {$authStep} auth v2/orders/{$order->getForterOrderId()}", ['authStep' => $authStep, 'order' => $order, 'sentPayload' => $orderSchema, 'forterOrderValidationResponse' => $forterResponse->getData()]);
        return $forterResponse;
    }

    /**
     * Prepare and send order status to v2/status.
     * @method makeOrderStatusRequest
     * @param  ForterOrder           $order
     * @return ForterResponse
     */
    public static function makeOrderStatusRequest(ForterOrder $order)
    {
        try {
            $orderStatusSchema = ForterSchemaBuilder::buildOrderStatusSchema($order);
            $forterOrderStatusResponse = self::makePostRequest(self::getForterApiUrl('status/' . $order->getForterOrderId()), $orderStatusSchema);
            Log::debug("[ForterApiService::makeOrderStatusRequest] v2/status/{$order->getForterOrderId()}", ['order' => $order, 'sentPayload' => $orderStatusSchema, 'forterOrderStatusResponse' => $forterOrderStatusResponse->getData()]);
            return $forterOrderStatusResponse;
        } catch (\Exception $e) {
            Log::error("[ForterApiService::makeOrderStatusRequest] v2/status/{$order->getForterOrderId()} [ERROR]", ['exception' => $e, 'order' => $order, 'sentPayload' => isset($orderStatusSchema) ? $orderStatusSchema : null, 'forterOrderStatusResponse' => isset($forterOrderStatusResponse) ? $forterOrderStatusResponse->getData() : null]);
        }
    }
}
