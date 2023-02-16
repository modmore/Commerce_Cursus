Cursus integration for Commerce
-------------------------------

This package assists connecting Cursus with Commerce.

## Usage

Install the package and enable the module in Commerce > Configuration > Modules.

## Technical Implementation

Create (in a hook or something) the CursusEventParticipant records, [create an order item that you add to the cart](https://forum.modmore.com/t/extending-comorderitem-adding-custom-products-to-cart/1187/5?u=mhamstra), and add an array of participant IDs in the comOrderItem properties with key `cursus_participants`.

For example:

````php
<?php
use modmore\Commerce\Frontend\Checkout\Standard;
use modmore\Commerce\Frontend\Steps\Cart;

// Instantiate the Commerce class
$path = $modx->getOption('commerce.core_path', null, MODX_CORE_PATH . 'components/commerce/') . 'model/commerce/';
$params = ['mode' => $modx->getOption('commerce.mode')];
/** @var Commerce|null $commerce */
$commerce = $modx->getService('commerce', 'Commerce', $path, $params);
if (!($commerce instanceof Commerce)) {
    return '<p class="error">Oops! It is not possible to view your cart currently. We\'re sorry for the inconvenience. Please try again later.</p>';
}

if ($commerce->isDisabled()) {
    return $commerce->adapter->lexicon('commerce.mode.disabled.message');
}

$ids = [];

// ..
// load cursus
// handle submission
// create records in commerce as unpaid reservation
$participant->save();
// keep id in a local array
$ids[] = $participant->get('id');

$order = \comOrder::loadUserOrder($commerce);

// Optionally, reset the cart if only one reservation can be taken per order
$orderItems = $order->getItems();
foreach ($orderItems as $orderItem) {
    $order->removeItem($orderItem);
}

// Create new item
$item = $commerce->adapter->newObject('comOrderItem');
$item->set('currency', $order->get('currency'));
$item->fromArray([
  'delivery_type' => $deliveryTypeId, // ID of a delivery type, must be set
  'tax_group' => $taxGroupId, // ID of a tax group, must be set
  'sku' => 'Product Code',
  'name' => 'Name',
  'description' => '...',
  'price' => 1334, // in cents!!
  'quantity' => 1,
]);
$item->setProperty('cursus_participants', $ids); // store IDs as an **array**

// add to order
$order->addItem($item);

// send user to cart
$cartResource = $modx->getOption('commerce.cart_resource');
$cartUrl = $modx->makeUrl($cartResource);
$modx->sendRedirect($cartUrl);

// ALTERNATIVELY, to skip the cart and go directly to the checkout
if ($order instanceof comSessionCartOrder) {
    $order = $order->createPersistedCartOrder();
}
$checkoutResource = $modx->getOption('commerce.checkout_resource');
$checkoutUrl = $modx->makeUrl($checkoutResource);
$modx->sendRedirect($checkoutUrl);
````

The module will parse the `cursus_participants` field when the order is moved to the Processing state.

The module will also make sure the reserved participant record is still valid during the checkout.

## Building

To run locally, clone the repository and create a config.core.php in the project root that points to your MODX installation. See the config.core.sample.php for an example.

On the command line, navigate to core/components/commerce_cursus/ and run `composer install` to set-up the autoloader.

On the command line, run `php _bootstrap/index.php` to set up the required settings and module record in Commerce.

To build a new release, edit `_build/build.transport.php` to change the version number (not required if building for modmore.com distribution) and run `php _build/build.transport.php` from the project root.


