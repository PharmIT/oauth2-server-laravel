<?php

/*
 * This file is part of Laravel OAuth 2.0.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LucaDegasperi\OAuth2Server\Entities;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * This is the client model class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class Client extends Model implements ClientEntityInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';

    public $incrementing = false;

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * Set the client's identifier.
     *
     * @param $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->id = $identifier;
    }

    /**
     * Get the client's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the client's name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set the client's redirect uri.
     *
     * @param string $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirect_uri = $redirectUri;
    }

    /**
     * Returns the registered redirect URI (as a string).
     *
     * Alternatively return an indexed array of redirect URIs.
     *
     * @return string|string[]
     */
    public function getRedirectUri()
    {
        return $this->redirectUris->map(function ($item, $key) {
            return $item->redirect_uri;
        })->toArray();
    }

    public function accessTokens()
    {
        return $this->hasMany(AccessToken::class);
    }

    public function authCodes()
    {
        return $this->hasMany(AuthCode::class);
    }

    public function scopes()
    {
        return $this->belongsToMany(Scope::class, 'oauth_client_scopes');
    }

    public function redirectUris()
    {
        return $this->hasMany(RedirectUri::class);
    }
}
