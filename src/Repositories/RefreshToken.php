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

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use LucaDegasperi\OAuth2Server\Entities\RefreshToken as RefreshTokenEntity;
use Carbon\Carbon;
/**
 * This is the fluent refresh token class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class RefreshToken implements RefreshTokenRepositoryInterface
{
    /**
     * Creates a new refresh token.
     *
     * @return RefreshTokenEntity
     */
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    /**
     * Revoke the refresh token.
     *
     * @param string $tokenId
     */
    public function revokeRefreshToken($tokenId)
    {
        $gracePeriod = config('oauth2.refresh_token_grace_period');
        $token = RefreshTokenEntity::where('token', $tokenId)->first();
        if ($gracePeriod > 0) {
            $token->setExpiryDateTime(Carbon::now()->addSeconds($gracePeriod));
            $token->save();

            return;
        }

        $token->delete();

    }
    /**
     * Check if the refresh token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        return RefreshTokenEntity::where('token', $tokenId)->where('expires_at', '>', Carbon::now())->count() === 0;
    }

    /**
     * Create a new refresh token_name.
     *
     * @param RefreshTokenEntityInterface $refreshTokenEntity
     *
     * @throws UniqueTokenIdentifierConstraintViolationException
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $refreshTokenEntity->save();
    }
}
