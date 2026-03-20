<?php

declare(strict_types=1);

/**
 * Example: Working with the productcomments PrestaShop module.
 *
 * This module adds product review/rating functionality to PrestaShop stores.
 * Customers can leave star ratings and written reviews on product pages.
 * The module integrates via PrestaShop hooks and Doctrine ORM entities.
 *
 * Install:
 *   php bin/console prestashop:module install productcomments
 *
 * This file documents common usage patterns and integration points.
 */

// --- Hook: displayProductAdditionalInfo ---
// The module registers on this hook to render the review form and listing
// on the product detail page. No direct PHP call needed — PrestaShop
// dispatches the hook automatically when rendering product pages.

// --- Accessing product comments programmatically ---
// Use the repository service registered in the module's container:
//
// $repository = $this->get('presta_shop.module.product_comment.repository.product_comment');
// $comments = $repository->findBy(['id_product' => $productId, 'validated' => 1]);
//
// foreach ($comments as $comment) {
//     echo $comment->getTitle() . ': ' . $comment->getContent() . "\n";
//     echo 'Rating: ' . $comment->getGrade() . '/5' . "\n";
// }

// --- Querying with the ProductCommentRepository ---
// The repository provides methods for common query patterns:
//
// // Get all validated comments for a product
// $validated = $repository->getByProduct($productId, validated: true);
//
// // Get comments pending moderation
// $pending = $repository->getByProduct($productId, validated: false);
//
// // Get average rating for a product
// $average = $repository->getAverageGrade($productId);

// --- Criterion (grade category) configuration ---
// Criteria allow per-category rating dimensions (e.g., "Quality", "Value").
// Configured in Back Office > Modules > Product Comments > Manage criteria.
//
// $criterionRepo = $this->get('presta_shop.module.product_comment.repository.product_comment_criterion');
// $criteria = $criterionRepo->findAll();
//
// foreach ($criteria as $criterion) {
//     echo $criterion->getName() . "\n"; // e.g., "Quality", "Delivery"
// }

// --- Hook: actionProductCommentValidate ---
// Fired when an admin validates (approves) a comment in the Back Office.
// Register a hook listener to trigger custom logic on approval:
//
// Hook::register('actionProductCommentValidate', 'MyModule', 'myMethodName');
//
// public function myMethodName(array $params): void
// {
//     $comment = $params['product_comment'];
//     // Send approval notification, update external system, etc.
// }

// --- Module configuration via Back Office ---
// Settings available at: Back Office > Modules > Product Comments
//   - Allow guests to post reviews
//   - Require purchase before posting
//   - Enable moderation (manual approval)
//   - Minimum time between reviews per customer
//   - Notify merchant on new review

// --- Template override ---
// Override templates in your theme at:
//   themes/{theme}/modules/productcomments/views/templates/
