<?php
namespace Pim\Bundle\CatalogBundle\Doctrine;

use Bap\Bundle\FlexibleEntityBundle\Doctrine\FlexibleEntityManager;

/**
 * Manager of flexible product stored with doctrine
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright Copyright (c) 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductManager extends FlexibleEntityManager
{

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getEntityShortname()
    {
        return 'PimCatalogBundle:ProductEntity';
    }

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getSetShortname()
    {
        return 'PimCatalogBundle:ProductSet';
    }

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getGroupShortname()
    {
        return 'PimCatalogBundle:ProductGroup';
    }

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getAttributeShortname()
    {
        return 'PimCatalogBundle:ProductAttribute';
    }

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getAttributeOptionShortname()
    {
        return 'PimCatalogBundle:ProductAttributeOption';
    }

    /**
     * Return shortname that can be used to get the repository or instance
     * @return string
     */
    public function getAttributeValueShortname()
    {
        return 'PimCatalogBundle:ProductAttributeValue';
    }

}
