<?php
// AO
$server_list['Ao']['Testlive'] = array('server' => 'chat.dt.funcom.com', 'port' => 7109);
$server_list['Ao']['Rubi-Ka'] = array('server' => 'chat.d1.funcom.com', 'port' => 7105);
$server_list['Ao']['Rubi-Ka-2019'] = array('server' => 'chat.d1.funcom.com', 'port' => 7106);
$server_list['Ao']['0'] = $server_list['Ao']['Testlive'];
$server_list['Ao']['5'] = $server_list['Ao']['Rubi-Ka'];
$server_list['Ao']['6'] = $server_list['Ao']['Rubi-Ka-2019'];

// AOC
$server_list['Aoc']['EU'] = array(
    'server' => 'dm10-nj4.ageofconan.com',
    'port' => 7000
);
$server_list['Aoc']['US'] = array(
    'server' => 'aoc-us-um.live.ageofconan.com',
    'port' => 7010
);
$server_list['Aoc']['Aoctestlive'] = array(
    'server' => 'testlive.ageofconan.com',
    'port' => 7010
);

// all EU servers use the same login server
 $server_list['Aoc']['Bloodbrand']  = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Ahriman']   = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Bori']   = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Dagon']   = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Ymir']   = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Hyrkania']  = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Aquilonia']  = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Twilight']  = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Corinthia']  = $server_list['Aoc']['EU'];

 // Merged Servers
 $server_list['Aoc']['Crom']             = $server_list['Aoc']['US'];
 $server_list['Aoc']['Fury']             = $server_list['Aoc']['US'];


 // Spanish Servers
 $server_list['Aoc']['Zingara']      = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Indomitus']    = $server_list['Aoc']['EU'];

 // French Servers
 $server_list['Aoc']['Ishtar']       = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Ferox']        = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Stygia']       = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Strix']        = $server_list['Aoc']['EU'];

 // German Servers
 $server_list['Aoc']['Asura']        = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Ibis']         = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Aries']        = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Titus']        = $server_list['Aoc']['EU'];
 $server_list['Aoc']['Asgard']       = $server_list['Aoc']['EU'];

 // all US servers use the same login server
 $server_list['Aoc']['Gwahlur']      = $server_list['Aoc']['US'];
 $server_list['Aoc']['Wiccana']      = $server_list['Aoc']['US'];
 $server_list['Aoc']['Bloodspire']   = $server_list['Aoc']['US'];
 $server_list['Aoc']['Ironspine']    = $server_list['Aoc']['US'];
 $server_list['Aoc']['Tyranny']      = $server_list['Aoc']['US'];
 $server_list['Aoc']['Cimmeria']     = $server_list['Aoc']['US'];
 $server_list['Aoc']['Agony']        = $server_list['Aoc']['US'];

?>
