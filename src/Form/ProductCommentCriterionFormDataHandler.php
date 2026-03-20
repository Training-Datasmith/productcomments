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

use Doctrine\ORM\Entity_Manager_Interface;
use Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion;
use Presta_Shop\Module\Product_Comment\Entity\Product_Comment_Criterion_Lang;
use Presta_Shop\Module\Product_Comment\Repository\Product_Comment_Criterion_Repository;
use Presta_Shop\Presta_Shop\Core\Form\Identifiable_Object\Data_Handler\Form_Data_Handler_Interface;
use Presta_Shop_Bundle\Entity\Repository\Lang_Repository;
class Product_Comment_Criterion_Form_Data_Handler implements Form_Data_Handler_Interface
{
    /**
     * @var LangRepository
     */
    private $lang_repository;
    /**
     * @var EntityManagerInterface
     */
    private $entity_manager;
    public function __construct(Product_Comment_Criterion_Repository $pccriterion_repository, Lang_Repository $lang_repository, Entity_Manager_Interface $entity_manager)
    {
        $this->lang_repository = $lang_repository;
        $this->entity_manager = $entity_manager;
    }
    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
    }
    /**
     * @param ProductCommentCriterion $pccriterion
     * @param array $pcc_languages
     *
     * @todo migrate this temporary function to above standard function create
     */
    public function create_langs($pccriterion, $pcc_languages): void
    {
        foreach ($pcc_languages as $lang_id => $lang_content) {
            $lang = $this->lang_repository->find($lang_id);
            $pccriterion_lang = new Product_Comment_Criterion_Lang();
            $pccriterion_lang->set_lang($lang)->set_name($lang_content);
            $pccriterion->add_criterion_lang($pccriterion_lang);
        }
        $this->entity_manager->persist($pccriterion);
        $this->entity_manager->flush();
    }
    /**
     * @param ProductCommentCriterion $pccriterion
     * @param array $pcc_languages
     *
     * @todo migrate this temporary function to above standard function update
     */
    public function update_langs($pccriterion, $pcc_languages): void
    {
        foreach ($pcc_languages as $lang_id => $lang_content) {
            $lang = $this->lang_repository->find($lang_id);
            $pccriterion_lang = $pccriterion->get_criterion_lang_by_lang_id($lang_id);
            if (null === $pccriterion_lang) {
                continue;
            }
            $pccriterion_lang->set_name($lang_content);
        }
        $this->entity_manager->persist($pccriterion);
        $this->entity_manager->flush();
    }
}