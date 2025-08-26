<?php

return [
    'web_W3cCategoryManager' => [
        'parentIdentifier' => 'web', // sous le menu WEB
        'position' => ['after' => 'web_list'],
        'navigationComponent' => '',
        'navigationComponentId' => '',
        'inheritNavigationComponentFromMainModule' => 'false',
        'workspaces' => 'live',
        'identifier' => 'web_W3cCategoryManager',
        'access' => 'user',
        'path' => '/module/web/w3ccategorymanager',
        'labels' => 'LLL:EXT:w3c_categorymanager/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'W3cCategorymanager',
        'iconIdentifier' => 'mimetypes-x-sys_category',
        'controllerActions' => [
            'W3code\W3cCategorymanager\Controller\CategoryModuleController' => [
                'main'
            ],
        ],
    ],
];
