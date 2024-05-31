<?php

declare(strict_types=1);

use SUDHAUS7\Sudhaus7Wizard\Controller\Backend\CreateNewTaskAjaxController;

return [
    's7wizardGetTemplates' => [
        'path' => '/s7wizard/getTemplates',
        'method' => ['GET'],
        'target' => CreateNewTaskAjaxController::class . '::getTemplatesAction',
    ],
    's7wizardGetGeneralFields' => [
        'path' => '/s7wizard/getGeneralFields',
        'method' => ['GET'],
        'target' => CreateNewTaskAjaxController::class . '::getGeneralFieldsAction',
    ],
    's7wizardGetEditorFields' => [
        'path' => '/s7wizard/getEditorFields',
        'method' => ['GET'],
        'target' => CreateNewTaskAjaxController::class . '::getEditorFieldsAction',
    ],
];
