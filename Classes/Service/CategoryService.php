<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_category_manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Service;

use W3code\W3cCategoryManager\Domain\Model\Category;
use W3code\W3cCategoryManager\Domain\Repository\CategoryRepository;
use W3code\W3cCategoryManager\Utility\BackendUserUtility;

/**
 * Class CategoryService
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 */
class CategoryService
{
    protected CategoryRepository $categoryRepository;

    /**
     * Injects the Category Repository
     *
     * @param CategoryRepository $categoryRepository An instance of the Category Repository
     */
    public function injectCategoryRepository(
        CategoryRepository $categoryRepository
    ): void {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param int $pid
     * @param int $currentLanguage
     * @param array $sorting
     * @return array
     */
    public function getCategories(int $pid, int $currentLanguage, array $sorting): array
    {
        $rows = $this->categoryRepository->findBy(
            // criteria
            [
                'pid' => $pid,
                'sys_language_uid' => $currentLanguage,
            ],
            // sorting
            [
                $sorting['sortingBy'] => $sorting['direction'],
            ]
        )->toArray();

        return $this->buildRecursiveCategoriesTree($rows, $currentLanguage);
    }

    /**
     * @param array $categories
     * @param int $language
     * @return array
     */
    private function buildRecursiveCategoriesTree(array $categories, int $language): array
    {
        $backendUser = BackendUserUtility::getBackendUser();
        $expandedNodes = $backendUser->uc['w3c_category_manager']['expandedNodes'] ?? [];

        $tree = [];
        foreach ($categories as $category) {
            $parent = $category->getParent()?->getUid();

            if (empty($parent)) {
                $tree[] = $this->buildTreeNode($categories, $category, $expandedNodes, $language);
            }
        }

        return $tree;
    }

    /**
     * @param array $categories
     * @param Category $category
     * @param array $expandedNodes
     * @param int $language
     * @return Category
     */
    private function buildTreeNode(array $categories, Category $category, array $expandedNodes, int $language): Category
    {
        $category->setExpanded(in_array($category->getUid(), $expandedNodes, true));

        $children = [];
        foreach ($categories as $child) {
            $parent = $child->getParent()?->getUid();

            if ($parent === $category->getUid()) {
                $children[] = $this->buildTreeNode($categories, $child, $expandedNodes, $language);
            }
        }

        $category->setChildren($children);

        return $category;
    }
}
