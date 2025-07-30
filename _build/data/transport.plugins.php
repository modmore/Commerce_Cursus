<?php

/** @var modX $modx */
/** @var array $sources */

$pluginSource = include __DIR__ . '/plugins.php';

$plugins = [];

/**
 * Loop over plugin stuff to interpret the xtype and to create the modPlugin object for the package.
 */
foreach ($pluginSource as $name => $options) {
    /** @var modPlugin */
    $plugin = $modx->newObject('modPlugin');
    $plugin->fromArray([
        'name' => $name,
        'description' => $options['description'],
        'plugincode' => getSnippetContent($sources['plugins'] . $options['file']),
        'disabled' => (bool)$options['disabled'] ?? 0,
    ], '', true, true);

    $events = [];
    foreach ($options['events'] as $e) {
        $events[$e] = $modx->newObject('modPluginEvent');
        $events[$e]->fromArray([
            'event' => $e,
            'priority' => 0,
            'propertyset' => 0,
        ], '', true, true);
    }
    $plugin->addMany($events);
    $plugins[] = $plugin;
}
unset($pluginSource, $plugin, $events);

return $plugins;
