<?php
namespace modmore\Commerce_Cursus;

use Commerce;
use comTransaction;
use modmore\Commerce\Events\Checkout;
use modmore\Commerce\Events\OrderState;
use modmore\Commerce\Modules\BaseModule;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class Module extends BaseModule {

    public function getName()
    {
        $this->adapter->loadLexicon('commerce_cursus:default');
        return $this->adapter->lexicon('commerce_cursus');
    }

    public function getAuthor()
    {
        return 'modmore';
    }

    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_cursus.description');
    }

    public function initialize(EventDispatcher $dispatcher)
    {
        // Load our lexicon
        $this->adapter->loadLexicon('commerce_cursus:default');

        // Make sure Cursus is installed and running
        $corePath = $this->adapter->getOption('agenda.core_path', null, $this->adapter->getOption('core_path') . 'components/agenda/');
        $agenda = $this->adapter->getService('agenda', 'Agenda', $corePath . 'model/agenda/', [
            'core_path' => $corePath
        ]);
        if (!$agenda) {
            throw new \RuntimeException('Agenda is not installed or cannot be loaded.');
        }
        if (!isset($agenda->cursus) || !$agenda->cursus) {
            throw new \RuntimeException('Cursus is not installed or cannot be loaded.');
        }

        /**
         * When the order progresses from cart to processing, we mark any associated
         * participants as booked. This expects an array of participant IDs in the order items.
         */
        $dispatcher->addListener(
            Commerce::EVENT_STATE_CART_TO_PROCESSING,
            function (OrderState $event) {
                $order = $event->getOrder();
                $items = $order->getItems();
                foreach ($items as $item) {
                    $participants = $item->getProperty('cursus_participants');
                    if (empty($participants) || !is_array($participants)) {
                        continue;
                    }

                    foreach ($participants as $participantId) {
                        $this->markParticipantBooked($order, $participantId);
                    }
                }
            }
        );


        /**
         * During the checkout, after a step is done with its processing, check if the
         * participant records are still valid. If they expired, they will be removed.
         */
        $dispatcher->addListener(
            Commerce::EVENT_CHECKOUT_AFTER_STEP,
            function (Checkout $event) {
                // Skip checking if the order has started the payment process
                $order = $event->getOrder();
                $checkStatus = !$order->isPaid() && $order->getState() === \comOrder::STATE_CART;
                $transactions = $order->getTransactions();
                foreach ($transactions as $transaction) {
                    $status = $transaction->get('status');
                    if ($status >= comTransaction::STATUS_NEW) {
                        $checkStatus = false;
                    }
                }
                if (!$checkStatus) {
                    return;
                }

                $items = $order->getItems();
                foreach ($items as $item) {
                    $participants = $item->getProperty('cursus_participants');
                    if (empty($participants) || !is_array($participants)) {
                        continue;
                    }

                    foreach ($participants as $participantId) {
                        if (!$this->isParticipantValid($order, $participantId)) {
                            $response = $event->getResponse();
                            $response->addError('Your participant reservation expired.'); // @todo lexicon
                            $response->setRedirect('cart');
                            $order->removeItem($item);
                        }
                    }
                }

            }
        );
    }

    public function getModuleConfiguration(\comModule $module)
    {
        $fields = [];

        // A more detailed description to be shown in the module configuration. Note that the module description
        // ({@see self:getDescription}) is automatically shown as well.
//        $fields[] = new DescriptionField($this->commerce, [
//            'description' => $this->adapter->lexicon('commerce_cursus.module_description'),
//        ]);

        return $fields;
    }

    private function markParticipantBooked(\comOrder $order, int $participantId): void
    {
        $participant = $this->adapter->getObject(\CursusEventParticipants::class, [
            'id' => $participantId,
        ]);
        if (!$participant) {
            $order->log('Failed marking Cursus Participant #' . $participantId . ' as paid, object not loaded.');
            return;
        }

        $participant->set('paid', true);
        $participant->set('status', 'booked'); // @todo verify this is correct
        $participant->save();
        $order->log('Marked Cursus Participant #' . $participantId . ' as paid and booked');
    }

    private function isParticipantValid(\comOrder $order, int $participantId): bool
    {
        $participant = $this->adapter->getObject(\CursusEventParticipants::class, [
            'id' => $participantId,
        ]);
        if (!$participant) {
            return false;
        }

        // @todo check an expiration date on the EventParticipant model, TBA to Cursus

        $event = $this->adapter->getObject(\CursusEvents::class, [$participant->get('event_id')]);
        if (!$event) {
            $participant->remove();
            return false;
        }

        // Check the latest by which a registration must be completed
        if (time() < $event->get('latest_registration')) {
            $participant->remove();
            return false;
        }

        // @todo Check the event isn't overbooked (max_participants < count(participants))

        // @todo extend the expiration date if it expires soon and there is room

        return true;
    }
}
