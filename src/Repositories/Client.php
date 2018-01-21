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
     * Limit clients to grants.
     *
     * @var bool
     */
    protected $limitClientsToGrants = false;

    /**
     * Create a new fluent client instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param bool $limitClientsToGrants
     */
    public function __construct($limitClientsToGrants = false)
    {
        $this->limitClientsToGrants = $limitClientsToGrants;
    }

    /**
     * Check if clients are limited to grants.
     *
     * @return bool
     */
    public function areClientsLimitedToGrants()
    {
        return $this->limitClientsToGrants;
    }

    /**
     * Whether or not to limit clients to grants.
     *
     * @param bool $limit
     */
    public function limitClientsToGrants($limit = false)
    {
        $this->limitClientsToGrants = $limit;
    }

    /**
     * Get a client.
     *
     * @param string $clientIdentifier The client's identifier
     * @param string $grantType The grant type used
     * @param null|string $clientSecret The client's secret (if sent)
     * @param bool $mustValidateSecret If true the client must attempt to validate the secret if the client
     *                                        is confidential
     *
     * @throws \Exception
     *
     * @return ClientEntity|null
     */
    public function getClientEntity($clientId, $grantType, $clientSecret = null, $mustValidateSecret = true)
    {
        $query = null;

        if ($mustValidateSecret && is_null($clientSecret)) {
            // TODO: correct exception type
            throw new \Exception('No client secret provided');
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
