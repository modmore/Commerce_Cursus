<?php
/**
 * Cursus for Commerce Demo Cart Order Prepare Hook
 *
 * @package commerce_cursus
 * @subpackage hook
 */

namespace modmore\Commerce_Cursus\Snippets;

use Agenda;
use Commerce;
use comOrder;
use comOrderItem;
use Cursus;
use CursusEventParticipants;
use CursusEvents;
use CursusParticipantProfiles;
use CursusParticipants;
use DateTime;
use MODX\Revolution\modResource;
use TreehillStudio\Cursus\Helper\Parse;
use xPDO;

class CommerceCursusDemoCartOrderPrepareHook extends Hook
{
    /**
     * Execute the hook and return success.
     *
     * @return bool
     * @throws /Exception
     */
    public function execute()
    {
        // Load Commerce
        $path = $this->modx->getOption('commerce.core_path', null, MODX_CORE_PATH . 'components/commerce/') . 'model/commerce/';
        /** @var Commerce|null $commerce */
        $commerce = $this->modx->getService('commerce', 'Commerce', $path, ['mode' => $this->modx->getOption('commerce.mode')]);
        if (!($commerce instanceof Commerce)) {
            die('<pre>Commerce not installed.</pre>');
        }

        // Load Agenda/Cursus
        $path = $this->modx->getOption('agenda.core_path', null, $this->modx->getOption('core_path') . 'components/agenda/');
        /** @var Agenda $agenda */
        $agenda = $this->modx->getService('agenda', 'Agenda', $path . 'model/agenda/', [
            'core_path' => $path
        ]);
        /** @var Cursus $cursus */
        $cursus = &$agenda->cursus;

        // Prepare the event values
        $values = $this->hook->getValues();
        unset($values[$this->hook->formit->config['submitVar']]);

        // Load cursus classes
        if (!$this->modx->loadClass('cursus.CursusEvents', $cursus->getOption('modelPath'))) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load CursusEvents class!');
            return false;
        }
        if (!$this->modx->loadClass('cursus.CursusParticipants', $cursus->getOption('modelPath'))) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load CursusParticipants class!');
            return false;
        }
        if (!$this->modx->loadClass('cursus.CursusParticipantProfiles', $cursus->getOption('modelPath'))) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load CursusParticipantProfiles class!');
            return false;
        }
        if (!$this->modx->loadClass('cursus.CursusEventParticipants', $cursus->getOption('modelPath'))) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load CursusEventParticipants class!');
            return false;
        }

        $eventClass = new CursusEvents($this->modx);
        $eventsOptions = [
            'event_id' => $values['cursus'],
            'published' => true,
        ];
        $event = $eventClass->getEvent($eventsOptions);
        if ($event) {
            $eventArray = $event->toExtendedArray();
            $eventArray = Parse::flattenArray($eventArray, '_');
        } else {
            $this->hook->addError('message', $this->modx->lexicon('commerce_cursus.err_event_not_found'));
            return false;
        }

        /** @var modResource $eventResource */
        // If you need to access the resource or template variables
        $eventResource = $this->modx->getObject('modResource', $eventArray['event_resource_id']);

        $order = comOrder::loadUserOrder($commerce);
        // (Optional) If we have a processing order, forget about it and get a new one
        if ($order->getState() !== comOrder::STATE_CART) {
            $order->forgetOrderId();
            $order = comOrder::loadUserOrder($commerce);
        }
        // (Optional) We want the cart to be empty first
        $orderItems = $order->getItems();
        foreach ($orderItems as $orderItem) {
            $order->removeItem($orderItem);
        }

        if (!$eventArray) {
            $this->hook->addError('message', $this->modx->lexicon('commerce_cursus.err_event_not_found'));
            return false;
        }

        // Create the participant and combine it with the event
        $cursusParticipantProfile = $this->modx->getObject('CursusParticipantProfiles', [
            'firstname' => $values['firstname'],
            'lastname' => $values['lastname'],
            'birthdate' => $values['birthdate'],
            'address' => $values['address'],
            'zip' => $values['zip'],
            'city' => $values['city'],
            'phone' => $values['phone'],
            'extended' => json_encode([])
        ]);
        if (!$cursusParticipantProfile) {
            /** @var CursusParticipants $cursusParticipant */
            $cursusParticipant = $this->modx->newObject('CursusParticipants');
            $cursusParticipant->set('name', $this->modx->filterPathSegment(strtolower($values['firstname'] . '_' . $values['lastname'] . '_' . $values['city']), [
                'friendly_alias_word_delimiter' => '_',
            ]));
            if (!$cursusParticipant->save()) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Can\'t save the participant!');
                $this->hook->addError('message', $this->modx->lexicon('commerce_cursus.err_participant_save'));
                return false;
            }
            /** @var CursusParticipantProfiles $cursusParticipantProfile */
            $cursusParticipantProfile = $this->modx->newObject('CursusParticipantProfiles');
            $cursusParticipantProfile->fromArray([
                'internalKey' => $cursusParticipant->get('id'),
                'firstname' => $values['firstname'],
                'lastname' => $values['lastname'],
                'birthdate' => $values['birthday'],
                'address' => $values['address'],
                'zip' => $values['zip'],
                'city' => $values['city'],
                'phone' => $values['phone'],
            ]);
            $cursusParticipantProfile->setExtendedFields([], false);
            if (!$cursusParticipantProfile->save()) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Can\'t save the participant profile!');
                $this->hook->addError('message', $this->modx->lexicon('commerce_cursus.err_participant_save'));
                return false;
            }
        } else {
            $cursusParticipant = $cursusParticipantProfile->getOne('Participant');
        }

        $validUntil = new DateTime('+15 minutes');
        /** @var CursusEventParticipants $cursusEventParticipant */
        $cursusEventParticipant = $this->modx->newObject('CursusEventParticipants');
        $cursusEventParticipant->fromArray([
            'event_id' => $eventArray['id'],
            'participant_id' => $cursusParticipant->get('id'),
            'status' => 'reserved',
            'validuntil' => $validUntil->getTimestamp(),
        ]);
        if (!$cursusEventParticipant->save()) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Can\'t save the event participant!');
            $this->hook->addError('message', $this->modx->lexicon('commerce_cursus.err_eventparticipant_save'));
            return false;
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Event participant ' . $cursusEventParticipant->get('id') . ' of ' . (($cursusParticipant) ? $cursusParticipant->get('name') : 'unknown') . ' created!' . "\n" . print_r($cursusEventParticipant->toArray(), true));
        }

        $participantId = $cursusEventParticipant->get('id');
        $participantName = $values['firstname'] . ' ' . $values['lastname'];
        $quantity = 1;

        // Create Order Item
        /** @var comOrderItem $item */
        $item = $commerce->adapter->newObject('comOrderItem');
        $item->set('currency', $order->get('currency'));
        $item->fromArray([
            'delivery_type' => 1,
            'tax_group' => 1,
            'product' => $eventArray['event_id'],
            'sku' => 'EVENT-' . $eventArray['id'],
            'name' => $eventArray['title'],
            'description' => $eventArray['range'],
            'is_manual_price' => true,
            'price' => $eventArray['price'],
            'quantity' => $quantity,
        ]);
        // These fields can be accessed in frontend/checkout/cart/items.twig with {{ item.properties.cursus_event_participants|e }} and {{ item.properties.participant_names|e }}
        $item->setProperty('cursus_event_participants', $participantId);
        $item->setProperty('participant_names', $participantName);
        $order->addItem($item);

        $cartResource = $this->modx->getOption('commerce.cart_resource');
        $cartUrl = $this->modx->makeUrl($cartResource);
        $this->modx->sendRedirect($cartUrl);

        return !$this->hook->hasErrors();
    }
}
