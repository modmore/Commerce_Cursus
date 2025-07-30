<?php
/**
 * Cursus for Commerce Plugin
 *
 * @package commerce_cursus
 * @subpackage plugin
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$className = 'modmore\Commerce_Cursus\Plugins\DemoEvents\\' . $modx->event->name;

$corePath = $modx->getOption('commerce_cursus.core_path', null, $modx->getOption('core_path') . 'components/commerce_cursus/');
require_once $corePath . '/vendor/autoload.php';

if (class_exists($className)) {
    $handler = new $className($modx, $scriptProperties);
    if (get_class($handler) == $className) {
        $handler->run();
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, $className . ' could not be initialized!', '', 'Cursus for Commerce Plugin');
    }
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR, $className . ' was not found!', '', 'Cursus for Commerce Plugin');
}

return;
