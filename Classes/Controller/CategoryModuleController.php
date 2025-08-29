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
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Imaging\Icon;


#[AsController]
class CategoryModuleController extends ActionController
{
    private ModuleTemplateFactory $moduleTemplateFactory;

    private UriBuilder $backendUriBuilder;

    private IconFactory $iconFactory;

    private array $sortingOptions = [
        'title',
        'sorting'
    ];

    private string $sortingBy = 'title';

    private string $sortingDirection = 'ASC';

    private int $pid = 0;

    private array $siteLanguages = [];

    private int $currentLang = 0;

    private string $returnUrl;

    private ModuleTemplate $moduleTemplate;

    private int $typo3Version;

    private string $iconOn = '';
    
    private string $iconOff = '';

    private array $languageFlags = [];

    private Icon $addNewIcon;

    private Icon $langIcon;

    private Icon $sortIcon;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        UriBuilder $backendUriBuilder,    
    )
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->backendUriBuilder = $backendUriBuilder;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    public function initializeAction(): void
    {
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('module.title','w3c_categorymanager'));

        $this->pid = (int)($this->request->getQueryParams()['id'] ?? 0);
        if($this->pid!=0){
            $this->siteLanguages = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->pid)->getAllLanguages();
        }
        $this->currentLang = (int)($this->request->getQueryParams()['sys_language_uid'] ?? 0);

        $this->sortingBy = $this->request->getQueryParams()['sortingBy'] ?? 'title';
        $this->sortingDirection = $this->request->getQueryParams()['sortingDirection'] ?? 'ASC';

        $this->returnUrl = (string)$this->backendUriBuilder->buildUriFromRoute(
            'web_W3cCategoryManager',
            [
                'id' => $this->pid, 
                'action' => 'main',
                'sys_language_uid' => $this->currentLang,
                'sortingBy' => $this->sortingBy,
                'sortingDirection' => $this->sortingDirection
            ],
        );

        $this->buildIcons();

    }

    public function mainAction(): ResponseInterface
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:w3c_categorymanager/Resources/Public/Css/module.css');
        $pageRenderer->addJsFile('EXT:w3c_categorymanager/Resources/Public/JavaScript/backend.js');

        $this->buildMenu();

        $categories = $this->getCategoriesTree(0);

        $this->moduleTemplate->assign('categories', $categories);
        $this->moduleTemplate->assign('iconOn', $this->iconOn);
        $this->moduleTemplate->assign('iconOff', $this->iconOff);

        return $this->moduleTemplate->renderResponse('CategoryModule/Main');
    }

    private function getCategoriesTree(int $parent = 0): array
    {
        /** @var BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        $expandedNodes = $backendUser->uc['w3c_categorymanager']['expandedNodes'] ?? [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        
        $rows = $queryBuilder
        ->select('*')
        ->from('sys_category')
        ->where(
            $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($parent, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($this->currentLang, ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($this->pid, ParameterType::INTEGER))
        )
        ->orderBy($this->sortingBy, $this->sortingDirection)
        ->executeQuery()
        ->fetchAllAssociative();
        

        $tree = [];
        foreach ($rows as $row) {
            $row['expanded'] = false;
            if($expandedNodes && in_array($row['uid'], $expandedNodes)){
                $row['expanded'] = true;
            }

            // Récursion pour les enfants
            $parent = $row['uid'];
            if( $this->currentLang != 0 ){
                $parent = $row['l10n_parent'];
            }
            $row['children'] = $this->getCategoriesTree((int)$parent);

            // URL d'édition
            $row['editUrl'] = $this->backendUriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'sys_category' => [
                            $row['uid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $this->returnUrl
                ]
            );

            if( $this->currentLang == 0 ){
                // URL création sous-catégorie
                $row['newChildUrl'] = $this->backendUriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            'sys_category' => [
                                $this->pid => 'new'
                            ]
                        ],
                        'defVals' => [
                            'sys_category' => [
                                'parent' => $parent,
                            ]
                        ],
                        'returnUrl' => $this->returnUrl
                    ]
                );
            }

            if ( $this->pid != 0 && $this->currentLang == 0 && count($this->siteLanguages) > 0) {
                foreach($this->siteLanguages as $lang){
                    // Si pas de traduction on ajoute le bouton
                    if(!$this->categoryTranslationExists($row['uid'],$lang->getLanguageId())){
                        $row['translations'][$lang->getLanguageId()]['url'] = $this->backendUriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'edit' => ['sys_category' => [$this->pid => 'new']],
                                'defVals' => ['sys_category' => [
                                        'parent' => $row['parent'],
                                        'l10n_parent' => $row['uid'],
                                        'sys_language_uid' => $lang->getLanguageId()
                                    ]
                                ],
                                'returnUrl' => $this->returnUrl
                            ]
                        );

                        $row['translations'][$lang->getLanguageId()]['icon'] = $this->languageFlags[$lang->getLanguageId()] ?? '';
                        $row['translations'][$lang->getLanguageId()]['title'] = LocalizationUtility::translate('category.translate_to','w3c_categorymanager').' '.$lang->getNavigationTitle();
                    }
                }
            }

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

    public function toggleExpandAction(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $data = json_decode($request->getBody()->getContents(), true);
        $uid = (int)$data['uid'];
        $expand = (int)$data['state'];

        if ($uid > 0) {
            /** @var BackendUserAuthentication $backendUser */
            $backendUser = $GLOBALS['BE_USER'];
            $expandedNodes = $backendUser->uc['w3c_categorymanager']['expandedNodes'] ?? [];
            if($expand){
                if(!$expandedNodes || !in_array($uid, $expandedNodes)){
                    $expandedNodes[] = $uid;
                }
            } else {
                if($expandedNodes && in_array($uid, $expandedNodes)){
                    $key = array_search($uid, $expandedNodes);
                    if($key !== false){
                        unset($expandedNodes[$key]);
                    }
                }
            }
            $backendUser->uc['w3c_categorymanager']['expandedNodes'] = $expandedNodes;
            $backendUser->writeUC();

            $result = [
                'success' => true,
                'uid' => $uid,
                'expand' => $expand
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

    private function categoryTranslationExists(int $categoryUid, int $languageUid): bool
    {
        if ($languageUid === 0) {
            // Pas de traduction à vérifier pour la langue par défaut
            return true;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');

        $queryBuilder->getRestrictions()->removeAll();

        $count = $queryBuilder
            ->count('uid')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($categoryUid, ParameterType::INTEGER)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, ParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return ((int)$count > 0);
    }

    private function buildMenu(): void
    {
        // Récupérer le composant de boutons
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Ajouter un bouton
        $newCategoryButton = $buttonBar->makeLinkButton()
            ->setTitle('Create new category')
            ->setIcon($this->addNewIcon)
            ->setHref($this->backendUriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => ['sys_category' => [$this->pid => 'new']],
                        'defVals' => ['sys_category' => ['pid' => $this->pid]],
                        'returnUrl' => $this->returnUrl
                    ]
                ));
        $buttonBar->addButton($newCategoryButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        if ($this->pid != 0 && count($this->siteLanguages) > 0) {

            $languagesDropDownButton = $buttonBar->makeDropDownButton()
                ->setLabel( LocalizationUtility::translate( 'language.switch', 'w3c_categorymanager' ) )
                ->setTitle( LocalizationUtility::translate( 'language.switch', 'w3c_categorymanager' ) )
                ->setIcon( $this->langIcon );

            foreach( $this->siteLanguages as $lang){
                $languagesDropDownButton->addItem(
                    GeneralUtility::makeInstance(DropDownItem::class)
                        ->setLabel($lang->getNavigationTitle())
                        ->setHref($this->backendUriBuilder->buildUriFromRoute(
                            'web_W3cCategoryManager',
                            [
                                'id' => $this->pid,
                                'sys_language_uid' => $lang->getLanguageId()
                            ]
                        ))
                );
            }
            
            $buttonBar->addButton(
                $languagesDropDownButton,
                ButtonBar::BUTTON_POSITION_RIGHT,
                2,
            );
        }

        $sortingDropDownButton = $buttonBar->makeDropDownButton()
            ->setLabel(LocalizationUtility::translate('sorting.dropdownLabel','w3c_categorymanager'))
            ->setTitle(LocalizationUtility::translate('sorting.dropdownLabel','w3c_categorymanager'))
            ->setIcon($this->sortIcon);
        
        foreach( $this->sortingOptions as $sortingOption){
            $sortingDropDownButton->addItem(
                GeneralUtility::makeInstance(DropDownItem::class)
                    ->setLabel(LocalizationUtility::translate('sorting.' . $sortingOption, 'w3c_categorymanager').' '.LocalizationUtility::translate('sorting.direction.ASC', 'w3c_categorymanager'))
                    ->setHref($this->backendUriBuilder->buildUriFromRoute(
                        'web_W3cCategoryManager',
                        [
                            'id' => $this->pid,
                            'sys_language_uid' => $this->currentLang,
                            'sortingBy' => $sortingOption,
                            'sortingDirection' => 'ASC',
                        ]
                    ))
            );
            $sortingDropDownButton->addItem(
                GeneralUtility::makeInstance(DropDownItem::class)
                    ->setLabel(LocalizationUtility::translate('sorting.' . $sortingOption, 'w3c_categorymanager').' '.LocalizationUtility::translate('sorting.direction.DESC', 'w3c_categorymanager'))
                    ->setHref($this->backendUriBuilder->buildUriFromRoute(
                        'web_W3cCategoryManager',
                        [
                            'id' => $this->pid,
                            'sys_language_uid' => $this->currentLang,
                            'sortingBy' => $sortingOption,
                            'sortingDirection' => 'DESC',
                        ]
                    ))
            );
        }
        $buttonBar->addButton(
            $sortingDropDownButton,
            ButtonBar::BUTTON_POSITION_RIGHT,
            2,
        );
    }

    private function buildIcons(){
        if($this->typo3Version == 12){
            $this->iconOn = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon('actions-toggle-on', Icon::SIZE_SMALL)->render());
            $this->iconOff = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon('actions-toggle-off', Icon::SIZE_SMALL)->render());
            $this->addNewIcon = $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $this->langIcon = $this->iconFactory->getIcon( 'module-lang', Icon::SIZE_SMALL );
            $this->sortIcon = $this->iconFactory->getIcon( 'actions-sort-amount', Icon::SIZE_SMALL );
            foreach($this->siteLanguages as $lang){
                $this->languageFlags[$lang->getLanguageId()] = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon($lang->getFlagIdentifier(), Icon::SIZE_SMALL)->render());
            }
        }else{
            $this->iconOn = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon('actions-toggle-on', IconSize::SMALL)->render());
            $this->iconOff = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon('actions-toggle-off', IconSize::SMALL)->render());
            $this->addNewIcon = $this->iconFactory->getIcon('actions-document-new', IconSize::SMALL);
            $this->langIcon = $this->iconFactory->getIcon( 'module-lang', IconSize::SMALL );
            $this->sortIcon = $this->iconFactory->getIcon( 'actions-sort-amount', IconSize::SMALL );
            foreach($this->siteLanguages as $lang){
                $this->languageFlags[$lang->getLanguageId()] = str_replace(["\n", "\r"], '', $this->iconFactory->getIcon($lang->getFlagIdentifier(), IconSize::SMALL)->render());
            }
        }
    }

}
