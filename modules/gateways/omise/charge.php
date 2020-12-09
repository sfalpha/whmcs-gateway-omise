<?php
/**
 * WHMCS Omise Gateway Module Charge
 *
 * @copyright Copyright (c) Nettree Co., Ltd. 2020
 * @license MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions
 * of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/omise-php/lib/Omise.php';

$gatewayModuleName = 'omise';

$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$charge = $_POST['charge'];

$message = null;
$status = null;

if ($charge == '1') {
    // Charging Step, make actual charge using Omise API
    define('OMISE_API_VERSION', '2017-11-02');
    define('OMISE_PUBLIC_KEY', $gatewayParams['pkey']);
    define('OMISE_SECRET_KEY', $gatewayParams['skey']);
    $systemUrl = $gatewayParams['systemurl'];

    $invoiceId = $_POST['invoiceId'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $omiseToken = $_POST['omiseToken'];
    $omiseSource = $_POST['omiseSource'];

    // Check invoice id
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    // Hash for verify authenticity of payment callback
    $hash = sha1($invoiceId . $gatewayParams['pkey'] . $gatewayParams['skey']);

    try {
        if ($omiseToken) {
            $charge = OmiseCharge::create(array(
                'amount' => $amount * 100.0,
                'currency' => $currency,
                'card' => $omiseToken,
                'description' => $description,
                'metadata' => array('invoiceid' => $invoiceId, 'hash' => $hash),
            ));
        } else if ($omiseSource) {
            $charge = OmiseCharge::create(array(
                'amount' => $amount * 100.0,
                'currency' => $currency,
                'source' => $omiseSource,
                'description' => $description,
                'metadata' => array('invoiceid' => $invoiceId, 'hash' => $hash),
            ));
        } else {
             http_response_code(400);
            exit();
        }
        // Log raw response for debugging
        $log = fopen(__DIR__ . '/../../../attachments/omise_charge.log', 'a');
        fputs($log, print_r($charge, true));
        fclose($log);
        if ($charge['status'] == 'successful') {
            $message = "Payment Successful";
        } else {
            $message = "Payment Failure (" . $charge['failure_message'] . ')';
        }
    } catch (OmiseException $e) {
        $message = "Payment Error " . $e->getMessage();
    }
} else {
    http_response_code(400);
    exit();
}
?>
<html>
<head>
    <title>Omise Payment</title>
</head>
<body>
    <h1><?= $message ?></h1>
    [ <a href="<?= $systemUrl . '/viewinvoice.php?id=' . $invoiceId ?>">Return</a> ]
</body>
</html>