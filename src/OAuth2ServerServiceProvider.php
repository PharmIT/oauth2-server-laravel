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

use DateInterval;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use League\OAuth2\Server\ResourceServer;
use LucaDegasperi\OAuth2Server\Middleware\CheckAuthCodeRequestMiddleware;
use LucaDegasperi\OAuth2Server\Middleware\OAuthClientOwnerMiddleware;
use LucaDegasperi\OAuth2Server\Middleware\OAuthMiddleware;
use LucaDegasperi\OAuth2Server\Middleware\OAuthUserOwnerMiddleware;
use LucaDegasperi\OAuth2Server\Repositories\AccessToken;
use LucaDegasperi\OAuth2Server\Repositories\AuthCode;
use LucaDegasperi\OAuth2Server\Repositories\Client;
use LucaDegasperi\OAuth2Server\Repositories\RefreshToken;
use LucaDegasperi\OAuth2Server\Repositories\Scope;

/**
 * This is the oauth2 server service provider class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class OAuth2ServerServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
        $this->setupMigrations($this->app);
    }

    /**
     * Setup the config.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    protected function setupConfig(Application $app)
    {
        $source = realpath(__DIR__.'/../config/oauth2.php');

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('oauth2.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('oauth2');
        }

        $this->mergeConfigFrom($source, 'oauth2');
    }

    /**
     * Setup the migrations.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    protected function setupMigrations(Application $app)
    {
        $source = realpath(__DIR__.'/../database/migrations/');

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => database_path('migrations')], 'migrations');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAuthorizer($this->app);
        $this->registerMiddlewareBindings($this->app);
    }

    /**
     * Register the Authorization server with the IoC container.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    public function registerAuthorizer(Application $app)
    {
        $app->singleton('oauth2-server.authorizer', function ($app) {
            $config = $app['config']->get('oauth2');
            $issuer = $app->make(AuthorizationServer::class,[
                'clientRepository' => $app->make(Client::class),
                'accessTokenRepository' => $app->make(AccessToken::class),
                'scopeRepository' => $app->make(Scope::class),
                'privateKey' => new CryptKey($config['private_key_path'], $config['key_passphrase']),
                'encryptionKey' => $app['config']->get('app')['key'],
                'responseType' => $app->make($config['response_type'])
            ]);


            // add the supported grant types to the authorization server
            foreach ($config['grant_types'] as $grantIdentifier => $grantParams) {
                $params = [];
                if ($grantIdentifier === 'password') {
                    if (!isset($grantParams['repository'])) {
                        throw new \Exception('No repository given for password grant.');
                    }
                    $params['userRepository'] = $app->make($grantParams['repository']);
                }
                if ($grantIdentifier === 'authorization_code') {
                    $params['authCodeRepository'] = new AuthCode();
                    $params['authCodeTTL'] = new DateInterval('PT'.$grantParams['auth_token_ttl'].'S');
                }

                $params['refreshTokenRepository'] = $app->make(RefreshToken::class);
                /** @var GrantTypeInterface $grantType */
                $grantType = $app->make($grantParams['class'], $params);

                if (isset($grantParams['refresh_token_ttl'])) {
                    $grantType->setRefreshTokenTTL(new DateInterval('PT'.$grantParams['refresh_token_ttl'].'S'));
                }

                $issuer->enableGrantType(
                    $grantType,
                    new DateInterval('PT'.$grantParams['access_token_ttl'].'S')
                );
            }

            $checker = $app->make(ResourceServer::class, [
                'accessTokenRepository' => $app->make(AccessToken::class),
                'publicKey' => new CryptKey($config['public_key_path'], $config['key_passphrase'])
            ]);

            $authorizer = new Authorizer($issuer, $checker);
            $authorizer->setRequest($app['request']);
            $authorizer->setResponse(new \Zend\Diactoros\Response());

            $app->refresh('request', $authorizer, 'setRequest');

            return $authorizer;
        });

        $app->alias('oauth2-server.authorizer', Authorizer::class);
    }

    /**
     * Register the Middleware to the IoC container because
     * some middleware need additional parameters.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    public function registerMiddlewareBindings(Application $app)
    {
        $app->singleton(CheckAuthCodeRequestMiddleware::class, function ($app) {
            return new CheckAuthCodeRequestMiddleware($app['oauth2-server.authorizer']);
        });

        $app->singleton(OAuthMiddleware::class, function ($app) {
            $httpHeadersOnly = $app['config']->get('oauth2.http_headers_only');

            return new OAuthMiddleware($app['oauth2-server.authorizer'], $httpHeadersOnly);
        });

        $app->singleton(OAuthClientOwnerMiddleware::class, function ($app) {
            return new OAuthClientOwnerMiddleware($app['oauth2-server.authorizer']);
        });

        $app->singleton(OAuthUserOwnerMiddleware::class, function ($app) {
            return new OAuthUserOwnerMiddleware($app['oauth2-server.authorizer']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     * @codeCoverageIgnore
     */
    public function provides()
    {
        return ['oauth2-server.authorizer'];
    }
}
