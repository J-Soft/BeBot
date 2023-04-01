<?php
if (!defined("DISCORD_API"))
    define("DISCORD_API", "https://discord.com/api/v10");


/**
 * Sends a GET request to discord using the bot token
 */
function discord_get($route, $botToken) 
{
    return discord_http("GET", DISCORD_API . $route, [ 'Authorization: Bot ' . $botToken ], null);
}
    
/**
 * Sends a DELETE request to discord using the bot token
 */
function discord_delete($route, $botToken) 
{
    return discord_http("DELETE", DISCORD_API . $route, [ 'Authorization: Bot ' . $botToken ], null);
}

/**
 * Sends a PATCH request to discord using the bot token and json data.
 */
function discord_patch($route, $botToken, $data) 
{
    $json = json_encode($data);
    return discord_http("PATCH", DISCORD_API . $route, [ 'Authorization: Bot ' . $botToken, 'Content-Type: application/json' ], $json);
}

/**
 * Sends a POST request to discord using the bot token and json data.
 */
function discord_post($route, $botToken, $data) 
{    
    $json = json_encode($data);
    return discord_http("POST", DISCORD_API . $route, [ 'Authorization: Bot ' . $botToken, 'Content-Type: application/json' ], $json);
}

/**
 * Sends a PUT request to discord using the bot token and json data.
 */
function discord_put($route, $botToken, $data) 
{    
    $json = json_encode($data);
    return discord_http("PUT", DISCORD_API . $route, [ 'Authorization: Bot ' . $botToken, 'Content-Type: application/json' ], $json);
}
  
/**
 * Creates a cURL request to discord with specified method, uri, headers and data.
 */
function discord_http($method, $uri, $headers, $data) 
{
    //Make the request
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER,  $headers );
    $response = curl_exec($ch);
    $json = json_decode($response, true);
    return $json;
}
