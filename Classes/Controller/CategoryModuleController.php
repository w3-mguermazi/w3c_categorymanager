<?php
namespace W3code\W3cCategorymanager\Controller;

use Psr\Http\Message\ResponseInterface;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Page\PageRenderer;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
class CategoryModuleController extends ActionController
{
    private ModuleTemplateFactory $moduleTemplateFactory;

    private UriBuilder $backendUriBuilder;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        UriBuilder $backendUriBuilder,    
    )
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->backendUriBuilder = $backendUriBuilder;
    }

    public function mainAction(): ResponseInterface
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:w3c_categorymanager/Resources/Public/Css/module.css');
        $pageRenderer->addJsFile('EXT:w3c_categorymanager/Resources/Public/JavaScript/backend.js');

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $iconOn = str_replace(["\n", "\r"], '', $iconFactory->getIcon('actions-toggle-on', IconSize::SMALL)->render());
        $iconOff = str_replace(["\n", "\r"], '', $iconFactory->getIcon('actions-toggle-off', IconSize::SMALL)->render());


        $pid = (int)($this->request->getQueryParams()['id'] ?? 0);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle(LocalizationUtility::translate('module.title','w3c_categorymanager'));

        // Récupérer le composant de boutons
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Ajouter un bouton
        $returnUrl = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_W3cCategoryManager',
            ['id' => $pid, 'action' => 'main'],
        );
        $shortcutButton = $buttonBar->makeLinkButton()
            ->setTitle('Create new category')
            ->setIcon($iconFactory->getIcon('actions-document-new'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => ['sys_category' => [1 => 'new']],
                        'defVals' => ['sys_category' => ['pid' => $pid]],
                        'returnUrl' => $returnUrl
                    ]
                ));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT,2,);

        /* $dropDownButton = $buttonBar->makeDropDownButton()
            ->setLabel('Dropdown')
            ->setTitle('Save')
            ->setIcon($iconFactory->getIcon('actions-heart'))
            ->addItem(
                GeneralUtility::makeInstance(DropDownItem::class)
                    ->setLabel('Item')
                    ->setHref('#'),
            );
        $buttonBar->addButton(
            $dropDownButton,
            ButtonBar::BUTTON_POSITION_RIGHT,
            2,
        ); */

        $categories = $this->getCategoriesTree(0, $pid);

        $moduleTemplate->assign('categories', $categories);
        $moduleTemplate->assign('iconOn', $iconOn);
        $moduleTemplate->assign('iconOff', $iconOff);
        
        return $moduleTemplate->renderResponse('CategoryModule/Main');
    }

    private function getCategoriesTree(int $parent = 0, int $pid = 0): array
    {
        $returnUrl = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_W3cCategoryManager',
            ['id' => $pid, 'action' => 'main'],
        );
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parent, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, ParameterType::INTEGER))
            )
            ->orderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $tree = [];
        foreach ($rows as $row) {
            // Récursion pour les enfants
            $children = $this->getCategoriesTree((int)$row['uid'], $pid);
            $row['children'] = $children;

            // URL d'édition
            $row['editUrl'] = $this->backendUriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_category' => [$row['uid'] => 'edit']],
                    'returnUrl' => $returnUrl
                ]
            );

            // URL création sous-catégorie
            $row['newChildUrl'] = $this->backendUriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_category' => [1 => 'new']],
                    'defVals' => ['sys_category' => ['parent' => $row['uid'], 'pid' => $pid]],
                    'returnUrl' => $returnUrl
                ]
            );

            $tree[] = $row;
        }

        return $tree;
    }

    public function toggleHideAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $data = json_decode($request->getBody()->getContents(), true);
        $uid = (int)$data['uid'];
        $hidden = (int)$data['hidden'];

        if ($uid > 0) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_category');

            $connection->update(
                'sys_category',
                ['hidden' => $hidden],
                ['uid' => $uid]
            );

            $result = [
                'success' => true,
                'uid' => $uid,
                'hidden' => $hidden
            ];

            $response->getBody()->write(
                json_encode(['result' => $result], JSON_THROW_ON_ERROR),
            );

            return $response;
        }

        $result = ['success' => false];
        $response->getBody()->write(
                json_encode(['result' => $result], JSON_THROW_ON_ERROR),
            );

        return $response;
        
    }

}
