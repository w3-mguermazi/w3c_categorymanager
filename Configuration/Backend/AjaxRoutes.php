<?php

use W3code\W3cCategoryManager\Controller\AjaxModuleController;

return [
    'w3c_categorymanager_toggle_hide' => [
        'path' => '/module/W3cCategoryManager/ToggleHide',
        'target' => AjaxModuleController::class . '::toggleHideAction',
    ],
    'w3c_categorymanager_toggle_expand' => [
        'path' => '/module/W3cCategoryManager/ToggleExpand',
        'target' => AjaxModuleController::class . '::toggleExpandAction',
    ],
    'w3c_categorymanager_move' => [
        'path' => '/w3c/W3cCategoryManager/move',
        'target' => AjaxModuleController::class . '::moveAction'
    ],
];
