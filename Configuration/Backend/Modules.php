<?php

use W3code\W3cCategoryManager\Controller\AjaxModuleController;
use W3code\W3cCategoryManager\Controller\ModuleController;

return [
    'web_w3c_categorymanager' => [
        'parentIdentifier' => 'web',
        'position' => ['after' => 'web_list'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'identifier' => 'web_W3cCategoryManager',
        'path' => '/module/W3cCategoryManager',
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
