<?php
/**
 * CommerceCursus Module
 *
 * @package commerce_cursus
 * @subpackage module
 */

namespace modmore\Commerce_Cursus;

use Commerce;
use comOrder;
use comTransaction;
use CursusEventParticipants;
use modmore\Commerce\Events\Admin\OrderItemDetail;
use modmore\Commerce\Events\Checkout;
use modmore\Commerce\Events\OrderState;
use modmore\Commerce\Modules\BaseModule;
use Symfony\Component\EventDispatcher\EventDispatcher;
use xPDO;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class Module extends BaseModule
{
    /**
     * {@inheritDoc}
     * @return mixed|string|null
     */
    public function getName()
    {
        $this->adapter->loadLexicon('commerce_cursus:default');
        return $this->adapter->lexicon('commerce_cursus');
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function getAuthor()
    {
        return 'modmore &amp; Treehill Studio';
    }

    /**
     * {@inheritDoc}
     * @return mixed|string|null
     */
    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_cursus.description');
    }

    /**
     * {@inheritDoc}
     * @param EventDispatcher $dispatcher
     * @return void
     */
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
        $cursus = $agenda->cursus;
        if (!$cursus) {
            throw new \RuntimeException('Cursus is not installed or cannot be loaded.');
        }

        $dispatcher->addListener(Commerce::EVENT_STATE_CART_TO_PROCESSING, array($this, 'bookParticipant'));
        $dispatcher->addListener(Commerce::EVENT_CHECKOUT_AFTER_STEP, array($this, 'checkReservationExpired'));
        $dispatcher->addListener(Commerce::EVENT_DASHBOARD_ORDER_ITEM_DETAIL, array($this, 'showOnDetailRow'));
    }

    /**
     * When the order progresses from cart to processing, we mark any associated
     * participants as booked. This expects the event participant ID in the order items.
     *
     * @param OrderState $event
     * @return void
     */
    public function bookParticipant(OrderState $event)
    {
        $order = $event->getOrder();
        $items = $order->getItems();
        foreach ($items as $item) {
            $eventParticipants = $item->getProperty('cursus_event_participants') ? explode(',', $item->getProperty('cursus_event_participants')) : [];
            if (empty($eventParticipants)) {
                continue;
            }

            foreach ($eventParticipants as $eventParticipant) {
                $this->markEventParticipantBooked($order, $eventParticipant);
            }
        }
    }

    /**
     * Mark a Cursus event participant as booked
     *
     * @param comOrder $order
     * @param int $eventParticipantId
     * @return void
     */
    private function markEventParticipantBooked(comOrder $order, int $eventParticipantId): void
    {
        $eventParticipant = $this->adapter->getObject(CursusEventParticipants::class, [
            'id' => $eventParticipantId,
        ]);
        if (!$eventParticipant) {
            $order->log('Failed marking Cursus Event Participant #' . $eventParticipantId . ' as paid, object not loaded.');
            return;
        }

        $eventParticipant->set('paid', true);
        $eventParticipant->set('status', 'booked');
        $eventParticipant->set('validuntil');
        $eventParticipant->save();

        $participant = $eventParticipant->getOne('Participant');
        $participantProfile = $participant->getOne('Profile');
        $address = $order->getBillingAddress();
        if ($participantProfile && $address) {
            $participantProfile->set('email', $address->get('email'));
            $participantProfile->save();
            $this->commerce->modx->invokeEvent('OnCursusEventParticipantBooked', [
                'order' => &$order,
                'address' => &$address,
            ]);
        } else {
            $this->adapter->log(xPDO::LOG_LEVEL_ERROR, 'The participant profile or the billing address was not found!');
        }

        $order->log('Marked Cursus Event Participant #' . $eventParticipantId . ' as paid and booked');
    }

    /**
     * During the checkout, after a step is done with its processing, check if the
     * participant records are still valid. If they expired, they will be removed.
     *
     * @param Checkout $event
     * @return void
     */
    public function checkReservationExpired(Checkout $event)
    {
        // Skip checking if the order has started the payment process
        $order = $event->getOrder();
        $checkStatus = !$order->isPaid() && $order->getState() === comOrder::STATE_CART;
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
            $eventParticipants = $item->getProperty('cursus_event_participants') ? explode(',', $item->getProperty('cursus_event_participants')) : [];
            if (empty($eventParticipants)) {
                continue;
            }

            foreach ($eventParticipants as $eventParticipant) {
                if (!$this->isEventParticipantValid($order, $eventParticipant)) {
                    $response = $event->getResponse();
                    $response->addError($this->adapter->lexicon('commerce_cursus.err_reservation_expired'));
                    $response->setRedirect('cart');
                    $order->removeItem($item);
                }
            }
        }
    }

    /**
     * Check if the event participant record is still valid
     *
     * @param comOrder $order
     * @param int $eventParticipantId
     * @return bool
     */
    private function isEventParticipantValid(comOrder $order, int $eventParticipantId): bool
    {
        $eventParticipant = $this->adapter->getObject(CursusEventParticipants::class, [
            'id' => $eventParticipantId,
        ]);
        if (!$eventParticipant) {
            return false;
        }

        // Check if the event participant reservation is valid
        if ($eventParticipant->get('validuntil') < time() && $eventParticipant->get('status') === 'reserved') {
            $eventParticipant->remove();
            return false;
        }

        return true;
    }

    /**
     * Add Participant names to the order item detail
     *
     * @param OrderItemDetail $event
     * @return void
     */
    public function showOnDetailRow(OrderItemDetail $event)
    {
        $item = $event->getItem();
        $prop = $item->getProperty('participant_names');
        $event->addRow('<p>' . $prop . '</p>');
    }

    /**
     * {@inheritDoc}
     * @param \comModule $module
     * @return array
     */
    public function getModuleConfiguration(\comModule $module)
    {
        return [];
    }
}
