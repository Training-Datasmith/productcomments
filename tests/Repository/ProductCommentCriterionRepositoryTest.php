<?php
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
 */

namespace PrestaShop\Module\ProductComment\Tests\Repository;

use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterionLang;
use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;
use PrestaShopBundle\Entity\Lang;

class ProductCommentCriterionRepositoryTest extends IntegrationTestCase
{
    public function testGetByProductHonorsScopeActiveAndLang()
    {
        $repo = $this->createCriterionRepository();
        $productId = 500;
        $this->insertProduct($productId, 1, 1, 'Scope product');

        $rows = $repo->getByProduct($productId, 1);
        $names = array_column($rows, 'name');
        $this->assertContains('Quality', $names);

        $inactiveId = $this->insertCriterionRow(1, 0, 'Inactive catalog');
        $rows = $repo->getByProduct($productId, 1);
        $ids = array_column($rows, 'id_product_comment_criterion');
        $this->assertNotContains($inactiveId, $ids);

        $productCriterionId = $this->insertCriterionRow(3, 1, 'Product only');
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion_product (id_product_comment_criterion, id_product) VALUES (?, ?)',
            [$productCriterionId, $productId]
        );
        $this->assertContains(
            'Product only',
            array_column($repo->getByProduct($productId, 1), 'name')
        );
        $this->assertNotContains(
            'Product only',
            array_column($repo->getByProduct(501, 1), 'name')
        );

