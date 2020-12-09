# whmcs-gateway-omise
Omise Payment Gateway support for WHMCS

## Installation
Clone the repository (with submodules)

`git clone --recursive https://github.com/sfalpha/whmcs-gateway-omise.git`

Copy modules files inside modules/gateway to WHMCS installation

`cp -rf whmcs-gateway-omise/modules/gateway/* /PATH/TO/WHMCS/modules/gateway/`

Enable gateway modules in WHMCS and config the Public and Secret Key

## Payment Methods
You may need to config **defaultPaymentMethod** and **otherPaymentMethods**
by edit **modules/gateway/omise.php** if you are not in Thailand or want to
change payment methods.
