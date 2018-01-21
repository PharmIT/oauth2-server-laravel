WIP Upgrade guide:

- Remove `LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider` from your config/app.php      
- Run the additional database migration, this will invalidate all your access tokens and refresh tokens
- Update your oauth2 config file with additional config params. (TODO: specify which ones)
- Make sure APP_KEY is set to a secure random key in your .env file
- set response_type in config
- Create a UserStorageRepository to return user entities
- Implement UserEntityInterface in your user model

Changes:
- Authorizer::validateAccessToken() only takes request objects now
- The exceptions thrown by the library have changed, they now are either `\League\Oauth2\Server\Exception` or `\Illuminate\Auth\Exception\AuthorizationException`