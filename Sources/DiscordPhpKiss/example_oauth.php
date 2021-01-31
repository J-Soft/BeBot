<?php

    /*
    This example shows how one would authorize a user with Discord via the oAuth2 flow and request their information
    using the token. There are logs to show exactly the information that is generated.
    
    https://discordapp.com/developers/docs/topics/oauth2
    */

    require "discord_oauth.php";

    $redirectURL = "http://127.0.0.1:81/discord/discord-php-kiss/example_oauth.php";    //The URL that the user will be redirect back too
    $clientID = "439410995987742720";                                       //The ID of the client
    $clientSecret = file_get_contents("client.key");                        //The sensitive and secret key of the client
    $scope = "identify email guilds";                                              //Space delimered scope

    //If we do not have a code, then we will redirect them
    // The exit; is not required, but its a good explicit practice.
    if (!isset($_GET['code'])) {
        discord_oauth_redirect($clientID, $scope, $redirectURL, true);
        exit;
    }

    //Exchange the token
    $auth = discord_oauth_exchange($clientID, $clientSecret, $scope, $redirectURL, $_GET['code']);
    echo "<h3>Auth Response:</h3>"; var_dump($auth);  echo "<hr>";

    if ($auth == null || !empty($auth['error'])) 
        die("Failed: Authorization was bad: " . $auth['error']);

    //Get the user
    $user = discord_oauth_get("/users/@me", $auth['access_token']);
    echo "<h3>User Response:</h3>"; var_dump($user);  echo "<hr>";

    if ($user == null || !empty($user['message']))
        die("Failed: /users/@me threw a error: " . $user['message']);
	
	//Get the guilds
    $guilds = discord_oauth_get("/users/@me/guilds", $auth['access_token']);
    echo "<h3>Guilds Response:</h3>"; var_dump($guilds);  echo "<hr>";

    if ($guilds == null || !empty($guilds['message']))
        die("Failed: /users/@me/guilds threw a error: " . $guilds['message']);

    //Display the username
    echo "Welcome " . $user['username'];
    exit;