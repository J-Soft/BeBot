<?php

    /*
    This example shows how one would use a bot that is in the server to access some information or award a role
   
    https://discordapp.com/developers/docs/resources/guild
    */

    require "discord_curl.php";

    $guildID = "81384788765712384";       //ID of the guild the role is in
    $roleID = "454876643257876480";        //ID of the role to award
    $userID = "130973321683533824";        //ID of the user we are going to award the role too
    $botToken = file_get_contents("bot.key");   //The secret and SENSITIVE bot token.

    //Send the PUT request, updating the user to the new award url
    $route = "/guilds/{$guildID}/members/{$userID}/roles/{$roleID}";
    $result = discord_put($route, $botToken, null);

    //Dump the result
    echo "<h3>Result</h3>"; var_dump($result); "<hr>";

    //Get the users new roles
    $route = "/guilds/{$guildID}/members/{$userID}";
    $result = discord_get($route, $botToken);
    
    //Dump the result
    echo "<h3>Guild Member</h3>"; var_dump($result); "<hr>";

    exit;