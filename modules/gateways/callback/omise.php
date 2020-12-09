<?php
/**
 * WHMCS Omise Gateway Module Callback
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

$gatewayModuleName = 'omise';

$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Log raw request for debugging
$data = file_get_contents('php://input');
$log = fopen(__DIR__ . '/../../../attachments/omise_webhook.log', 'a');
fputs($log, date('Y-m-d H:i:s') . "\n");
fputs($log, $data);
$event = json_decode($data, true);

if ($event['data']['object'] == 'charge') {
    $charge = $event['data'];
    $transactionId = $charge['id'];
    $invoiceId = $charge['metadata']['invoiceid'];
    $amount = $charge['amount'] / 100.0;

    $hash = $charge['metadata']['hash'];
    $verifyHash = sha1($invoiceId . $gatewayParams['pkey'] . $gatewayParams['skey']);

    $success = ($charge['status'] == 'successful');
    $transactionStatus = $success ? 'Success' : 'Failure';
    if ($hash != $verifyHash) {
        $transactionStatus = 'Hash Verification Failure';
        $success = false;
    }

    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
    checkCbTransId($transactionId);
    logTransaction($gatewayParams['name'], $charge, $transactionStatus);

    if ($success) {
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $amount,
            null,
            $gatewayModuleName
        );
    }
}

fclose($log);