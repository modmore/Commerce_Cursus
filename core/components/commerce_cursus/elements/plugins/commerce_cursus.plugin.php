<?php
/**
 * Commerce Cursus Plugin
 *
 * @package commerce_cursus
 * @subpackage plugin
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$className = 'modmore\Commerce_Cursus\Plugins\Events\\' . $modx->event->name;

$corePath = $modx->getOption('commerce_cursus.core_path', null, $modx->getOption('core_path') . 'components/commerce_cursus/');
/** @var Commerce_Cursus $commerce_cursus */
$commerce_cursus = $modx->getService('commerce_cursus', 'Commerce_Cursus', $corePath . 'model/commerce_cursus/', [
    'core_path' => $corePath
]);

if ($commerce_cursus) {
    if (class_exists($className)) {
        $handler = new $className($modx, $scriptProperties);
        if (get_class($handler) == $className) {
            $handler->run();
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' could not be initialized!', '', 'Commerce_Cursus Plugin');
        }
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' was not found!', '', 'Commerce_Cursus Plugin');
    }
}

return;
