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

use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Table()
 * @ORM\Entity()
 */
class Product_Comment_Report
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="ProductComment")
     * @ORM\JoinColumn(name="id_product_comment", referencedColumnName="id_product_comment")
     *
     * @var ProductComment
     */
    private $comment;
    /**
     * @ORM\Id
     * @ORM\Column(name="id_customer", type="integer")
     *
     * @var int
     */
    private $customer_id;
    /**
     * @param int $customerId
     */
    public function __construct(Product_Comment $comment, $customer_id)
    {
        $this->comment = $comment;
        $this->customer_id = $customer_id;
    }
    /**
     * @return ProductComment
     */
    public function get_comment()
    {
        return $this->comment;
    }
    /**
     * @return int
     */
    public function get_customer_id()
    {
        return $this->customer_id;
    }
}