Cursus integration for Commerce
-------------------------------

This package assists connecting Cursus with Commerce.

## Usage

Install the package and enable the module in Commerce > Configuration > Modules.

## Technical Implementation

Create (in a hook or something) the CursusEventParticipant records, [create an order item that you add to the cart](https://forum.modmore.com/t/extending-comorderitem-adding-custom-products-to-cart/1187/5?u=mhamstra), and add an array of participant IDs in the comOrderItem properties with key `cursus_participants`.

The module will parse the `cursus_event_participants` field when the order is moved to the Processing state.

The module will also make sure the reserved participant record is still valid during the checkout.

There is an example hook installed with the package called CommerceCursusDemoCartOrderPrepare that can be used as FormIt hook. Feel free to extend it to suit your needs.

By default, the CommerceCursusDemo plugin is disabled, but it can be used to add and remove Commerce products when an Agenda event is created or deleted. Feel free to extend it to suit your needs. 

## Building

To run locally, clone the repository and create a config.core.php in the project root that points to your MODX installation. See the config.core.sample.php for an example.

On the command line, navigate to core/components/commerce_cursus/ and run `composer install` to set-up the autoloader.

On the command line, run `php _bootstrap/index.php` to set up the required settings and module record in Commerce.

To build a new release, edit `_build/build.transport.php` to change the version number (not required if building for modmore.com distribution) and run `php _build/build.transport.php` from the project root.


