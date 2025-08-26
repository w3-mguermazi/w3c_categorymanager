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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconFactory;

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

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $iconOn = $iconFactory->getIcon('actions-toggle-on', IconSize::SMALL)->render();
        $iconOff = $iconFactory->getIcon('actions-toggle-off', IconSize::SMALL)->render();

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $categories = $this->getCategoriesTree();

        $moduleTemplate->assign('categories', $categories);
        $moduleTemplate->assign('iconOn', $iconOn);
        $moduleTemplate->assign('iconOff', $iconOff);
        
        return $moduleTemplate->renderResponse('CategoryModule/Main');
    }

    private function getCategoriesTree(int $parent = 0): array
    {
        $returnUrl = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_W3cCategoryManager',
            ['id' => 0, 'action' => 'main'],
        );
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parent, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER))
            )
            ->orderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $tree = [];
        foreach ($rows as $row) {
            // Récursion pour les enfants
            $children = $this->getCategoriesTree((int)$row['uid']);
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
                    'defVals' => ['sys_category' => ['parent' => $row['uid']]],
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
