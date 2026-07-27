<?php

declare(strict_types=1);
/*
 * This file is part of the "w3c_categorymanager" Extension for TYPO3 CMS.
 */

namespace W3code\W3cCategoryManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use W3code\W3cCategoryManager\Domain\Repository\CategoryRepository;
use W3code\W3cCategoryManager\Service\CategoryService;
use W3code\W3cCategoryManager\Utility\LocalizationUtility;
use W3code\W3cCategoryManager\Utility\SortingUtility;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;

#[AsController]
class ModuleController extends ActionController
{
    protected QuerySettingsInterface $querySettings;

    protected int $pid = 0;
    protected array $sorting = [];
    private array $siteLanguages = [];
    protected int $currentLanguage = 0;
    protected string $returnUrl = '';

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly CategoryRepository $categoryRepository,
        protected readonly CategoryService $categoryService,
        private readonly IconFactory $iconFactory,
        protected readonly UriBuilder $backendUriBuilder,
        private readonly ComponentFactory $componentFactory
    ) {}

    /**
     * @throws SiteNotFoundException
     */
    public function initializeAction(): void
    {
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            return;
        }

        $this->querySettings = $this->categoryRepository->createQuery()->getQuerySettings();
        $this->sorting = SortingUtility::get();

        if (!empty($this->request->getQueryParams())) {
            $params = $this->request->getQueryParams();

            $this->pid = (int)($params['id'] ?? 0);
            if ($this->pid) {
                $this->siteLanguages = GeneralUtility::makeInstance(SiteFinder::class)
                    ->getSiteByPageId($this->pid)->getAllLanguages();
            }

            $this->currentLanguage = (int)($params['sys_language_uid'] ?? 0);
            $this->sorting['sortingBy'] = $params['sortingBy'] ?? $this->sorting['sortingBy'];
            $this->sorting['direction'] = $params['direction'] ?? $this->sorting['direction'];
        }

        $this->setReturnUrl();

        parent::initializeAction();
    }

    /**
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle(LocalizationUtility::translate('module.title'));
        
        // 1. CORRECTION : Réactivation de l'appel pour configurer le DocHeader
        $this->configureDocHeader($moduleTemplate);

        $categories = $this->categoryService->getCategories(
            $this->pid,
            $this->currentLanguage,
            $this->sorting
        );

        $moduleTemplate->assignMultiple([
            'categories' => $categories,
            'currentLanguage' => $this->currentLanguage,
            'siteLanguages' => $this->siteLanguages,
            'returnUrl' => $this->returnUrl,
            'sorting' => $this->sorting['sortingBy'],
        ]);

        return $moduleTemplate->renderResponse('Module/Index');
    }

    /**
     * Configure action buttons in the DocHeader.
     *
     * @throws RouteNotFoundException
     */
    protected function configureDocHeader(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // New Category button
        $newCategoryButton = $this->componentFactory->createLinkButton()
            ->setTitle(LocalizationUtility::translate('category.new'))
            ->setIcon($this->getIconByIdentifier('actions-document-new'))
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_category' => [$this->pid => 'new']],
                    'defVals' => ['sys_category' => ['pid' => $this->pid]],
                    'returnUrl' => $this->returnUrl,
                ]
            ));
        $buttonBar->addButton($newCategoryButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        // Language selector dropdown
        if ($this->pid !== 0 && count($this->siteLanguages) > 0) {
            // 2. CORRECTION : Remplacement par l'API moderne ComponentFactory pour harmoniser les DropDowns
            $languagesDropDownButton = $this->componentFactory->createDropDownButton()
                ->setLabel(LocalizationUtility::translate('language.switch'))
                ->setTitle(LocalizationUtility::translate('language.switch'))
                ->setIcon($this->getIconByIdentifier('module-lang'));
            foreach ($this->siteLanguages as $lang) {
                $item = $this->componentFactory->createDropDownItem()
                    ->setLabel($lang->getNavigationTitle())
                    ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                        'content_w3ccategorymanager',
                        [
                            'id' => $this->pid,
                            'sys_language_uid' => $lang->getLanguageId(),
                        ]
                    ));
                /** @var DropDownItemInterface $item */
                $languagesDropDownButton->addItem($item);
            }

            $buttonBar->addButton($languagesDropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
        }

        // Sorting options dropdown
        // 3. CORRECTION : Remplacement par l'API moderne ComponentFactory
        $sortingDropDownButton = $this->componentFactory->createDropDownButton()
            ->setLabel(LocalizationUtility::translate('sorting.dropdownLabel'))
            ->setTitle(LocalizationUtility::translate('sorting.dropdownLabel'))
            ->setIcon($this->getIconByIdentifier('actions-sort-amount'));

        foreach ($this->sorting['options'] as $sortingOption) {
            $item = $this->makeDropdownButton($sortingOption, 'ASC');
            /** @var DropDownItemInterface $item */
            $sortingDropDownButton->addItem($item);
        }
        $buttonBar->addButton($sortingDropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function makeDropdownButton(string $label, string $option): DropDownItemInterface
    {
        $dropDown = $this->componentFactory->createDropDownItem()
            ->setLabel(
                LocalizationUtility::translate('sorting.' . $label)
                . ' ' . LocalizationUtility::translate('sorting.direction.' . strtolower($option))
            )
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                'content_w3ccategorymanager',
                [
                    'id' => $this->pid,
                    'sys_language_uid' => $this->currentLanguage,
                    'sortingBy' => $label,
                ]
            ));
        /** @var DropDownItemInterface $dropDown */
        return $dropDown;
    }

    private function setReturnUrl(): void
    {
        $this->returnUrl = (string)($this->request->getParsedBody()['returnUrl']
            ?? $this->request->getQueryParams()['returnUrl']
            ?? '');
    }

    private function getIconByIdentifier(string $key): Icon
    {
        return $this->iconFactory->getIcon($key, IconSize::SMALL);
    }
}
