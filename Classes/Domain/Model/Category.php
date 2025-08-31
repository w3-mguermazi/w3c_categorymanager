<?php

declare(strict_types=1);

/*
 * This file is part of the "w3c_category_manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace W3code\W3cCategoryManager\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;

/**
 * Class Category
 *
 * @author Mehdi Guermazi <mehdi.guermazi@w3code.tn>
 */
class Category extends AbstractEntity
{
    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $title = '';
    protected string $description = '';
    protected bool $expanded = false;
    protected int $l10nParent = 0;
    protected bool $hidden = false;
    protected int $sysLanguageUid = 0;
    protected int $localizedUid = 0;

    /**
     * @Extbase\ORM\Lazy
     */
    protected Category|LazyLoadingProxy|null $parent = null;

    /**
     * @Extbase\ORM\Lazy
     */
    protected array $children = [];

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function getExpanded(): bool
    {
        return $this->expanded;
    }

    /**
     * @param bool $expanded
     */
    public function setExpanded(bool $expanded): void
    {
        $this->expanded = $expanded;
    }

    /**
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return int
     */
    public function getSysLanguageUid(): int
    {
        // int cast is needed as $this->_languageUid is null by default
        return (int)$this->_languageUid;
    }

    /**
     * @param int $sysLanguageUid
     */
    public function setSysLanguageUid(int $sysLanguageUid): void
    {
        $this->_languageUid = $sysLanguageUid;
    }

    /**
     * @return int
     */
    public function getLocalizedUid(): int
    {
        // int cast is needed as $this->_localizedUid is null by default
        return $this->_localizedUid;
    }

    /**
     * @param int $localizedUid
     */
    public function setLocalizedUid(int $localizedUid): void
    {
        $this->_localizedUid = $localizedUid;
    }

    /**
     * @return Category|null
     */
    public function getParent(): ?Category
    {
        if ($this->parent instanceof LazyLoadingProxy) {
            $this->parent->_loadRealInstance();
        }
        return $this->parent;
    }

    /**
     * @param Category $parent
     */
    public function setParent(Category $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param Category[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /**
     * @return int
     */
    public function getL10nParent(): int
    {
        return $this->l10nParent;
    }

    /**
     * @param int $l10nParent
     */
    public function setL10nParent(int $l10nParent): void
    {
        $this->l10nParent = $l10nParent;
    }
}
