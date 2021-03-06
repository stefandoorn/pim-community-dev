<?php

namespace Specification\Akeneo\Pim\Enrichment\Component\Product\Connector\Reader\Database\MassEdit;

use Akeneo\Channel\Component\Model\ChannelInterface;
use Akeneo\Channel\Component\Repository\ChannelRepositoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Connector\Reader\Database\MassEdit\FilteredProductAndProductModelReader;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderFactoryInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\ProductQueryBuilderInterface;
use Akeneo\Pim\Enrichment\Component\Product\Converter\MetricConverter;
use Akeneo\Tool\Component\Batch\Job\JobParameters;
use Akeneo\Tool\Component\Batch\Model\StepExecution;
use Akeneo\Tool\Component\StorageUtils\Cursor\CursorInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Promise\ReturnPromise;

class FilteredProductAndProductModelReaderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(FilteredProductAndProductModelReader::class);
    }

    function let(
        ProductQueryBuilderFactoryInterface $pqbFactory,
        ChannelRepositoryInterface $channelRepository,
        MetricConverter $metricConverter,
        StepExecution $stepExecution
    ) {
        $this->beConstructedWith(
            $pqbFactory,
            $channelRepository,
            $metricConverter,
            false
        );

        $this->setStepExecution($stepExecution);
    }

    function it_set_step_execution(
        $stepExecution
    ) {
        $this->setStepExecution($stepExecution)->shouldReturn(null);
    }

    function it_reads_products_only_and_not_product_models(
        $pqbFactory,
        $channelRepository,
        $metricConverter,
        $stepExecution,
        ChannelInterface $channel,
        ProductQueryBuilderInterface $pqb,
        CursorInterface $cursor,
        ProductModelInterface $productModel1,
        ProductModelInterface $productModel2,
        ProductModelInterface $productModel3,
        ProductInterface $product1,
        ProductInterface $product2,
        ProductInterface $product3,
        JobParameters $jobParameters
    ) {
        $filters = [
            'data'      => [
                [
                    'field'    => 'enabled',
                    'operator' => '=',
                    'value'    => true,
                ],
                [
                    'field'    => 'family',
                    'operator' => 'IN',
                    'value'    => [
                        'camcorder',
                    ],
                ],
            ],
            'structure' => [
                'scope'   => 'mobile',
                'locales' => ['fr_FR', 'en_US'],
            ],
        ];
        $stepExecution->getJobParameters()->willReturn($jobParameters);
        $jobParameters->get('filters')->willReturn($filters);

        $channelRepository->findOneByIdentifier('mobile')->willReturn($channel);
        $channel->getCode()->willReturn('mobile');

        $pqbFactory->create(['filters' => $filters['data'], 'default_scope' => 'mobile'])
            ->shouldBeCalled()
            ->willReturn($pqb);
        $pqb->execute()
            ->shouldBeCalled()
            ->willReturn($cursor);

        $products = [$productModel1, $product1, $productModel2, $product2, $product3, $productModel3];
        $productsCount = count($products);
        $cursor->valid()->will(
            function () use (&$productsCount) {
                return $productsCount-- > 0;
            }
        );
        $cursor->current()->will(new ReturnPromise($products));
        $cursor->next()->shouldBeCalledTimes(5);

        $stepExecution->incrementSummaryInfo('read')->shouldBeCalledTimes(3);
        $metricConverter->convert(Argument::any(), $channel)->shouldBeCalledTimes(3);

        $productModel1->getCode()->willReturn('product_model_1');
        $productModel2->getCode()->willReturn('product_model_2');
        $productModel3->getCode()->willReturn('product_model_3');
        $stepExecution->addWarning('This bulk action doesn\'t support Product models entities yet.', Argument::cetera())
            ->shouldBeCalledTimes(3);

        $this->initialize();
        $this->read()->shouldReturn($product1);
        $this->read()->shouldReturn($product2);
        $this->read()->shouldReturn($product3);
        $this->read()->shouldReturn(null);
    }
}
