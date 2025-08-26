<?php

use W3code\W3cCategorymanager\Controller\CategoryModuleController;

return [
    'w3c-categorymanager_categorymodule_togglehide' => [
        'path' => '/w3c-categorymanager/categorymodule/togglehide',
        'target' => CategoryModuleController::class . '::toggleHideAction',
    ],
];
