- Remove `LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider` from your config/app.php      
- update config file
- set response_type in config
- issueAccessToken now requires a `Psr\Http\Message\ServerRequestInterface`
    
    
    Route::post('oauth/token', function (\Psr\Http\Message\ServerRequestInterface $request) {
        return Authorizer::issueAccessToken($request);
    })
    
- Create a UserStorageRepository to return user entities
- Implement UserEntityInterface in your user model
- Make sure APP_KEY is set!!