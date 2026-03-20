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
class Product_Comment
{
    public const TITLE_MAX_LENGTH = 64;
    public const CUSTOMER_NAME_MAX_LENGTH = 64;
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_product_comment", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_product", type="integer")
     */
    private $product_id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_customer", type="integer")
     */
    private $customer_id;
    /**
     * @var int
     *
     * @ORM\Column(name="id_guest", type="integer")
     */
    private $guest_id;
    /**
     * @var string
     *
     * @ORM\Column(name="customer_name", type="string", length=64)
     */
    private $customer_name;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;
    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;
    /**
     * @var int
     *
     * @ORM\Column(name="grade", type="integer")
     */
    private $grade;
    /**
     * @var bool
     *
     * @ORM\Column(name="validate", type="boolean")
     */
    private $validate = false;
    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted = false;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_add", type="datetime")
     */
    private $date_add;
    /**
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * @return int
     */
    public function get_product_id()
    {
        return $this->product_id;
    }
    /**
     * @param int $productId
     */
    public function set_product_id($product_id): self
    {
        $this->product_id = $product_id;
        return $this;
    }
    /**
     * @return int
     */
    public function get_customer_id()
    {
        return $this->customer_id;
    }
    /**
     * @param int $customerId
     */
    public function set_customer_id($customer_id): self
    {
        $this->customer_id = $customer_id;
        return $this;
    }
    /**
     * @return int
     */
    public function get_guest_id()
    {
        return $this->guest_id;
    }
    /**
     * @param int $guestId
     */
    public function set_guest_id($guest_id): self
    {
        $this->guest_id = $guest_id;
        return $this;
    }
    /**
     * @return string
     */
    public function get_customer_name()
    {
        return $this->customer_name;
    }
    /**
     * @param string $customerName
     */
    public function set_customer_name($customer_name): self
    {
        $this->customer_name = $customer_name;
        return $this;
    }
    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }
    /**
     * @param string $title
     */
    public function set_title($title): self
    {
        $this->title = $title;
        return $this;
    }
    /**
     * @return string
     */
    public function get_content()
    {
        return $this->content;
    }
    /**
     * @param string $content
     */
    public function set_content($content): self
    {
        $this->content = $content;
        return $this;
    }
    /**
     * @return int
     */
    public function get_grade()
    {
        return $this->grade;
    }
    /**
     * @param int $grade
     */
    public function set_grade($grade): self
    {
        $this->grade = $grade;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_validate()
    {
        return $this->validate;
    }
    /**
     * @param bool $validate
     */
    public function set_validate($validate): self
    {
        $this->validate = $validate;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_deleted()
    {
        return $this->deleted;
    }
    /**
     * @param bool $deleted
     */
    public function set_deleted($deleted): self
    {
        $this->deleted = $deleted;
        return $this;
    }
    /**
     * @return \DateTime
     */
    public function get_date_add()
    {
        return $this->date_add;
    }
    /**
     * Date is stored in UTC timezone
     *
     * @param \DateTime $dateAdd
     */
    public function set_date_add($date_add): self
    {
        $this->date_add = $date_add;
        return $this;
    }
    public function to_array(): array
    {
        return ['id_product' => $this->get_product_id(), 'id_product_comment' => $this->get_id(), 'title' => $this->get_title(), 'content' => $this->get_content(), 'customer_name' => $this->get_customer_name(), 'date_add' => $this->date_add->format(\DateTime::ATOM), 'grade' => $this->grade, 'usefulness' => 3, 'total_usefulness' => 5];
    }
}