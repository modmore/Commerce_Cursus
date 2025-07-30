<?php

/** @var modX $modx */

$settingSource = include __DIR__ . '/settings.php';

$settings = [];

/**
 * Loop over setting stuff to interpret the xtype and to create the modSystemSetting object for the package.
 */
foreach ($settingSource as $key => $options) {
    $val = $options['value'];

    if (isset($options['xtype'])) {
        $xtype = $options['xtype'];
    } elseif (is_int($val)) {
        $xtype = 'numberfield';
    } elseif (is_bool($val)) {
        $xtype = 'modx-combo-boolean';
    } else {
        $xtype = 'textfield';
    }

    /** @var modSystemSetting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray([
        'key' => 'scheduler.' . $key,
        'xtype' => $xtype,
        'value' => $options['value'],
        'namespace' => 'scheduler',
        'area' => $options['area'],
        'editedon' => time(),
    ], '', true, true);
    $settings[] = $setting;
}
unset($setting);

return $settings;
