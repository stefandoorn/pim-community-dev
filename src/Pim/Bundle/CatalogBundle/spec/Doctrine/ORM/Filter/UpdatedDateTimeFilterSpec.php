<?php

namespace spec\Pim\Bundle\CatalogBundle\Doctrine\ORM\Filter;

use Akeneo\Component\Batch\Job\JobRepositoryInterface;
use Akeneo\Component\Batch\Model\JobExecution;
use Akeneo\Component\Batch\Model\JobInstance;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Query\Filter\Operators;
use Prophecy\Argument;

class UpdatedDateTimeFilterSpec extends ObjectBehavior
{
    function let(
        QueryBuilder $qb,
        JobInstanceRepository $jobInstanceRepository,
        JobRepositoryInterface $jobRepository
    ) {
        $this->beConstructedWith(
            $jobInstanceRepository,
            $jobRepository,
            ['updated'],
            [Operators::SINCE_LAST_N_DAYS, Operators::SINCE_LAST_EXPORT]
        );
        $this->setQueryBuilder($qb);
    }

    function it_is_a_field_filter()
    {
        $this->shouldImplement('Pim\Component\Catalog\Query\Filter\FieldFilterInterface');
    }

    function it_supports_operators()
    {
        $this->getOperators()->shouldReturn([Operators::SINCE_LAST_N_DAYS, Operators::SINCE_LAST_EXPORT]);
        $this->supportsOperator('IN')->shouldReturn(false);
        $this->supportsOperator(Operators::SINCE_LAST_N_DAYS)->shouldReturn(true);
    }

    function it_returns_supported_fields()
    {
        $this->getFields()->shouldReturn(['updated']);
    }

    function it_adds_an_updated_since_last_n_days_filter($qb, Expr $expr, Comparison $comparison)
    {
        $qb->getRootAliases()->willReturn(['alias']);
        $qb->andWhere($comparison)->shouldBeCalled();
        $qb->expr()->willReturn($expr);
        $expr->gt('alias.updated', Argument::type('string'))->shouldBeCalled()->willReturn($comparison);

        $expr->literal(Argument::type('string'))->shouldBeCalled()->willReturn('2016-06-20 16:42:42');
        $expr->gt('alias.updated', '2016-06-20 16:42:42')->shouldBeCalled()->willReturn($comparison);

        $this->addFieldFilter(
            'updated',
            Operators::SINCE_LAST_N_DAYS,
            30,
            null,
            null
        );
    }

    function it_adds_an_updated_since_last_export_filter(
        $qb,
        $jobInstanceRepository,
        $jobRepository,
        JobInstance $jobInstance,
        JobExecution $jobExecution,
        \DateTime $startTime,
        Expr $expr,
        Comparison $comparison
    ) {
        $jobInstanceRepository->findOneBy(['code' => 'csv_product_export'])->willReturn($jobInstance);
        $jobRepository->getLastJobExecution($jobInstance, 1)->shouldBeCalled()->willReturn($jobExecution);

        $jobExecution->getStartTime()->willReturn($startTime);
        $startTime->format('Y-m-d H:i:s')->willReturn('2016-06-20 16:42:42');

        $qb->getRootAliases()->willReturn(['alias']);
        $qb->expr()->willReturn($expr);
        $qb->andWhere($comparison)->shouldBeCalled();

        $expr->literal('2016-06-20 16:42:42')->shouldBeCalled()->willReturn('2016-06-20 16:42:42');
        $expr->gt('alias.updated', '2016-06-20 16:42:42')->shouldBeCalled()->willReturn($comparison);

        $this->addFieldFilter(
            'updated',
            Operators::SINCE_LAST_EXPORT,
            'csv_product_export',
            null,
            null
        );
    }

    function it_does_not_add_an_updated_since_last_export_filter_if_no_option_given(
        $qb,
        $jobInstanceRepository
    ) {
        $jobInstanceRepository->findOneBy(['code' => 'csv_product_export'])->willReturn(null);

        $qb->andWhere(Argument::cetera())->shouldNotBeCalled();

        $this->addFieldFilter(
            'updated',
            Operators::SINCE_LAST_EXPORT,
            'csv_product_export',
            null,
            null
        );
    }

    function it_throws_an_exception_if_value_is_wrong()
    {
        $this
            ->shouldThrow(
                InvalidArgumentException::stringExpected('updated', 'filter', 'updated', 'integer')
            )->during(
                'addFieldFilter',
                [
                    'updated',
                    'SINCE LAST EXPORT',
                    42,
                    null,
                    null,
                ]
            );

        $this
            ->shouldThrow(
                InvalidArgumentException::numericExpected('updated', 'filter', 'updated', 'string')
            )->during(
                'addFieldFilter',
                [
                    'updated',
                    'SINCE LAST N DAYS',
                    'csv_product_export',
                    null,
                    null
                ]
            );
    }
}
