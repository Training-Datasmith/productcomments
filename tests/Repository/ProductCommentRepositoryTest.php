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

use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\Module\ProductComment\Tests\IntegrationTestCase;

class ProductCommentRepositoryTest extends IntegrationTestCase
{
    public function testUsefulnessTalliesUpvotesOnly()
    {
        $repo = $this->createCommentRepository();
        $commentId = $this->insertCommentRow(['title' => 'u1']);
        $otherId = $this->insertCommentRow(['title' => 'u2']);

        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$commentId, 1, 1]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$commentId, 2, 1]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$commentId, 3, 0]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$otherId, 4, 1]
        );

        $stats = $repo->getProductCommentUsefulness($commentId);
        $this->assertSame(2, $stats['usefulness']);
        $this->assertSame(3, $stats['total_usefulness']);

        $empty = $repo->getProductCommentUsefulness($otherId);
        $this->assertSame(1, $empty['usefulness']);
        $this->assertSame(1, $empty['total_usefulness']);
    }

    public function testAverageGradeExcludesDeletedAndOptionalUnvalidated()
    {
        $repo = $this->createCommentRepository();
        $productId = 100;
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 5, 'validate' => 1, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 4, 'validate' => 1, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 2, 'validate' => 0, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'grade' => 1, 'validate' => 1, 'deleted' => 1]);

        $all = $repo->getAverageGrade($productId, false);
        $this->assertInternalType('float', $all);
        $this->assertEqualsWithDelta(11 / 3, $all, 0.001);

        $validated = $repo->getAverageGrade($productId, true);
        $this->assertEqualsWithDelta(4.5, $validated, 0.001);

        $unknown = $repo->getAverageGrade(99999, false);
        $this->assertInternalType('float', $unknown);
        $this->assertSame(0.0, $unknown);
    }

    public function testCommentsNumber()
    {
        $repo = $this->createCommentRepository();
        $productId = 101;
        $this->insertCommentRow(['id_product' => $productId, 'validate' => 1, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'validate' => 1, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'validate' => 0, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => $productId, 'validate' => 1, 'deleted' => 1]);

        $this->assertSame(3, $repo->getCommentsNumber($productId, false));
        $this->assertSame(2, $repo->getCommentsNumber($productId, true));
        $this->assertSame(0, $repo->getCommentsNumber(88888, true));
    }

    public function testPaginateOrdersAndPages()
    {
        $repo = $this->createCommentRepository();
        $productId = 102;
        $this->insertCustomer(50, 'Del', 'eted', 1);
        $this->insertCustomer(51, 'Live', 'User', 0);
        $dates = [
            '2020-01-06 00:00:00',
            '2020-01-05 00:00:00',
            '2020-01-04 00:00:00',
            '2020-01-03 00:00:00',
            '2020-01-02 00:00:00',
            '2020-01-01 00:00:00',
        ];
        foreach ($dates as $index => $date) {
            $this->insertCommentRow([
                'id_product' => $productId,
                'title' => 'p' . $index,
                'date_add' => $date,
                'validate' => $index === 5 ? 0 : 1,
                'deleted' => $index === 4 ? 1 : 0,
                'id_customer' => $index === 0 ? 50 : ($index === 1 ? 51 : 0),
                'customer_name' => 'Guest',
            ]);
        }

        $page2 = $repo->paginate($productId, 2, 2, false);
        $this->assertCount(2, $page2);
        $this->assertSame('p2', $page2[0]['title']);
        $this->assertSame('p3', $page2[1]['title']);

        $validatedOnly = $repo->paginate($productId, 1, 10, true);
        $titles = array_column($validatedOnly, 'title');
        $this->assertSame(['p0', 'p1', 'p2', 'p3'], $titles);

        $defaultPage = $repo->paginate($productId, 1, 0, false);
        $this->assertCount(5, $defaultPage);

        $page1 = $repo->paginate($productId, 1, 10, false);
        $deletedCustomerRow = null;
        $liveCustomerRow = null;
        foreach ($page1 as $row) {
            if ($row['title'] === 'p0') {
                $deletedCustomerRow = $row;
            }
            if ($row['title'] === 'p1') {
                $liveCustomerRow = $row;
            }
        }
        $this->assertNotNull($deletedCustomerRow);
        $this->assertNotNull($liveCustomerRow);
        $this->assertNull($deletedCustomerRow['firstname']);
        $this->assertSame('Live', $liveCustomerRow['firstname']);
    }

    public function testIsPostAllowed()
    {
        $repo = $this->createCommentRepository(false, 30);
        $productId = 103;

        $this->assertFalse($repo->isPostAllowed($productId, 0, 7));

        $repoGuests = $this->createCommentRepository(true, 30);
        $this->assertTrue($repoGuests->isPostAllowed($productId, 10, 0));

        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 10,
            'date_add' => '2000-01-01 00:00:00',
            'title' => 'old',
        ]);
        $this->assertTrue($repoGuests->isPostAllowed($productId, 10, 0));

        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 10,
            'date_add' => '2099-01-01 00:00:00',
            'title' => 'recent',
        ]);
        $this->assertFalse($repoGuests->isPostAllowed($productId, 10, 0));
        $this->assertTrue($repoGuests->isPostAllowed(104, 10, 0));

        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 0,
            'id_guest' => 55,
            'date_add' => '2099-01-01 00:00:00',
            'title' => 'guest recent',
        ]);
        $this->assertFalse($repoGuests->isPostAllowed($productId, 0, 55));

        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 12,
            'date_add' => '2099-01-01 00:00:00',
            'deleted' => 1,
            'title' => 'deleted block',
        ]);
        $this->assertTrue($repoGuests->isPostAllowed($productId, 12, 0));

        $this->insertCommentRow([
            'id_product' => $productId,
            'id_customer' => 0,
            'id_guest' => 99,
            'date_add' => '2099-01-01 00:00:00',
            'title' => 'guest only',
        ]);
        $this->assertTrue($repoGuests->isPostAllowed($productId, 11, 0));
    }

    public function testGetByValidateFiltersShopLangAndValidation()
    {
        $repo = $this->createCommentRepository();
        $this->insertProduct(200, 1, 1, 'Mug');
        $this->insertProduct(200, 1, 2, 'Other');
        $this->insertCustomer(20, 'First', 'Last');
        $this->insertCommentRow([
            'id_product' => 200,
            'id_customer' => 20,
            'validate' => 1,
            'deleted' => 0,
            'title' => 'validated',
            'date_add' => '2020-02-02 00:00:00',
        ]);
        $this->insertCommentRow([
            'id_product' => 200,
            'id_customer' => 0,
            'customer_name' => 'Guest Only',
            'validate' => 0,
            'deleted' => 0,
            'title' => 'pending',
            'date_add' => '2020-02-01 00:00:00',
        ]);
        $this->insertCommentRow([
            'id_product' => 200,
            'validate' => 1,
            'deleted' => 1,
            'title' => 'gone',
        ]);

        $validated = $repo->getByValidate(1, 1, 1, false);
        $this->assertCount(1, $validated);
        $this->assertSame('validated', $validated[0]['title']);
        $this->assertSame('Mug', $validated[0]['name']);
        $this->assertSame('First Last', $validated[0]['customer_name']);

        $all = $repo->getByValidate(1, 1, 0, false, null, null, true);
        $this->assertCount(2, $all);
        $this->assertSame('validated', $all[0]['title']);

        $guestRow = null;
        foreach ($all as $row) {
            if ($row['title'] === 'pending') {
                $guestRow = $row;
            }
        }
        $this->assertNotNull($guestRow);
        $this->assertSame('Guest Only', $guestRow['customer_name']);

        $paged = $repo->getByValidate(1, 1, 0, false, 1, 1, true);
        $this->assertCount(1, $paged);
        $this->assertSame('validated', $paged[0]['title']);
    }

    public function testCountByValidateExcludesDeleted()
    {
        $repo = $this->createCommentRepository();
        $this->insertCommentRow(['validate' => 1, 'deleted' => 0, 'title' => 'live']);
        $this->insertCommentRow(['validate' => 1, 'deleted' => 1, 'title' => 'dead']);

        $this->assertSame(1, $repo->getCountByValidate(1));
        $this->assertSame(1, $repo->getCountByValidate(0, true));
    }

    public function testAverageGradesAndCommentCounts()
    {
        $repo = $this->createCommentRepository();
        $this->insertCommentRow(['id_product' => 300, 'grade' => 5, 'validate' => 1, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => 300, 'grade' => 3, 'validate' => 0, 'deleted' => 0]);
        $this->insertCommentRow(['id_product' => 300, 'grade' => 1, 'validate' => 1, 'deleted' => 1]);
        $this->insertCommentRow(['id_product' => 301, 'grade' => 4, 'validate' => 1, 'deleted' => 0]);

        $avgAll = $repo->getAverageGrades([300, 301, 302], false);
        $countAll = $repo->getCommentsNumberForProducts([300, 301, 302], false);
        $this->assertEqualsWithDelta(4.0, (float) $avgAll['300'], 0.001);
        $this->assertSame(2, (int) $countAll['300']);
        $this->assertEqualsWithDelta(4.0, (float) $avgAll['301'], 0.001);
        $this->assertSame(1, (int) $countAll['301']);
        $this->assertArrayHasKey('302', $countAll);
        $this->assertSame(0, (int) $countAll['302']);

        $avgValidated = $repo->getAverageGrades([300], true);
        $countValidated = $repo->getCommentsNumberForProducts([300], true);
        $this->assertEqualsWithDelta(5.0, (float) $avgValidated['300'], 0.001);
        $this->assertSame(1, (int) $countValidated['300']);
    }

    public function testAverageGradesEmptyIdList()
    {
        $repo = $this->createCommentRepository();
        $this->assertSame([], $repo->getAverageGrades([], false));
    }

    public function testCommentsNumberForProductsEmptyIdList()
    {
        $repo = $this->createCommentRepository();
        $this->assertSame([], $repo->getCommentsNumberForProducts([], false));
    }

    public function testCleanCustomerDataUnlinksAndDropsThatCustomersVotes()
    {
        $repo = $this->createCommentRepository();
        $commentId = $this->insertCommentRow(['id_customer' => 8, 'title' => 'gdpr']);
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_report (id_product_comment, id_customer) VALUES (?, ?)',
            [$commentId, 8]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$commentId, 8, 1]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, ?, ?)',
            [$commentId, 9, 1]
        );

        $repo->cleanCustomerData(8);

        $customerId = (int) self::$connection->fetchColumn(
            'SELECT id_customer FROM ps_product_comment WHERE id_product_comment = ?',
            [$commentId]
        );
        $this->assertSame(0, $customerId);
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_report WHERE id_customer = ?',
                [8]
            )
        );
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_usefulness WHERE id_customer = ?',
                [8]
            )
        );
        $this->assertSame(
            1,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_usefulness WHERE id_customer = ?',
                [9]
            )
        );
    }

    public function testValidateWritesColumnAndDispatchesHook()
    {
        $repo = $this->createCommentRepository();
        $commentId = $this->insertCommentRow(['validate' => 0, 'title' => 'approve me']);
        $entity = self::$entityManager->find(ProductComment::class, $commentId);

        $this->assertTrue($repo->validate($entity, 1));
        $validate = (int) self::$connection->fetchColumn(
            'SELECT validate FROM ps_product_comment WHERE id_product_comment = ?',
            [$commentId]
        );
        $this->assertSame(1, $validate);
        $this->assertCount(1, \Hook::$calls);
        $this->assertSame('actionObjectProductCommentValidateAfter', \Hook::$calls[0]['hook']);
        $this->assertSame($entity, \Hook::$calls[0]['params']['object']);
    }

    public function testDeleteRemovesCommentGradesReportsAndVotes()
    {
        $repo = $this->createCommentRepository();
        $commentId = $this->insertCommentRow(['title' => 'delete me']);
        $otherId = $this->insertCommentRow(['title' => 'keep']);
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_grade (id_product_comment, id_product_comment_criterion, grade) VALUES (?, 1, 5)',
            [$commentId]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_grade (id_product_comment, id_product_comment_criterion, grade) VALUES (?, 1, 4)',
            [$otherId]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_report (id_product_comment, id_customer) VALUES (?, 1)',
            [$commentId]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_usefulness (id_product_comment, id_customer, usefulness) VALUES (?, 1, 1)',
            [$commentId]
        );

        $entity = self::$entityManager->find(ProductComment::class, $commentId);
        $repo->delete($entity);
        self::$entityManager->clear();

        $this->assertNull(self::$entityManager->find(ProductComment::class, $commentId));
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_grade WHERE id_product_comment = ?',
                [$commentId]
            )
        );
        $this->assertSame(
            1,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_grade WHERE id_product_comment = ?',
                [$otherId]
            )
        );
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_report WHERE id_product_comment = ?',
                [$commentId]
            )
        );
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_usefulness WHERE id_product_comment = ?',
                [$commentId]
            )
        );
    }

    public function testDeleteReportsOnlyTouchesThatComment()
    {
        $repo = $this->createCommentRepository();
        $commentId = $this->insertCommentRow(['title' => 'reports']);
        $otherId = $this->insertCommentRow(['title' => 'other reports']);
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_report (id_product_comment, id_customer) VALUES (?, 1), (?, 2)',
            [$commentId, $commentId]
        );
        self::$connection->executeUpdate(
            'INSERT INTO ps_product_comment_report (id_product_comment, id_customer) VALUES (?, 3)',
            [$otherId]
        );

        $this->assertTrue($repo->deleteReports($commentId));
        $this->assertSame(
            0,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_report WHERE id_product_comment = ?',
                [$commentId]
            )
        );
        $this->assertSame(
            1,
            (int) self::$connection->fetchColumn(
                'SELECT COUNT(*) FROM ps_product_comment_report WHERE id_product_comment = ?',
                [$otherId]
            )
        );
        $this->assertFalse($repo->deleteReports(999999));
    }

    public function testFlushKeepsFractionalAverageGrade()
    {
        $comment = new ProductComment();
        $comment->setProductId(1);
        $comment->setCustomerId(0);
        $comment->setGuestId(0);
        $comment->setTitle('frac');
        $comment->setContent('body');
        $comment->setCustomerName('Guest');
        $comment->setGrade(4.5);
        $comment->setDateAdd(new \DateTime('2020-01-01 00:00:00', new \DateTimeZone('UTC')));

        self::$entityManager->persist($comment);
        self::$entityManager->flush();
        $id = $comment->getId();
        self::$entityManager->clear();

        $stored = (float) self::$connection->fetchColumn(
            'SELECT grade FROM ps_product_comment WHERE id_product_comment = ?',
            [$id]
        );
        $this->assertEqualsWithDelta(4.5, $stored, 0.001);

        $reloaded = self::$entityManager->find(ProductComment::class, $id);
        $this->assertEqualsWithDelta(4.5, (float) $reloaded->getGrade(), 0.001);
    }
}
