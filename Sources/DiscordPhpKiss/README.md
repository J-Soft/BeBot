# discord-php-kiss
A Keep It Simple Stupid approach of Discord and PHP.
This library is minimal and only has 2 files, one for requests with a bot token and the other for oauth2.

**This is not best practice!**

This is just a quick dirty example to get you started with implementing your own oAuth2 flow with Discord. It does work and meet all the API requirements, but it doesnt support ratelimits and is a pure functional style of PHP. 

## discord_curl.php
This is the core file that is required. It creates a simple wrapper around the cURL and provides a clearer way to create requests to the Discord API. 

The `example_bot.php` is an example on how to use this.

**functions**
* `discord_get($route, $botToken)` Sends a GET request
* `discord_delete($route, $botToken)` Sends a DELETE request
* `discord_patch($route, $botToken, $data)` Sends a PATCH request
* `discord_post($route, $botToken, $data)` Sends a POST request
* `discord_put($route, $botToken, $data)` Sends a PUT request
* `discord_http($method, $uri, $headers, $encodedData)` Sends a request with given method and absolute URI

Example: `discord_get("/guilds/81384788765712384/members/130973321683533824", $cfg['token'])`

## discord_oauth.php
This has some extra functions to help with oauth functionality. It requires `discord_curl.php` as it uses the base `discord_http` method to make its requests.

The `example_oauth.php` is a rudementry implementation of Discord oAuth2 and provides an example on how to use this.

**functions**
* `discord_oauth_get($route, $accessToken)` Creates a GET request to Discord API using the Bearer authentication mode.
* `discord_oauth_exchange($clientID, $clientSecret, $scope, $redirect,  $code)` Exchanges the code for a access_key.
* `discord_oauth_redirect($clientID, $scope, $redirect)` Sets the location header to the discord authorization screen and exits.

## Thats All Folks
A really down to earth no-nonsense library. It does not support ratelimiting, caching, gateway, Object Oriented design or any of the fancy things that a real library supports. For that I recommend [RestCord](https://github.com/restcord/restcord).
This "library" (more a snippet) is more for those who want a quick starting guide or plan to make a use-once website. I plan to use this as a reference to future people (hello!) on how to do oAuth2 on PHP when they have troubles.