        $categoryId = 10;
        self::$connection->executeUpdate('INSERT INTO ps_category (id_category) VALUES (?)', [$categoryId]);
        self::$connection->executeUpdate(
            'INSERT INTO ps_category_product (id_category, id_product) VALUES (?, ?)',
            [$categoryId, $productId]
        );
        $categoryCriterionId = $this->insertCriterionRow(2, 1, 'Category scoped');
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion_category (id_product_comment_criterion, id_category) VALUES (?, ?)',
            [$categoryCriterionId, $categoryId]
        );
        $this->assertContains(
            'Category scoped',
            array_column($repo->getByProduct($productId, 1), 'name')
        );

        $this->insertCriterionLangName($productCriterionId, 2, 'Produit');
        $french = $repo->getByProduct($productId, 1);
        $this->assertNotContains('Produit', array_column($french, 'name'));
    }

    public function testGetProductsAndCategoriesReturnInts()
    {
        $repo = $this->createCriterionRepository();
        $criterionId = $this->insertCriterionRow(3, 1, 'Links');
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion_product (id_product_comment_criterion, id_product) VALUES (?, ?), (?, ?)',
            [$criterionId, 5, $criterionId, 6]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion_category (id_product_comment_criterion, id_category) VALUES (?, ?)',
            [$criterionId, 7]
        );

        $products = $repo->getProducts($criterionId);
        $this->assertSame([5, 6], $products);
        foreach ($products as $productId) {
            $this->assertInternalType('int', $productId);
        }

        $categories = $repo->getCategories($criterionId);
        $this->assertSame([7], $categories);
        $this->assertInternalType('int', $categories[0]);
        $this->assertSame([], $repo->getProducts(99999));
    }

    public function testGetCriterionsFiltersAndSorts()
    {
        $repo = $this->createCriterionRepository();
        $this->insertCriterionRow(3, 1, 'Zzz');
        $this->insertCriterionRow(3, 0, 'Hidden');
        $this->insertCriterionRow(1, 1, 'Aaa');

        $filtered = $repo->getCriterions(1, 1, true);
        $names = array_column($filtered, 'name');
        $this->assertContains('Quality', $names);
        $this->assertContains('Aaa', $names);
        $this->assertNotContains('Zzz', $names);
        $this->assertNotContains('Hidden', $names);

        $all = $repo->getCriterions(1, false, false);
        $allNames = array_column($all, 'name');
        $this->assertContains('Aaa', $allNames);
        $this->assertLessThan(array_search('Zzz', $allNames), array_search('Aaa', $allNames));
        $row = null;
        foreach ($all as $item) {
            if ($item['name'] === 'Quality') {
                $row = $item;
            }
        }
        $this->assertNotNull($row);
        $types = $repo->getTypes();
        $this->assertSame($types[$row['id_product_comment_criterion_type']], $row['type_name']);
    }

    public function testUpdateReplacesProductLinks()
    {
        $repo = $this->createCriterionRepository();
        $criterion = $this->createCriterionEntity(3, true, 'Update products');
        $criterion->setProducts([5, 6]);
        $repo->update($criterion);
        $this->assertSame([5, 6], $repo->getProducts($criterion->getId()));

        $criterion->setProducts([6]);
        $repo->update($criterion);
        $this->assertSame([6], $repo->getProducts($criterion->getId()));

        $categoryCriterion = $this->createCriterionEntity(2, true, 'Update categories');
        $categoryCriterion->setCategories([2, 3]);
        $repo->update($categoryCriterion);
        $this->assertSame([2, 3], $repo->getCategories($categoryCriterion->getId()));
        $categoryCriterion->setCategories([3]);
        $repo->update($categoryCriterion);
        $this->assertSame([3], $repo->getCategories($categoryCriterion->getId()));

        $catalog = $this->createCriterionEntity(1, true, 'Catalog toggle');
        $result = $repo->update($catalog);
        $this->assertSame(1, $result);
        $catalog->setActive(false);
        $repo->updateGeneral($catalog);
        self::$entityManager->clear();
        $stored = self::$entityManager->find(ProductCommentCriterion::class, $catalog->getId());
        $this->assertFalse($stored->isActive());
    }

    public function testDeleteRemovesLinksGradesAndCriterion()
    {
        $repo = $this->createCriterionRepository();
        $criterion = $this->createCriterionEntity(3, true, 'Delete me');
        $criterion->setProducts([1]);
        $repo->update($criterion);
        $commentId = $this->insertCommentRow(['title' => 'grade holder']);
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_grade (id_product_comment, id_product_comment_criterion, grade) VALUES (?, ?, 5)',
            [$commentId, $criterion->getId()]
        );

        $criterionId = $criterion->getId();
        $repo->delete($criterion);
        self::$entityManager->clear();

        $this->assertNull(self::$entityManager->find(ProductCommentCriterion::class, $criterionId));
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_criterion_product WHERE id_product_comment_criterion = ?',
                [$criterionId]
            )
        );
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_grade WHERE id_product_comment_criterion = ?',
                [$criterionId]
            )
        );
        $this->assertSame(
            1,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment WHERE id_product_comment = ?',
                [$commentId]
            )
        );
    }

    public function testGetTypesKeys()
    {
        $types = $this->createCriterionRepository()->getTypes();
        $this->assertArrayHasKey(1, $types);
        $this->assertArrayHasKey(2, $types);
        $this->assertArrayHasKey(3, $types);
        $this->assertSame('Valid for the entire catalog', $types[1]);
        $this->assertSame('Restricted to some categories', $types[2]);
        $this->assertSame('Restricted to some products', $types[3]);
    }

    private function insertCriterionRow($type, $active, $name, $langId = 1)
    {
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion (id_product_comment_criterion_type, active) VALUES (?, ?)',
            [$type, $active]
        );
        $id = (int) self::$connection->lastInsertId();
        $this->insertCriterionLangName($id, $langId, $name);

        return $id;
    }

    private function insertCriterionLangName($criterionId, $langId, $name)
    {
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_criterion_lang (id_product_comment_criterion, id_lang, name) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)',
            [$criterionId, $langId, $name]
        );
    }

    private function createCriterionEntity($type, $active, $name)
    {
        $lang = self::$entityManager->find(Lang::class, 1);
        $criterion = new ProductCommentCriterion();
        $criterion->setType($type);
        $criterion->setActive($active);
        $criterionLang = new ProductCommentCriterionLang();
        $criterionLang->setLang($lang)->setName($name);
        $criterion->addCriterionLang($criterionLang);
        self::$entityManager->persist($criterion);
        self::$entityManager->flush();

        return $criterion;
    }
}
