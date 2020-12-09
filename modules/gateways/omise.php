<?php
/**
 * WHMCS Omise Gateway Module
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

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function omise_MetaData()
{
    return array(
        'DisplayName' => 'Omise',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function omise_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Omise',
        ),
        'pkey' => array(
            'FriendlyName' => 'Public Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Omise public key here',
        ),
        'skey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Omise secret key here',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function omise_link($params)
{
    // Gateway Configuration Parameters
    $pkey = $params['pkey'];
    $skey = $params['skey'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $verificationHash = sha1($invoiceId . $amount . $pkey . $skey);

    $omiseJsUrl = 'https://cdn.omise.co/omise.js';

    $htmlOutput = '';
    $htmlOutput .= '<form id="checkoutForm" method="POST" action="' . $systemUrl . '/modules/gateways/' . $moduleName . '/charge.php">';
    $htmlOutput .= '<input type="hidden" name="charge" value="1">';
    $htmlOutput .= '<input type="hidden" name="invoiceId" value="' . urlencode($invoiceId) . '">';
    $htmlOutput .= '<input type="hidden" name="description" value="' . urlencode($description) . '">';
    $htmlOutput .= '<input type="hidden" name="amount" value="' . urlencode($amount) .'">';
    $htmlOutput .= '<input type="hidden" name="currency" value="' . urlencode($currencyCode) . '">';
    $htmlOutput .= '<input type="hidden" name="omiseToken">';
    $htmlOutput .= '<input type="hidden" name="omiseSource">';
    $htmlOutput .= '<input type="submit" id="checkoutButton" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    $htmlOutput .= '<script type="text/javascript" src="' . $omiseJsUrl . '"></script>';
    $htmlOutput .= '<script>';
    $htmlOutput .= 'OmiseCard.configure({ publicKey: ' . json_encode($pkey) . '});';
    $htmlOutput .= 'var button = document.querySelector("#checkoutButton");';
    $htmlOutput .= 'var form = document.querySelector("#checkoutForm");';
    $htmlOutput .= 'button.addEventListener("click", (event) => {
        event.preventDefault();
        OmiseCard.open({
            amount: ' . json_encode($amount * 100.0) . ',
            currency: ' . json_encode($currencyCode) . ',
            defaultPaymentMethod: "credit_card",
            otherPaymentMethods: ["internet_banking", "truemoney" ,"bill_payment_tesco_lotus" ,"installment"],
            onCreateTokenSuccess: (nonce) => {
                if (nonce.startsWith("tokn_")) {
                    form.omiseToken.value = nonce;
                } else {
                    form.omiseSource.value = nonce;
                };
                form.submit();
            }
        });
    });';
    $htmlOutput .= '</script>';

    return $htmlOutput;
}