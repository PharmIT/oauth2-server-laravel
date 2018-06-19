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

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use LucaDegasperi\OAuth2Server\Entities\Scope as ScopeEntity;

/**
 * This is the fluent scope class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class Scope implements ScopeRepositoryInterface
{

    /**
     * @var string
     */
    private $defaultScopes;
    public function __construct($defaultScopes = [])
    {
        $this->defaultScopes = $defaultScopes;
    }

    /**
     * Return information about a scope.
     *
     * @param string $identifier The scope identifier
     *
     * @return \League\OAuth2\Server\Entities\ScopeEntityInterface
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        return ScopeEntity::where('id', $identifier)->first();
    }

    /**
     * Given a client, grant type and optional user identifier validate the set of scopes requested are valid and optionally
     * append additional scopes or remove requested scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     * @param string $grantType
     * @param \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity
     * @param null|string $userIdentifier
     *
     * @return \League\OAuth2\Server\Entities\ScopeEntityInterface[]
     */
    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        if (!$clientEntity->has('scopes')) {
            return $scopes;
        }
        $clientScopes = $clientEntity->scopes;
        // TODO: this can be simplified imho.
        $scopes = array_filter($scopes, function ($scope) use ($clientScopes) {
            $identifier = $scope->getIdentifier();
            return $clientScopes->contains(function ($value, $key) use ($identifier) {
                return $value->getIdentifier() == $identifier;
            });
        });
        // TODO: add possibility to append scopes from clients or grants
        return $scopes;
    }
}
