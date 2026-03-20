<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
declare (strict_types=1);
namespace Presta_Shop\Module\Product_Comment\Form;

use Presta_Shop\Module\Product_Comment\Repository\Product_Comment_Criterion_Repository;
use Presta_Shop\Presta_Shop\Core\Form\Identifiable_Object\Data_Provider\Form_Data_Provider_Interface;
use Presta_Shop_Bundle\Entity\Repository\Lang_Repository;
class Product_Comment_Criterion_Form_Data_Provider implements Form_Data_Provider_Interface
{
    /**
     * @var ProductCommentCriterionRepository
     */
    private $pccriterion_repository;
    /**
     * @var LangRepository
     */
    private $lang_repository;
    public function __construct(Product_Comment_Criterion_Repository $pccriterion_repository, Lang_Repository $lang_repository)
    {
        $this->pccriterion_repository = $pccriterion_repository;
        $this->lang_repository = $lang_repository;
    }
    /**
     * {@inheritdoc}
     */
    public function get_data($criterion_id): array
    {
        $criterion = $this->pccriterion_repository->find($criterion_id);
        $criterion_data = ['type' => $criterion->get_type(), 'active' => $criterion->is_active()];
        foreach ($criterion->get_criterion_langs() as $criterion_lang) {
            $criterion_data['name'][$criterion_lang->get_lang()->get_id()] = $criterion_lang->get_name();
        }
        return $criterion_data;
    }
    /**
     * {@inheritdoc}
     */
    public function get_default_data(): array
    {
        $default_name = [];
        $lang_entities = $this->lang_repository->find_by(['active' => 1]);
        foreach ($lang_entities as $lang_entity) {
            $default_name[$lang_entity->get_id()] = $lang_entity->get_iso_code();
        }
        return ['type' => '', 'active' => false, 'name' => $default_name];
    }
}