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
namespace Presta_Shop\Module\Product_Comment\Repository;

use Doctrine\Bundle\Doctrine_Bundle\Repository\Service_Entity_Repository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Query_Builder;
use Doctrine\Persistence\Manager_Registry;
use Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion;
use Presta_Shop\Presta_Shop\Adapter\Symfony_Container;
/**
 * @extends ServiceEntityRepository<ProductCommentCriterion>
 *
 * @method ProductCommentCriterion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductCommentCriterion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductCommentCriterion[] findAll()
 * @method ProductCommentCriterion[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Product_Comment_Criterion_Repository extends Service_Entity_Repository
{
    /**
     * @var Connection the Database connection
     */
    private $connection;
    /**
     * @var string the Database prefix
     */
    private $database_prefix;
    /**
     * @param ManagerRegistry $registry
     * @param Connection $connection
     * @param string $databasePrefix
     */
    public function __construct($registry, $connection, $database_prefix)
    {
        parent::__construct($registry, Product_Comment_Criterion::class);
        $this->connection = $connection;
        $this->database_prefix = $database_prefix;
    }
    public function add(Product_Comment_Criterion $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->persist($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    public function remove(Product_Comment_Criterion $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->remove($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    private function delete_categories(\Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion $criterion): int
    {
        return $this->connection->execute_update('
            DELETE FROM `' . _DB_PREFIX_ . 'product_comment_criterion_category`
            WHERE `id_product_comment_criterion` = ' . $criterion->get_id());
    }
    private function delete_products(\Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion $criterion): int
    {
        return $this->connection->execute_update('
            DELETE FROM `' . _DB_PREFIX_ . 'product_comment_criterion_product`
            WHERE `id_product_comment_criterion` = ' . $criterion->get_id());
    }
    private function delete_grades(\Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion $criterion): int
    {
        return $this->connection->execute_update('
            DELETE FROM `' . _DB_PREFIX_ . 'product_comment_grade`
            WHERE `id_product_comment_criterion` = ' . $criterion->get_id());
    }
    /* Remove a criterion and Delete its manual relation _category, _product, _grade */
    public function delete(Product_Comment_Criterion $criterion): int
    {
        $res = 0;
        $criterion_type = $criterion->get_type();
        if ($criterion_type == Product_Comment_Criterion::CATEGORIES_TYPE) {
            $res += $this->delete_categories($criterion);
        } elseif ($criterion_type == Product_Comment_Criterion::PRODUCTS_TYPE) {
            $res += $this->delete_products($criterion);
        } else {
            $res = 1;
        }
        $res += $this->delete_grades($criterion);
        $this->remove($criterion, true);
        // todo: return void, and use try catch Exception instead
        return $res;
    }
    /* Update a criterion and Update its manual relation _category, _product */
    public function update(Product_Comment_Criterion $criterion): int
    {
        $res = 0;
        $criterion_type = $criterion->get_type();
        $this->get_entity_manager()->persist($criterion);
        $this->get_entity_manager()->flush();
        if ($criterion_type == Product_Comment_Criterion::CATEGORIES_TYPE) {
            $res += $this->delete_categories($criterion);
            $res += $this->update_categories($criterion);
        } elseif ($criterion_type == Product_Comment_Criterion::PRODUCTS_TYPE) {
            $res += $this->delete_products($criterion);
            $res += $this->update_products($criterion);
        } else {
            $res = 1;
        }
        // todo: return void, and use try catch Exception instead
        return $res;
    }
    private function update_categories(\Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion $criterion): int
    {
        $res = 0;
        $criterion_id = $criterion->get_id();
        foreach ($criterion->get_categories() as $id_category) {
            $res += $this->connection->execute_update('INSERT INTO `' . _DB_PREFIX_ . 'product_comment_criterion_category` (`id_product_comment_criterion`, `id_category`)
                VALUES(' . $criterion_id . ',' . $id_category . ')');
        }
        return $res;
    }
    private function update_products(\Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion $criterion): int
    {
        $res = 0;
        $criterion_id = $criterion->get_id();
        foreach ($criterion->get_products() as $id_product) {
            $res += $this->connection->execute_update('INSERT INTO `' . _DB_PREFIX_ . 'product_comment_criterion_product` (`id_product_comment_criterion`, `id_product`)
                VALUES(' . $criterion_id . ',' . $id_product . ')');
        }
        return $res;
    }
    public function update_general(Product_Comment_Criterion $criterion): void
    {
        $this->get_entity_manager()->persist($criterion);
        $this->get_entity_manager()->flush();
    }
    /**
     * @return array
     *
     * @throws \PrestaShopException
     */
    public function get_by_product(int $id_product, int $id_lang)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('pcc.id_product_comment_criterion, pccl.name')->from($this->database_prefix . 'product_comment_criterion', 'pcc')->left_join('pcc', $this->database_prefix . 'product_comment_criterion_lang', 'pccl', 'pcc.id_product_comment_criterion = pccl.id_product_comment_criterion')->left_join('pcc', $this->database_prefix . 'product_comment_criterion_product', 'pccp', 'pcc.id_product_comment_criterion = pccp.id_product_comment_criterion')->left_join('pcc', $this->database_prefix . 'product_comment_criterion_category', 'pccc', 'pcc.id_product_comment_criterion = pccc.id_product_comment_criterion')->left_join('pccc', $this->database_prefix . 'category', 'c', 'pccc.id_category = c.id_category')->left_join('c', $this->database_prefix . 'category_product', 'cp', 'c.id_category = cp.id_category')->and_where($qb->expr()->or_x($qb->expr()->eq('pcc.id_product_comment_criterion_type', ':catalog_type'), $qb->expr()->eq('pccp.id_product', ':id_product'), $qb->expr()->eq('cp.id_product', ':id_product')))->and_where('pccl.id_lang = :id_lang')->and_where('pcc.active = :active')->set_parameter('catalog_type', Product_Comment_Criterion::ENTIRE_CATALOG_TYPE)->set_parameter('active', 1)->set_parameter('id_product', $id_product)->set_parameter('id_lang', $id_lang)->add_group_by('pcc.id_product_comment_criterion');
        return $qb->execute()->fetch_all();
    }
    /**
     * @return array Criterions
     */
    public function get_criterions(int $id_lang, $type = false, $active = false)
    {
        $sql = '
            SELECT pcc.`id_product_comment_criterion`, pcc.id_product_comment_criterion_type, pccl.`name`, pcc.active
            FROM `' . _DB_PREFIX_ . 'product_comment_criterion` pcc
            JOIN `' . _DB_PREFIX_ . 'product_comment_criterion_lang` pccl ON (pcc.id_product_comment_criterion = pccl.id_product_comment_criterion)
            WHERE pccl.`id_lang` = ' . $id_lang . ($active ? ' AND active = 1' : '') . ($type ? ' AND id_product_comment_criterion_type = ' . (int) $type : '') . '
            ORDER BY pccl.`name` ASC';
        $criterions = $this->connection->execute_query($sql)->fetch_all();
        $types = self::get_types();
        foreach ($criterions as $key => $data) {
            $criterions[$key]['type_name'] = $types[$data['id_product_comment_criterion_type']];
        }
        return $criterions;
    }
    /**
     * @return array
     */
    public function get_products(int $id_criterion)
    {
        $sql = '
            SELECT pccp.id_product, pccp.id_product_comment_criterion
            FROM `' . _DB_PREFIX_ . 'product_comment_criterion_product` pccp
            WHERE pccp.id_product_comment_criterion = ' . $id_criterion;
        $res = $this->connection->execute_query($sql)->fetch_all();
        $products = [];
        if ($res) {
            foreach ($res as $row) {
                $products[] = (int) $row['id_product'];
            }
        }
        return $products;
    }
    /**
     * @return array
     */
    public function get_categories(int $id_criterion)
    {
        $sql = '
            SELECT pccc.id_category, pccc.id_product_comment_criterion
            FROM `' . _DB_PREFIX_ . 'product_comment_criterion_category` pccc
            WHERE pccc.id_product_comment_criterion = ' . $id_criterion;
        $res = $this->connection->execute_query($sql)->fetch_all();
        $criterions = [];
        if ($res) {
            foreach ($res as $row) {
                $criterions[] = (int) $row['id_category'];
            }
        }
        return $criterions;
    }
    /**
     * @return array
     */
    public function get_types()
    {
        $sf_translator = Symfony_Container::get_instance()->get('translator');
        return [1 => $sf_translator->trans('Valid for the entire catalog', [], 'Modules.Productcomments.Admin'), 2 => $sf_translator->trans('Restricted to some categories', [], 'Modules.Productcomments.Admin'), 3 => $sf_translator->trans('Restricted to some products', [], 'Modules.Productcomments.Admin')];
    }
}