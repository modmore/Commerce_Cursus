<?php
/**
 * Commerce Cursus Cart Order Prepare Hook
 *
 * @package commerce_cursus
 * @subpackage hook
 *
 * @var modX $modx
 * @var array $scriptProperties
 * @var fiHooks $hook
 */

use modmore\Commerce_Cursus\Snippets\CommerceCursusCartOrderPrepareHook;

$corePath = $modx->getOption('agenda.core_path', null, $modx->getOption('core_path') . 'components/agenda/');
/** @var Agenda $agenda */
$agenda = $modx->getService('agenda', 'Agenda', $corePath . 'model/agenda/', [
    'core_path' => $corePath
]);
/** @var Cursus $cursus */
$cursus = &$agenda->cursus;

$snippet = new CommerceCursusCartOrderPrepareHook($modx, $scriptProperties);
if ($snippet instanceof modmore\Commerce_Cursus\Snippets\CommerceCursusCartOrderPrepareHook) {
    return $snippet->execute();
}
return 'modmore\Commerce_Cursus\Snippets\CommerceCursusCartOrderPrepareHook class not found';
