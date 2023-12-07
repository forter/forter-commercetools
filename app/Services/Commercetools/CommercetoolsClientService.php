<?php
/**
 * Forter Commercetools app
 *
 * @package  Forter Commercetools app
 * @author   Forter (https://www.forter.com/)
 * @author   Developer: Pniel Cohen (Trus)
 */

declare(strict_types=1);

namespace App\Services\Commercetools;

use GuzzleHttp\ClientInterface;
use Commercetools\Api\Client\ApiRequestBuilder;
use Commercetools\Api\Client\ClientCredentialsConfig;
use Commercetools\Api\Client\Config as CommercetoolsClientConfig;
use Commercetools\Client\ClientCredentials;
use Commercetools\Client\ClientFactory;

class CommercetoolsClientService
{
    public static function getApiClientBuilder()
    {
        /** @var string $clientId */
        /** @var string $clientSecret */
        $authConfig = new ClientCredentialsConfig(
            new ClientCredentials(config('commercetools.client_id'), config('commercetools.client_secret')),
            [],
            config('commercetools.auth_url') . '/oauth/token'
        );

        $client = ClientFactory::of()->createGuzzleClient(
            new CommercetoolsClientConfig([], config('commercetools.api_url')),
            $authConfig
        );

        /** @var ClientInterface $client */
        $builder = new ApiRequestBuilder($client);

        // Include the Project key with the returned Client
        return $builder->withProjectKey(config('commercetools.project_key'));
    }
}
