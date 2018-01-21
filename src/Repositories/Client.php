<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LucaDegasperi\OAuth2Server\Repositories;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use LucaDegasperi\OAuth2Server\Entities\Client as ClientEntity;

/**
 * This is the fluent client class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class Client implements ClientRepositoryInterface
{
    /**
     * Get a client.
     *
     * @param string $clientId The client's identifier
     * @param string $grantType The grant type used
     * @param null|string $clientSecret The client's secret (if sent)
     * @param bool $mustValidateSecret If true the client must attempt to validate the secret if the client
     *                                        is confidential
     *
     * @throws OAuthServerException
     *
     * @return ClientEntity|null
     */
    public function getClientEntity($clientId, $grantType, $clientSecret = null, $mustValidateSecret = true)
    {
        $query = null;

        if ($mustValidateSecret && is_null($clientSecret)) {
            // TODO: correct exception type
            throw OAuthServerException::invalidClient();
        }

        $query = ClientEntity::where('id', $clientId);
        if (!is_null($clientSecret)) {
            $query = $query->where('oauth_clients.secret', $clientSecret);
        }


        // TODO: check grants

        $result = $query->first();

        if (is_null($result)) {
            return;
        }

        return $result;
    }
}
