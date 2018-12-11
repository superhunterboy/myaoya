<?php

// "sonata-project/google-authenticator": "dev-master"

require_once __DIR__ . '/../vendor/autoload.php';

$g = new \Google\Authenticator\GoogleAuthenticator();

$secret = 'HBE7FJA2UHXQ52AV';
// $secret = $g->generateSecret();
echo "Get a new Secret: $secret \n";

echo "The QR Code for this secret (to scan with the Google Authenticator App: \n";
echo \Google\Authenticator\GoogleQrUrl::generate('silen', $secret, 'GoogleAuthenticator');

echo "\n";

$code = '807368';
// $code = $g->getCode($secret);
echo 'Current Code is: ' . $code . "\n";

echo "Check if $code is valid: ";
if ($g->checkCode($secret, $code)) {
    echo "YES \n";
} else {
    echo "NO \n";
}
