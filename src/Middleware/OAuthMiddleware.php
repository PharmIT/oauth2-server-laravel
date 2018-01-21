<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LucaDegasperi\OAuth2Server\Middleware;

use Closure;
use League\OAuth2\Server\Exception\InvalidScopeException;
use LucaDegasperi\OAuth2Server\Authorizer;
use LucaDegasperi\OAuth2Server\Exceptions\MissingScopeException;

/**
 * This is the oauth middleware class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class OAuthMiddleware
{
    /**
     * The Authorizer instance.
     *
     * @var \LucaDegasperi\OAuth2Server\Authorizer
     */
    protected $authorizer;

    /**
     * Create a new oauth middleware instance.
     *
     * @param \LucaDegasperi\OAuth2Server\Authorizer $authorizer
     */
    public function __construct(Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $scopesString
     *
     * @throws MissingScopeException
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $scopesString = null)
    {
        $scopes = [];

        if (!is_null($scopesString)) {
            $scopes = explode('+', $scopesString);
        }

        $this->authorizer->setRequest($request);

        $this->authorizer->validateAccessToken();
        $this->validateScopes($scopes);

        return $next($request);
    }

    /**
     * Validate the scopes.
     *
     * @param $scopes
     *
     * @throws MissingScopeException
     */
    public function validateScopes($scopes)
    {
        if (!empty($scopes) && !$this->authorizer->hasScope($scopes)) {
            throw new MissingScopeException($scopes);
        }
    }
}
