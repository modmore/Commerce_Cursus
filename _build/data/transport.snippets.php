<?php

/** @var modX $modx */
/** @var array $sources */

$snippetSource = include __DIR__ . '/snippets.php';

$snippets = [];

/**
 * Loop over snippet stuff to interpret the xtype and to create the modSnippet object for the package.
 */
foreach ($snippetSource as $name => $options) {
    /** @var modSnippet */
    $snippet = $modx->newObject('modSnippet');
    $snippet->fromArray([
        'name' => $name,
        'description' => $options['description'],
        'snippet' => getSnippetContent($sources['snippets'] . $options['file']),
    ], '', true, true);
    $snippets[] = $snippet;
}
unset($snippetSource, $snippet, $events);

return $snippets;
