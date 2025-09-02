<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_categorymanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\ViewHelpers;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use W3code\W3cCategoryManager\Domain\Model\Category;
use W3code\W3cCategoryManager\Domain\Repository\CategoryRepository;

/**
 * Class CheckIfCategoryTranslatedViewHelper
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 * @author Haythem Daoud <haythem.daoud@w3code.tn>
 */
class CheckIfCategoryTranslatedViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('category', Category::class, 'Category Record', true);
        $this->registerArgument('language', SiteLanguage::class, 'Site Language Object', true);
    }

    /**
     * @return bool
     */
    public function render(): bool
    {

        $category = $this->arguments['category'];
        $language = $this->arguments['language'];

        if ($language->getLanguageId() == 0) {
            return true;
        }

        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $count = $categoryRepository->findBy([
            'l10n_parent' => $category->getUid(),
            'sys_language_uid' => $language->getLanguageId(),
        ])->count();

        return $count > 0;
    }
}
