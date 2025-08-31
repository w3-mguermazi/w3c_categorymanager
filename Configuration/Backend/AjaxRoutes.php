<?php

use W3code\W3cCategoryManager\Controller\AjaxModuleController;

return [
    'w3c_category_manager_toggle_hide' => [
        'path' => '/module/W3cCategoryManager/ToggleHide',
        'target' => AjaxModuleController::class . '::toggleHideAction',
    ],
    'w3c_category_manager_toggle_expand' => [
        'path' => '/module/W3cCategoryManager/ToggleExpand',
        'target' => AjaxModuleController::class . '::toggleExpandAction',
    ],
];
