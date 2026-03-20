<?php

/**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */
declare (strict_types=1);
namespace Presta_Shop\Module\Product_Comment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Presta_Shop_Bundle\Entity\Lang;
/**
 * @ORM\Table()
 *
 * @ORM\Entity()
 */
class Product_Comment_Criterion_Lang
{
    /**
     * @var ProductCommentCriterion
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity="PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion", inversedBy="criterionLangs")
     *
     * @ORM\JoinColumn(name="id_product_comment_criterion", referencedColumnName="id_product_comment_criterion", nullable=false)
     */
    private $productcommentcriterion;
    /**
     * @var Lang
     *
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity="PrestaShopBundle\Entity\Lang")
     *
     * @ORM\JoinColumn(name="id_lang", referencedColumnName="id_lang", nullable=false, onDelete="CASCADE")
     */
    private $lang;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;
    /**
     * @return ProductCommentCriterion
     */
    public function get_product_comment_criterion()
    {
        return $this->productcommentcriterion;
    }
    public function set_product_comment_criterion(Product_Comment_Criterion $productcommentcriterion): self
    {
        $this->productcommentcriterion = $productcommentcriterion;
        return $this;
    }
    /**
     * @return Lang
     */
    public function get_lang()
    {
        return $this->lang;
    }
    public function set_lang(Lang $lang): self
    {
        $this->lang = $lang;
        return $this;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function set_name(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}