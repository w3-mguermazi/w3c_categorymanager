<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_categorymanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use W3code\W3cCategoryManager\Domain\Repository\CategoryRepository;
use W3code\W3cCategoryManager\Service\CategoryService;
use W3code\W3cCategoryManager\Utility\LocalizationUtility;
use W3code\W3cCategoryManager\Utility\SortingUtility;

/**
 * Class ModuleController
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 * @author Haythem Daoud <haythem.daoud@w3code.tn>
 */
#[AsController]
class ModuleController extends ActionController
{
    protected ModuleTemplate $moduleTemplate;
    protected QuerySettingsInterface $querySettings;

    protected int $pid = 0;
    protected array $sorting = [];
    private array $siteLanguages = [];
    protected int $currentLanguage;
    protected string $returnUrl;

    /**
     * @param ModuleTemplateFactory $moduleTemplateFactory
     * @param CategoryRepository $categoryRepository
     * @param CategoryService $categoryService
     * @param IconFactory $iconFactory
     */
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected CategoryRepository $categoryRepository,
        protected CategoryService $categoryService,
        private readonly IconFactory $iconFactory
    ) {}

    /**
     * @throws SiteNotFoundException
     * @throws RouteNotFoundException
     */
    public function initializeAction(): void
    {
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            return;
        }

        $this->querySettings = $this->categoryRepository->createQuery()->getQuerySettings();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('module.title'));
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
        $this->setDocHeader();

        parent::initializeAction();
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function setDocHeader(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Add new Category button
        $newCategoryButton = $buttonBar->makeLinkButton()
            ->setTitle(LocalizationUtility::translate('category.new'))
            ->setIcon($this->getIconByIdentifier('actions-document-new'))
            ->setHref($this->getUriBuilder()->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_category' => [$this->pid => 'new']],
                    'defVals' => ['sys_category' => ['pid' => $this->pid]],
                    'returnUrl' => $this->returnUrl,
                ]
            ));
        $buttonBar->addButton($newCategoryButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        // Add language button dropdown
        if ($this->pid != 0 && count($this->siteLanguages) > 0) {

            $languagesDropDownButton = $buttonBar->makeDropDownButton()
                ->setLabel(LocalizationUtility::translate('language.switch'))
                ->setTitle(LocalizationUtility::translate('language.switch'))
                ->setIcon($this->getIconByIdentifier('module-lang'));

            foreach ($this->siteLanguages as $lang) {
                $languagesDropDownButton->addItem(
                    GeneralUtility::makeInstance(DropDownItem::class)
                        ->setLabel($lang->getNavigationTitle())
                        ->setHref((string)$this->getUriBuilder()->buildUriFromRoute(
                            'web_w3c_categorymanager',
                            [
                                'id' => $this->pid,
                                'sys_language_uid' => $lang->getLanguageId(),
                            ]
                        ))
                );
            }

            $buttonBar->addButton($languagesDropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
        }

        // add sorting button dropdown
        $sortingDropDownButton = $buttonBar->makeDropDownButton()
            ->setLabel(LocalizationUtility::translate('sorting.dropdownLabel'))
            ->setTitle(LocalizationUtility::translate('sorting.dropdownLabel'))
            ->setIcon($this->getIconByIdentifier('actions-sort-amount'));

        foreach ($this->sorting['options'] as $sortingOption) {
            $sortingDropDownButton->addItem(
                $this->makeDropdownButton($sortingOption, 'ASC')
            );
            $sortingDropDownButton->addItem(
                $this->makeDropdownButton($sortingOption, 'DESC')
            );
        }
        $buttonBar->addButton($sortingDropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
    }

    /**
     * @throws RouteNotFoundException
     */
    protected function makeDropdownButton(string $label, string $option)
    {
        return GeneralUtility::makeInstance(DropDownItem::class)
            ->setLabel(
                LocalizationUtility::translate('sorting.' . $label)
                . ' ' . LocalizationUtility::translate('sorting.direction.' . strtolower($option))
            )
            ->setHref((string)$this->getUriBuilder()->buildUriFromRoute(
                'web_w3c_categorymanager',
                [
                    'id' => $this->pid,
                    'sys_language_uid' => $this->currentLanguage,
                    'sortingBy' => $label,
                    'direction' => $option,
                ]
            ));
    }

    /**
     * @return ResponseInterface
     */
    public function indexAction(): ResponseInterface
    {
        $categories = $this->categoryService->getCategories(
            $this->pid,
            $this->currentLanguage,
            $this->sorting
        );

        $this->moduleTemplate->assignMultiple([
            'categories' => $categories,
            'currentLanguage' => $this->currentLanguage,
            'siteLanguages' => $this->siteLanguages,
            'returnUrl' => $this->returnUrl,
        ]);

        return $this->moduleTemplate->renderResponse('Module/Index');
    }

    private function setReturnUrl(): void
    {
        $this->returnUrl = (string)($this->request->getParsedBody()['returnUrl']
            ?? $this->request->getQueryParams()['returnUrl']
            ?? null);
    }

    /**
     * @param string $key
     * @return Icon
     */
    private function getIconByIdentifier(string $key): Icon
    {
        if ($this->getTypo3MajorVersion()) {
            $icon = $this->iconFactory->getIcon($key, IconSize::SMALL);
        } else {
            $icon = $this->iconFactory->getIcon($key, Icon::SIZE_SMALL);
        }

        return $icon;
    }

    /**
     * @return UriBuilder
     */
    protected function getUriBuilder(): UriBuilder
    {
        return GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * @return bool
     */
    protected function getTypo3MajorVersion(): bool
    {
        return  GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() > 12;
    }
}
