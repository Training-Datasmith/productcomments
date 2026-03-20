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
use Hook;
use Presta_Shop\Module\Product_Comment\Entity\Product_Comment;
/**
 * @extends ServiceEntityRepository<ProductComment>
 *
 * @method ProductComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductComment[] findAll()
 * @method ProductComment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Product_Comment_Repository extends Service_Entity_Repository
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
     * @var bool
     */
    private $guest_comments_allowed;
    /**
     * @var int
     */
    private $comments_minimal_time;
    public const DEFAULT_COMMENTS_PER_PAGE = 5;
    /**
     * @param ManagerRegistry $registry
     * @param Connection $connection
     * @param string $databasePrefix
     * @param bool $guestCommentsAllowed
     * @param int $commentsMinimalTime
     */
    public function __construct($registry, $connection, $database_prefix, $guest_comments_allowed, $comments_minimal_time)
    {
        parent::__construct($registry, Product_Comment::class);
        $this->connection = $connection;
        $this->database_prefix = $database_prefix;
        $this->guest_comments_allowed = (bool) $guest_comments_allowed;
        $this->comments_minimal_time = (int) $comments_minimal_time;
    }
    public function add(Product_Comment $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->persist($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    public function remove(Product_Comment $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->remove($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    public function delete(Product_Comment $entity): void
    {
        $entity_id = $entity->get_id();
        $this->remove($entity, true);
        $this->delete_grades($entity_id);
        $this->delete_reports($entity_id);
        $this->delete_usefulness($entity_id);
    }
    /**
     * @param int $productId
     * @param int $page
     * @param int $commentsPerPage
     * @param bool $validatedOnly
     *
     * @return array
     */
    public function paginate($product_id, $page, $comments_per_page, $validated_only)
    {
        if (empty($comments_per_page)) {
            $comments_per_page = self::DEFAULT_COMMENTS_PER_PAGE;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->add_select('pc.id_product, pc.id_product_comment, pc.title, pc.content, pc.customer_name, pc.date_add, pc.grade')->add_select('c.firstname, c.lastname')->from($this->database_prefix . 'product_comment', 'pc')->left_join('pc', $this->database_prefix . 'customer', 'c', 'pc.id_customer = c.id_customer AND c.deleted = :not_deleted')->and_where('pc.id_product = :id_product')->and_where('pc.deleted = :not_deleted')->set_parameter('not_deleted', 0)->set_parameter('id_product', $product_id)->set_max_results($comments_per_page)->set_first_result(($page - 1) * $comments_per_page)->add_group_by('pc.id_product_comment')->add_order_by('pc.date_add', 'DESC');
        if ($validated_only) {
            $qb->and_where('pc.validate = :validate')->set_parameter('validate', 1);
        }
        return $qb->execute()->fetch_all();
    }
    /**
     * @param int $productCommentId
     *
     * @return array
     */
    public function get_product_comment_usefulness($product_comment_id)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->add_select('pcu.usefulness')->from($this->database_prefix . 'product_comment_usefulness', 'pcu')->and_where('pcu.id_product_comment = :id_product_comment')->set_parameter('id_product_comment', $product_comment_id);
        $usefulness_infos = ['usefulness' => 0, 'total_usefulness' => 0];
        $customer_appreciations = $qb->execute()->fetch_all();
        foreach ($customer_appreciations as $customer_appreciation) {
            if ((int) $customer_appreciation['usefulness']) {
                ++$usefulness_infos['usefulness'];
            }
            ++$usefulness_infos['total_usefulness'];
        }
        return $usefulness_infos;
    }
    /**
     * @param int $productId
     * @param bool $validatedOnly
     *
     * @return float
     */
    public function get_average_grade($product_id, $validated_only)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('AVG(pc.grade) AS averageGrade')->from($this->database_prefix . 'product_comment', 'pc')->and_where('pc.id_product = :id_product')->and_where('pc.deleted = :deleted')->set_parameter('deleted', 0)->set_parameter('id_product', $product_id);
        if ($validated_only) {
            $qb->and_where('pc.validate = :validate')->set_parameter('validate', 1);
        }
        return (float) $qb->execute()->fetch(\PDO::FETCH_COLUMN);
    }
    /**
     * @param int $langId
     * @param int $shopId
     * @param int $validate
     * @param bool $deleted
     * @param int $page
     * @param int $limit
     * @param bool $skip_validate
     *
     * @return array
     */
    public function get_by_validate($lang_id, $shop_id, $validate = 0, $deleted = false, $page = null, $limit = null, $skip_validate = false)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('pc.`id_product_comment`, pc.`id_product`, c.id_customer AS customer_id, 
                IF(c.id_customer, CONCAT(c.`firstname`, \' \',  c.`lastname`), pc.customer_name) customer_name, 
                pc.`title`, pc.`content`, pc.`grade`, pc.`date_add`, pl.`name`')->from($this->database_prefix . 'product_comment', 'pc')->left_join('pc', $this->database_prefix . 'customer', 'c', 'pc.id_customer = c.id_customer')->left_join('pc', $this->database_prefix . 'product_lang', 'pl', 'pc.id_product = pl.id_product')->and_where('pc.deleted = :deleted')->set_parameter('deleted', $deleted)->and_where('pl.id_lang = :id_lang')->set_parameter('id_lang', $lang_id)->and_where('pl.id_shop = :id_shop')->set_parameter('id_shop', $shop_id)->add_order_by('pc.date_add', 'DESC');
        if (!$skip_validate) {
            $qb->and_where('pc.validate = :validate')->set_parameter('validate', $validate);
        }
        if ($page && $limit) {
            $limit = (int) $limit;
            $offset = ($page - 1) * $limit;
            $qb->set_first_result($offset)->set_max_results($limit);
        }
        return $this->connection->execute_query($qb->get_sql(), $qb->get_parameters(), $qb->get_parameter_types())->fetch_all();
    }
    /**
     * @param int $validate
     * @param bool $skip_validate
     *
     * @return int
     */
    public function get_count_by_validate($validate = 0, $skip_validate = false)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('COUNT(*)')->from($this->database_prefix . 'product_comment', 'pc');
        if (!$skip_validate) {
            $qb->and_where('pc.validate = :validate')->set_parameter('validate', $validate);
        }
        return (int) $this->connection->execute_query($qb->get_sql(), $qb->get_parameters(), $qb->get_parameter_types())->fetch(\PDO::FETCH_COLUMN);
    }
    /**
     * @param bool $validatedOnly
     * @return array
     */
    public function get_average_grades(array $product_ids, $validated_only)
    {
        $sql = 'SELECT';
        $count = count($product_ids);
        foreach ($product_ids as $index => $id) {
            $esq_id = (int) $id;
            $sql .= ' SUM(IF(id_product = ' . $esq_id . ' AND deleted = 0';
            if ($validated_only) {
                $sql .= ' AND validate = 1';
            }
            $sql .= ',grade, 0))';
            $sql .= ' / SUM(IF(id_product = ' . $esq_id . ' AND deleted = 0';
            if ($validated_only) {
                $sql .= ' AND validate = 1';
            }
            $sql .= ',1, 0)) AS "' . $esq_id . '"';
            if ($count - 1 > $index) {
                $sql .= ',';
            }
        }
        $sql .= ' FROM ' . $this->database_prefix . 'product_comment';
        return $this->connection->execute_query($sql)->fetch();
    }
    /**
     * @param int $productId
     * @param bool $validatedOnly
     *
     * @return int
     */
    public function get_comments_number($product_id, $validated_only)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('COUNT(pc.id_product_comment) AS commentNb')->from($this->database_prefix . 'product_comment', 'pc')->and_where('pc.id_product = :id_product')->and_where('pc.deleted = :deleted')->set_parameter('deleted', 0)->set_parameter('id_product', $product_id);
        if ($validated_only) {
            $qb->and_where('pc.validate = :validate')->set_parameter('validate', 1);
        }
        return (int) $qb->execute()->fetch(\PDO::FETCH_COLUMN);
    }
    /**
     * @param bool $validatedOnly
     * @return array
     */
    public function get_comments_number_for_products(array $product_ids, $validated_only)
    {
        $sql = 'SELECT';
        $count = count($product_ids);
        foreach ($product_ids as $index => $id) {
            $esq_id = (int) $id;
            $sql .= ' SUM(IF(id_product = ' . $esq_id . ' AND deleted = 0';
            if ($validated_only) {
                $sql .= ' AND validate = 1';
            }
            $sql .= ' ,1, 0)) AS "' . $esq_id . '"';
            if ($count - 1 > $index) {
                $sql .= ',';
            }
        }
        $sql .= ' FROM ' . $this->database_prefix . 'product_comment';
        return (array) $this->connection->execute_query($sql)->fetch();
    }
    /**
     * @param int $productId
     * @param int $idCustomer
     * @param int $idGuest
     *
     * @return bool
     */
    public function is_post_allowed($product_id, $id_customer, $id_guest)
    {
        if (!$id_customer && !$this->guest_comments_allowed) {
            $post_allowed = false;
        } else {
            $last_customer_comment = null;
            if ($id_customer) {
                $last_customer_comment = $this->get_last_customer_comment($product_id, $id_customer);
            } elseif ($id_guest) {
                $last_customer_comment = $this->get_last_guest_comment($product_id, $id_guest);
            }
            $post_allowed = true;
            if (null !== $last_customer_comment && isset($last_customer_comment['date_add'])) {
                $post_date = new \DateTime($last_customer_comment['date_add'], new \DateTimeZone('UTC'));
                if (time() - $post_date->get_timestamp() < $this->comments_minimal_time) {
                    $post_allowed = false;
                }
            }
        }
        return $post_allowed;
    }
    /**
     * @param int $productId
     * @param int $idCustomer
     *
     * @return array
     */
    public function get_last_customer_comment($product_id, $id_customer)
    {
        return $this->get_last_comment(['id_product' => $product_id, 'id_customer' => $id_customer]);
    }
    /**
     * @param int $productId
     * @param int $idGuest
     *
     * @return array
     */
    public function get_last_guest_comment($product_id, $id_guest)
    {
        return $this->get_last_comment(['id_product' => $product_id, 'id_guest' => $id_guest]);
    }
    /**
     * @param int $customerId
     */
    public function clean_customer_data($customer_id): void
    {
        //We anonymize the customer comment by unlinking them (the name won't be visible any more but the grade and comment are still visible)
        $qb = $this->connection->create_query_builder();
        $qb->update($this->database_prefix . 'product_comment', 'pc')->set('id_customer', (string) 0)->and_where('pc.id_customer = :id_customer')->set_parameter('id_customer', $customer_id);
        $qb->execute();
        //But we remove every report and votes for comments
        $qb = $this->connection->create_query_builder();
        $qb->delete($this->database_prefix . 'product_comment_report')->and_where('id_customer = :id_customer')->set_parameter('id_customer', $customer_id);
        $qb->execute();
        $qb = $this->connection->create_query_builder();
        $qb->delete($this->database_prefix . 'product_comment_usefulness')->and_where('id_customer = :id_customer')->set_parameter('id_customer', $customer_id);
        $qb->execute();
    }
    /**
     * @param int $customerId
     * @param int $langId
     *
     * @return array
     */
    public function get_customer_data($customer_id, $lang_id)
    {
        $qb = $this->connection->create_query_builder();
        $qb->select('pl.name, pc.id_product, pc.id_product_comment, pc.title, pc.content, pc.grade, pc.validate, pc.deleted, pcu.usefulness, pc.date_add')->from($this->database_prefix . 'product_comment', 'pc')->left_join('pc', $this->database_prefix . 'product_comment_usefulness', 'pcu', 'pc.id_product_comment = pcu.id_product_comment')->left_join('pc', $this->database_prefix . 'product', 'p', 'pc.id_product = p.id_product')->left_join('p', $this->database_prefix . 'product_lang', 'pl', 'p.id_product = pl.id_product')->left_join('pl', $this->database_prefix . 'lang', 'l', 'pl.id_lang = l.id_lang')->and_where('pc.id_customer = :id_customer')->and_where('l.id_lang = :id_lang')->set_parameter('id_customer', $customer_id)->set_parameter('id_lang', $lang_id)->add_group_by('pc.id_product_comment')->add_order_by('pc.date_add', 'ASC');
        return $qb->execute()->fetch_all();
    }
    /**
     * @return array
     */
    private function get_last_comment(array $criteria)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->connection->create_query_builder();
        $qb->select('pc.*')->from($this->database_prefix . 'product_comment', 'pc')->and_where('pc.deleted = :deleted')->set_parameter('deleted', 0)->add_order_by('pc.date_add', 'DESC')->set_max_results(1);
        foreach ($criteria as $field => $value) {
            $qb->and_where(sprintf('pc.%s = :%s', $field, $field))->set_parameter($field, $value);
        }
        $comments = $qb->execute()->fetch_all();
        return empty($comments) ? [] : $comments[0];
    }
    /**
     * @param ProductComment $productComment
     * @param int $validate
     *
     * @return bool
     */
    public function validate($product_comment, $validate = 1)
    {
        $success = $this->connection->execute_update('
            UPDATE `' . _DB_PREFIX_ . 'product_comment` SET
            `validate` = ' . $validate . '
            WHERE `id_product_comment` = ' . $product_comment->get_id());
        Hook::exec('actionObjectProductCommentValidateAfter', ['object' => $product_comment]);
        return (bool) $success;
    }
    /**
     * @param int $id_product_comment
     *
     * @return bool
     */
    public function delete_grades($id_product_comment)
    {
        $success = $this->connection->execute_update('
		DELETE FROM `' . _DB_PREFIX_ . 'product_comment_grade`
		WHERE `id_product_comment` = ' . $id_product_comment);
        return (bool) $success;
    }
    /**
     * @param int $id_product_comment
     *
     * @return bool
     */
    public function delete_reports($id_product_comment)
    {
        $success = $this->connection->execute_update('
		DELETE FROM `' . $this->database_prefix . 'product_comment_report`
		WHERE `id_product_comment` = ' . $id_product_comment);
        return (bool) $success;
    }
    /**
     * @param int $id_product_comment
     *
     * @return bool
     */
    public function delete_usefulness($id_product_comment)
    {
        $success = $this->connection->execute_update('
		DELETE FROM `' . _DB_PREFIX_ . 'product_comment_usefulness`
		WHERE `id_product_comment` = ' . $id_product_comment);
        return (bool) $success;
    }
    /**
     * @param int $langId
     * @param int $shopId
     *
     * @return array
     */
    public function get_reported_comments($lang_id, $shop_id)
    {
        $sql = 'SELECT DISTINCT(pc.`id_product_comment`), pc.`id_product`, pc.`content`, pc.`grade`, pc.`date_add`, pc.`title`
        , IF(c.id_customer, CONCAT(c.`firstname`, \' \',  c.`lastname`), pc.customer_name) customer_name, pl.`name`
		FROM `' . $this->database_prefix . 'product_comment_report` pcr
		LEFT JOIN `' . $this->database_prefix . 'product_comment` pc
			ON pcr.id_product_comment = pc.id_product_comment
		LEFT JOIN `' . $this->database_prefix . 'customer` c ON (c.`id_customer` = pc.`id_customer`)
		LEFT JOIN `' . $this->database_prefix . 'product_lang` pl ON ' . '(pl.`id_product` = pc.`id_product` ' . ' AND pl.`id_lang` = ' . $lang_id . ' AND pl.`id_shop` = ' . $shop_id . ') 
        ORDER BY pc.`date_add` DESC';
        return $this->connection->execute_query($sql)->fetch_all();
    }
}