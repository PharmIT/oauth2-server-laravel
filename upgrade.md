Remove `LucaDegasperi\OAuth2Server\Storage\FluentStorageServiceProvider` from your config/app.php
        
- update config file
- set response_type in config
- issueAccessToken now requires a `Psr\Http\Message\ServerRequestInterface`
    
    
    Route::get('/token', function (ServerRequestInterface $request) {
        $authorizer->issueAccessToken($request)
    });
    
- 