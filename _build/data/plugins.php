<?php

return [
    'CommerceCursusDemo' => [
        'description' => 'Cursus for Commerce Demo Plugin that runs on the OnAgendaBeforeRemove and the OnAgendaSave event to add and remove Commerce products when an Agenda event is created or deleted.',
        'file' =>  'commercecursusdemo.plugin.php',
        'disabled' => true,
        'events' => [
            'OnAgendaBeforeRemove',
            'OnAgendaSave'
        ],
    ]
];
