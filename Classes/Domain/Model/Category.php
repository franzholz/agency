<?php
declare(strict_types=1);

namespace JambageCom\Agency\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;

class Category extends AbstractEntity
{
    /**
     * @var string
     * @Extbase\Validate("NotEmpty")
     */
    protected string $title = '';

    /**
     * @var Category|null
     * @Extbase\ORM\Lazy
     */
    protected $parent;

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
     * Gets the parent category.
     *
     * @return Category|null the parent category
     */
    public function getParent(): Category|null
    {
        if ($this->parent instanceof LazyLoadingProxy) {
            $this->parent->_loadRealInstance();
        }
        return $this->parent;
    }

    /**
     * Sets the parent category.
     *
     * @param Category $parent the parent category
     */
    public function setParent(Category $parent)
    {
        $this->parent = $parent;
    }
}
