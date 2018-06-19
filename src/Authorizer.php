<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LucaDegasperi\OAuth2Server;

use League\OAuth2\Server\AuthorizationServer as Issuer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer as Checker;
use LucaDegasperi\OAuth2Server\Exceptions\NoActiveAccessTokenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is the authorizer class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class Authorizer
{
    /**
     * The authorization server (aka the issuer).
     *
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $issuer;

    /**
     * The resource server (aka the checker).
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $checker;

    /**
     * The auth code request parameters.
     *
     * @var array
     */
    protected $authCodeRequestParams;

    /**
     * The redirect uri generator.
     *
     * @var bool|null
     */
    protected $redirectUriGenerator = null;

    /**
     * The request to issue or validate
     *
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $request;

    /**
     * The response to issue or validate
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * This validation response from the resource server
     */
    protected $validationRespose;

    /**
     * Create a new Authorizer instance.
     *
     * @param \League\OAuth2\Server\AuthorizationServer $issuer
     * @param \League\OAuth2\Server\ResourceServer $checker
     */
    public function __construct(Issuer $issuer, Checker $checker)
    {
        $this->issuer = $issuer;
        $this->checker = $checker;
        $this->authCodeRequestParams = [];
    }

    /**
     * Get the issuer.
     *
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function getIssuer()
    {
        return $this->issuer;
    }


    public function getValidationResponse()
    {
        //TODO make this less hacky
        if (is_null($this->validationRespose)) {
            $this->validationRespose = $this->validateAccessToken()->getAttributes();
        }

        return $this->validationRespose;
    }

    /**
     * Issue an access token if the request parameters are valid.
     *
     * @return object a response object for the protocol in use
     */
    public function issueAccessToken()
    {
        return $this->convertResponse(
            $this->issuer->respondToAccessTokenRequest($this->request, $this->response)
        );
    }

    /**
     * Get the Auth Code request parameters.
     *
     * @return array
     */
    public function getAuthCodeRequestParams()
    {
        return $this->authCodeRequestParams;
    }

    /**
     * Get a single parameter from the auth code request parameters.
     *
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function getAuthCodeRequestParam($key, $default = null)
    {
        if (array_key_exists($key, $this->authCodeRequestParams)) {
            return $this->authCodeRequestParams[$key];
        }

        return $default;
    }

    /**
     * Check the validity of the auth code request.
     *
     * @return null a response appropriate for the protocol in use
     */
    public function checkAuthCodeRequest()
    {
        $this->authCodeRequestParams = $this->issuer->validateAuthorizationRequest($this->request);
    }

    /**
     * Issue an auth code.
     *
     * @param string $ownerType the auth code owner type
     * @param string $ownerId the auth code owner id
     * @param array $params additional parameters to merge
     *
     * @return string the auth code redirect url
     */
    public function issueAuthCode($ownerType, $ownerId, $params = [])
    {
        //TODO
        $params = array_merge($this->authCodeRequestParams, $params);

        return $this->issuer->getGrantType('authorization_code')->newAuthorizeRequest($ownerType, $ownerId, $params);
    }

    /**
     * Generate a redirect uri when the auth code request is denied by the user.
     *
     * @return string a correctly formed url to redirect back to
     */
    public function authCodeRequestDeniedRedirectUri()
    {
        //TODO
        $error = new AccessDeniedException();

        return $this->getRedirectUriGenerator()->make($this->getAuthCodeRequestParam('redirect_uri'), [
                        'error' => $error->errorType,
                        'error_description' => $error->getMessage(),
                ]
        );
    }

    /**
     * Get the RedirectUri generator instance.
     *
     * @return RedirectUri
     */
    public function getRedirectUriGenerator()
    {
        //TODO
        if (is_null($this->redirectUriGenerator)) {
            $this->redirectUriGenerator = new RedirectUri();
        }

        return $this->redirectUriGenerator;
    }

    /**
     * Set the RedirectUri generator instance.
     *
     * @param $redirectUri
     */
    public function setRedirectUriGenerator($redirectUri)
    {
        //TODO
        $this->redirectUriGenerator = $redirectUri;
    }

    /**
     * Validate a request with an access token in it.
     *
     * @throws NoActiveAccessTokenException
     *
     * @return mixed
     */
    public function validateAccessToken()
    {
        try {
            return $this->checker->validateAuthenticatedRequest($this->request);
        } catch (OAuthServerException $e) {
            throw new NoActiveAccessTokenException($e);
        }
    }

    /**
     * get the scopes associated with the current request.
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->getValidationResponse()['oauth_scopes'];
    }

    /**
     * Check if the current request has all the scopes passed.
     *
     * @param string|array $scope the scope(s) to check for existence
     *
     * @return bool
     */
    public function hasScope($scope)
    {
        if (is_array($scope)) {
            foreach ($scope as $s) {
                if ($this->hasScope($s) === false) {
                    return false;
                }
            }

            return true;
        }

        return in_array($scope, $this->getScopes());
    }

    /**
     * Get the resource owner ID of the current request.
     *
     * @return string
     */
    public function getResourceOwnerId()
    {
        //TODO check if this is right
        return $this->getValidationResponse()['oauth_user_id'];
    }

    /**
     * Get the resource owner type of the current request (client or user).
     *
     * @return string
     */
    public function getResourceOwnerType()
    {
        // TODO check if this is right
        $response = $this->getValidationResponse();
        return $response['oauth_user_id'] ? 'user' : 'client';
    }

    /**
     * Get the client id of the current request.
     *
     * @return string
     */
    public function getClientId()
    {
        // TODO: check if this is right
        return $this->getValidationResponse()['oauth_client_id'];
    }

    /**
     * Set the request to use on the issuer and checker.
     *
     * @param $request
     */
    public function setRequest(Request $request)
    {
        $psr7Factory = new DiactorosFactory();
        $this->request = $psr7Factory->createRequest($request);
    }


    /**
     * Set the response to use on the issuer and checker.
     *
     * @param $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Convert a PSR7 response to a Illuminate Response.
     *
     * @param \Psr\Http\Message\ResponseInterface $psrResponse
     * @return \Illuminate\Http\Response
     */
    public function convertResponse($psrResponse)
    {
        return new \Illuminate\Http\Response(
            $psrResponse->getBody(),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}
