<?php

namespace spec\Pim\Bundle\CatalogBundle\Updater\Setter;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\CatalogBundle\Builder\ProductBuilderInterface;
use Pim\Bundle\CatalogBundle\Model\AttributeInterface;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\CatalogBundle\Model\ProductValueInterface;
use Pim\Bundle\CatalogBundle\Updater\InvalidArgumentException;
use Pim\Bundle\CatalogBundle\Validator\AttributeValidatorHelper;
use Prophecy\Argument;

class DateValueSetterSpec extends ObjectBehavior
{
    function let(ProductBuilderInterface $builder, AttributeValidatorHelper $attrValidatorHelper)
    {
        $this->beConstructedWith($builder, $attrValidatorHelper, ['pim_catalog_date']);
    }

    function it_is_a_setter()
    {
        $this->shouldImplement('Pim\Bundle\CatalogBundle\Updater\Setter\SetterInterface');
    }

    function it_supports_date_attributes(
        AttributeInterface $dateAttribute,
        AttributeInterface $textareaAttribute
    ) {
        $dateAttribute->getAttributeType()->willReturn('pim_catalog_date');
        $this->supports($dateAttribute)->shouldReturn(true);

        $textareaAttribute->getAttributeType()->willReturn('pim_catalog_textarea');
        $this->supports($textareaAttribute)->shouldReturn(false);
    }

    function it_checks_locale_and_scope_when_setting_a_value(
        $attrValidatorHelper,
        AttributeInterface $attribute
    ) {
        $attrValidatorHelper->validateLocale(Argument::cetera())->shouldBeCalled();
        $attrValidatorHelper->validateScope(Argument::cetera())->shouldBeCalled();

        $this->setValue([], $attribute, '1970-01-01', 'fr_FR', 'mobile');
    }

    function it_throws_an_error_if_data_is_not_a_valid_date_format(
        AttributeInterface $attribute
    ) {
        $attribute->getCode()->willReturn('attributeCode');

        $data = 'not a date';

        $this->shouldThrow(
            InvalidArgumentException::expected(
                'attributeCode',
                'a string with the format yyyy-mm-dd',
                'setter',
                'date',
                gettype($data)
            )
        )->during('setValue', [[], $attribute, $data, 'fr_FR', 'mobile']);
    }

    function it_throws_an_error_if_data_is_not_correctly_formatted(
        AttributeInterface $attribute
    ) {
        $attribute->getCode()->willReturn('attributeCode');

        $data = '1970-mm-01';

        $this->shouldThrow(
            InvalidArgumentException::expected(
                'attributeCode',
                'a string with the format yyyy-mm-dd',
                'setter',
                'date',
                gettype($data)
            )
        )->during('setValue', [[], $attribute, $data, 'fr_FR', 'mobile']);
    }

    function it_allows_setting_data_to_null(
        ProductInterface $product,
        AttributeInterface $attribute,
        ProductValueInterface $value
    ) {
        $attribute->getCode()->willReturn('attributeCode');

        $product->getValue('attributeCode', null, null)->shouldBeCalled()->willReturn($value);

        $value->setData(null)->shouldBeCalled();

        $this->setValue([$product], $attribute, null);
    }

    function it_throws_an_error_if_data_is_not_a_string_or_null(AttributeInterface $attribute)
    {
        $attribute->getCode()->willReturn('attributeCode');

        $data = new \Datetime();

        $this->shouldThrow(
            InvalidArgumentException::stringExpected('attributeCode', 'setter', 'date', gettype($data))
        )->during('setValue', [[], $attribute, $data, 'fr_FR', 'mobile']);
    }

    function it_sets_date_value_to_a_product_value(
        AttributeInterface $attribute,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        $builder,
        ProductValueInterface $productValue
    ) {
        $locale = 'fr_FR';
        $scope = 'mobile';
        $data = '1970-01-01';

        $attribute->getCode()->willReturn('attributeCode');
        $productValue->setData(Argument::type('\Datetime'))->shouldBeCalled();

        $builder
            ->addProductValue($product2, $attribute, $locale, $scope)
            ->willReturn($productValue);

        $product1->getValue('attributeCode', $locale, $scope)->shouldBeCalled()->willReturn($productValue);
        $product2->getValue('attributeCode', $locale, $scope)->willReturn(null);
        $product3->getValue('attributeCode', $locale, $scope)->willReturn($productValue);

        $products = [$product1, $product2, $product3];

        $this->setValue($products, $attribute, $data, $locale, $scope);
    }
}
