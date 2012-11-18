<?php
namespace Pim\Bundle\CatalogBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Pim\Bundle\CatalogBundle\Doctrine\ProductManager;
use Pim\Bundle\CatalogBundle\Tests\KernelAwareTest;

/**
 * Test related class
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright Copyright (c) 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductTypeTest extends KernelAwareTest
{

    /**
     * Build form
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function testBuildForm()
    {
        $productManager = $this->container->get('pim.catalog.product_manager');
        $classFullName = $productManager->getEntityClass();
        $entity = $productManager->getNewEntityInstance();
        $this->container->get('form.factory')->create(new ProductType($classFullName), $entity);
    }

}
