#!/usr/bin/php
<?php

/*
* Script for controlling TP-LINK WR1043ND routers
* Author: Septi <septi@uscentral.eu>
*/

require_once(__DIR__ . '/lib/phpseclib1.0.16/Crypt/RSA.php');
require_once(__DIR__ . '/lib/phpseclib1.0.16/Math/BigInteger.php');

# CONFIG
$router_ip = '192.168.0.1';
$username = 'USERNAME_GOES_HERE';
$password = 'PASSWORD_GOES_HERE';
$cookie = '/tmp/cookie_router.txt';

# EXEC
$action = isset($argv[1]) ? $argv[1] : '';

# make sure we don't allow rogue actions
if(!in_array($action, array('restart', 'summary', 'clients'))) exitErr("Unknown action '{$action}'");

# authenticate, fetch stok key
$login_data = performRequest("stok=/login?form=login", array(
    "operation" => 'login',
    "username" => $username,
    "password"=> encryptPassword($password)
), $cookie);

$stok = $login_data['stok'];

if($action == 'summary') {
    
    $ret_data = performRequest("stok={$stok}/admin/status?form=all", array('operation' => 'read'), $cookie);
    $response = '';
    
    foreach($ret_data as $k => $v) {
        
        $parsed_v = is_array($v) ? json_encode($v) : $v;
        $response .= "{$k}:\t{$parsed_v}" . PHP_EOL;
    }
    
} elseif($action == 'restart') {
    
    $ret_data = performRequest("stok={$stok}/admin/system?form=reboot", array('operation' => 'write'), $cookie);
    $response = "Reboot time: {$ret_data['reboot_time']}s" . PHP_EOL;
} elseif($action == 'clients') {
    
    $ret_data = performRequest("stok={$stok}/admin/dhcps?form=client", array('operation' => 'load'), $cookie);
    $response = '';
    
    foreach($ret_data as $v) {
        
        $response .= "{$v['ipaddr']}\t{$v['macaddr']}\t{$v['name']}" . PHP_EOL;
    }
} else {
    
    exitErr("Action '{$action}' has no handler");
}

# CLEANUP

# logout
performRequest("stok={$stok}/admin/system?form=logout", array('operation' => 'write'), $cookie);
# remove cookie file
unlink($cookie);

echo $response;
exit(0);

function performRequest($path, $data, $cookie = '') {
    
    global $router_ip;
    
    $url = "http://{$router_ip}/cgi-bin/luci/;{$path}";
    
    $ch = curl_init();
    
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($cookie) {
        
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    }
    
    $result = curl_exec($ch);
    
    curl_close($ch);
    
    if(!$result) exitErr("Request '{$url}' returned no response");
    
    $response = json_decode($result, true);
    
    if(!$response || !is_array($response) || !isset($response['success'])) exitErr("Request '{$url}' returned an unknown format");
    
    if(!$response['success']) exitErr("Request '{$url}' was not successful");
    
    return isset($response['data']) ? $response['data'] : true;
}

function encryptPassword($password) {
    
    # fetch password public keys to encrypt password
    $login_keys = performRequest("stok=/login?form=login", array('operation' => 'read'));
    
    list($pubkey_n, $pubkey_e) = $login_keys['password'];

    # encrypt plain text password
    $rsa = new Crypt_RSA();
    $r = $rsa->loadkey(array('e' => new Math_BigInteger($pubkey_e, 16), 'n' => new Math_BigInteger($pubkey_n, 16)), CRYPT_RSA_PUBLIC_FORMAT_RAW);
    $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
    $ciphertext = $rsa->encrypt($password);
    return bin2hex($ciphertext);
}

function exitErr($msg) {
    
    echo "[ERROR] {$msg}. Exiting\n";
    exit(1);
}