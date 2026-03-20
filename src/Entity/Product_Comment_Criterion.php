<?php

declare (strict_types=1);
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
namespace Presta_Shop\Module\Product_Comment\Entity;

use Doctrine\Common\Collections\Array_Collection;
use Doctrine\ORM\Mapping as ORM;
use Validate;
/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Product_Comment_Criterion
{
    public const NAME_MAX_LENGTH = 64;
    public const ENTIRE_CATALOG_TYPE = 1;
    public const CATEGORIES_TYPE = 2;
    public const PRODUCTS_TYPE = 3;
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_product_comment_criterion", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_product_comment_criterion_type", type="integer")
     */
    private $type;
    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active = false;
    /**
     * @ORM\OneToMany(targetEntity="PrestaShop\Module\ProductComment\Entity\ProductCommentCriterionLang", cascade={"persist", "remove"}, mappedBy="productcommentcriterion")
     */
    private $criterion_langs;
    /**
     * @var array
     *
     * @todo implement as ORM\OneToMany in the future
     */
    private $categories;
    /**
     * @var array
     *
     * @todo implement as ORM\OneToMany in the future
     */
    private $products;
    public function __construct()
    {
        $this->criterion_langs = new Array_Collection();
    }
    /**
     * @return ArrayCollection
     */
    public function get_criterion_langs()
    {
        return $this->criterion_langs;
    }
    /**
     * @return ProductCommentCriterionLang|null
     */
    public function get_criterion_lang_by_lang_id(int $lang_id)
    {
        foreach ($this->criterion_langs as $criterion_lang) {
            if ($lang_id === $criterion_lang->get_lang()->get_id()) {
                return $criterion_lang;
            }
        }
        return null;
    }
    public function add_criterion_lang(Product_Comment_Criterion_Lang $criterion_lang): self
    {
        $criterion_lang->set_product_comment_criterion($this);
        $this->criterion_langs->add($criterion_lang);
        return $this;
    }
    public function get_criterion_name(): string
    {
        if ($this->criterion_langs->count() <= 0) {
            return '';
        }
        $criterion_lang = $this->criterion_langs->first();
        return $criterion_lang->get_name();
    }
    /**
     * @return array
     */
    public function get_categories()
    {
        return $this->categories;
    }
    /**
     * @param array $selectedCategories
     */
    public function set_categories($selected_categories): self
    {
        $this->categories = $selected_categories;
        return $this;
    }
    /**
     * @return array
     */
    public function get_products()
    {
        return $this->products;
    }
    /**
     * @param array $selectedProducts
     */
    public function set_products($selected_products): self
    {
        $this->products = $selected_products;
        return $this;
    }
    public function get_id(): int
    {
        return $this->id;
    }
    public function get_type(): int
    {
        return $this->type;
    }
    public function set_type(int $type): self
    {
        $this->type = $type;
        return $this;
    }
    public function is_active(): bool
    {
        return $this->active;
    }
    public function set_active(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
    public function is_valid(): bool
    {
        foreach ($this->criterion_langs as $criterion_lang) {
            if (!Validate::is_generic_name($criterion_lang->get_name())) {
                return false;
            }
        }
        return true;
    }
}