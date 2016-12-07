<?php
    /**
     * Adapted from: http://isometriks.com/verify-github-webhooks-with-php
     **/

    // Update with the appropriate secret, and rename so this file wont get overwritten
    $secret = '';

    $hubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE'];

    list($algo, $hash) = explode('=', $hubSignature, 2);

    $payload = file_get_contents('php://input');

    $payloadHash = hash_hmac($algo, $payload, $secret);

    if ($hash !== $payloadHash) {
        die('Bad secret');
    }

    // You must have previously checked out the SVN. Example:
    // svn checkout https://github.com/evreichard/evanreichard.com/trunk/_site evanreichard_com
    exec("svn update");
?>
