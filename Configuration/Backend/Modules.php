<?php

use W3code\W3cCategoryManager\Controller\AjaxModuleController;
use W3code\W3cCategoryManager\Controller\ModuleController;

return [
    'content_w3ccategorymanager' => [
        'parent' => 'content',
        'position' => ['after' => 'content_list'],
        'workspaces' => 'live',
        'path' => '/module/content/w3ccategorymanager',
        'labels' => 'LLL:EXT:w3c_categorymanager/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'W3cCategoryManager',
        'iconIdentifier' => 'mimetypes-x-sys_category',
        'controllerActions' => [
            ModuleController::class => [
                'index',
            ],
            AjaxModuleController::class => [
                'toggleHide',
                'toggleExpand',
            ],
        ],
    ],
];
