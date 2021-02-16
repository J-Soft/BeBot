<?php
require "discord_curl.php";

if (!defined("DISCORD_API"))
    define("DISCORD_API", "https://discordapp.com/api/v6");


/**
 * Creates a GET request to Discord API using the Bearer authentication mode.
 * See discord_get for Bot authentication mode.
 */
function discord_oauth_get($route, $accessToken) 
{
    return discord_http("GET", DISCORD_API . $route, [ 'Authorization: Bearer ' . $accessToken ], null);
}

/**
 * Exchanges the oauth code for bearers token
 */
function discord_oauth_exchange($clientID, $clientSecret, $scope, $redirect,  $code)
{
    $data = array(
        'client_id' => $clientID,
        'client_secret' => $clientSecret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect,
        'scope' => $scope
    );

    $header = [ 'Content-Type: application/x-www-form-urlencoded' ];
    $query = http_build_query($data);
    $result = discord_http("POST", DISCORD_API . "/oauth2/token", $header, $query);
    return $result;
}

/**
 * Sets the location header to the discord authorization screen and exits. 
 * This will cause the user to be redirected to discord to accept the oauth.
 */
function discord_oauth_redirect($clientID, $scope, $redirect, $no_prompt = true)
{ 
    $authurl = DISCORD_API . "/oauth2/authorize";
    $query = http_build_query(array(
        "response_type" => "code",
        "client_id" => $clientID,
        "scope" => $scope,
        "redirect_uri" => $redirect
    ));
    header("location: {$authurl}?{$query}" . ($no_prompt ? "&prompt=none" : ""));
    exit;
}