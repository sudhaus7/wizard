<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardTemplateConfigInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class CreateNewTaskAjaxController
{
    private UriBuilder $uriBuilder;

    public function __construct(
        UriBuilder $uriBuilder
    ) {
        $this->uriBuilder = $uriBuilder;
    }

    public function getTemplatesAction(ServerRequestInterface $request): ResponseInterface
    {
        $templates = [];

        /** @var WizardProcessInterface $registeredTemplateExtention */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'] ?? [] as $registeredTemplateExtension) {
            /** @var WizardTemplateConfigInterface $configuration */
            $configuration = $registeredTemplateExtension::getWizardConfig();
            $templates[] = [
                'title' => $configuration->getDescription(),
                'value' => $configuration->getExtension(),
            ];
        }

        $success = count($templates) > 0;

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:sudhaus7_wizard/Resources/Private/Templates/Backend/GetTemplates.html');
        $view->assign('templates', $templates);

        $data = [
            'success' => $success,
            'templates' => $templates,
            'html' => $view->render(),
        ];

        return new JsonResponse($data);
    }

    public function getGeneralFieldsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:sudhaus7_wizard/Resources/Private/Templates/Backend/GeneralFields.html');
        $data = [
            'html' => $view->render(),
        ];

        return new JsonResponse($data);
    }

    public function getEditorFieldsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:sudhaus7_wizard/Resources/Private/Templates/Backend/EditorFields.html');
        $data = [
            'html' => $view->render(),
        ];

        return new JsonResponse($data);
    }

    public function getTemplateFieldsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename('EXT:sudhaus7_wizard/Resources/Private/Templates/Backend/TemplateFields.html');
        $data = [
            'html' => $view->render(),
        ];

        return new JsonResponse($data);
    }

    public function createNewTask(ServerRequestInterface $request): ResponseInterface
    {
        $wizard = $this->getParamsFromRequest($request)['wizard'] ?? [];
        $wizard['pid'] = 0;
        $newWizardIdentifier = StringUtility::getUniqueId('NEW');
        $data = [
            'tx_sudhaus7wizard_domain_model_creator' => [
                $newWizardIdentifier => $wizard,
            ],
        ];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        if ($dataHandler->errorLog !== []) {
            $returnData = [
                'success' => false,
                'error' => [],
            ];
            foreach ($dataHandler->errorLog as $errorLog) {
                $returnData['error'][] = $errorLog;
            }
            return new JsonResponse($returnData);
        }

        $newWizardTaskId = $dataHandler->substNEWwithIDs[$newWizardIdentifier];

        $redirectUri = $this->uriBuilder
            ->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'tt_address' => [
                            $newWizardTaskId => 'edit',
                        ],
                    ],
                ]
            );
        $returnData = [
            'success' => true,
            'redirectUrl' => (string)$redirectUri,
        ];
        return new JsonResponse($returnData);
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getParamsFromRequest(ServerRequestInterface $request): array
    {
        $postParams = $request->getParsedBody();

        try {
            $postArrayParams = match (gettype($postParams)) {
                'array' => $postParams,
                'object' => json_decode(json_encode($postParams) ?: '{}', true, 512, JSON_THROW_ON_ERROR),
                default => []
            };
        } catch (\JsonException $_) {
            $postArrayParams = [];
        }

        return $postArrayParams;
    }
}
