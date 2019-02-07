<?php

declare(strict_types=1);

namespace Pim\Bundle\CatalogBundle\Elasticsearch\Filter\Field;

use Pim\Component\Catalog\Exception\InvalidOperatorException;
use Pim\Component\Catalog\Query\Filter\FieldFilterHelper;
use Pim\Component\Catalog\Query\Filter\Operators;

/**
 * An ancestor is a product model that is either a parent or a grand parent.
 * Look for documents having the given ancestor(s).
 *
 * Imagine the following tree:
 *      RPM
 *         \PM1
 *            \P11
 *            \P12
 *         \PM2
 *            \P21
 *
 * Using this filter with "IN LIST PM1" would return:
 *         \PM1
 *            \P11
 *            \P12
 *
 * Contrary to the ancestor filter, here PM1 itself is as well returned.
 *
 * @author    Damien Carcel <damien.carcel@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SelfAndAncestorFilterLabelOrIdentifier extends AbstractFieldFilter
{
    /**
     * @param array $supportedFields
     * @param array $supportedOperators
     */
    public function __construct(
        array $supportedFields,
        array $supportedOperators
    ) {
        $this->supportedFields = $supportedFields;
        $this->supportedOperators = $supportedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldFilter($field, $operator, $value, $locale = null, $channel = null, $options = []): void
    {
        if (null === $this->searchQueryBuilder) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        if (!$this->supportsOperator($operator)) {
            throw InvalidOperatorException::notSupported($operator, SelfAndAncestorFilter::class);
        }

        $this->checkValue($value);

        $clauses[] = [
            'wildcard' => [
                'ancestors.code' => sprintf('*%s*', $this->escapeValue($value)),
            ]
        ];

        if (null !== $channel && null !== $locale) {
            $clauses[] = [
                'wildcard' => [
                    sprintf('ancestors.label.%s.%s', $channel, $locale) => sprintf(
                        '*%s*',
                        $this->escapeValue($value)
                    ),
                ]
            ];
        }

        if (null !== $channel) {
            $clauses[] = [
                'wildcard' => [
                    sprintf('ancestors.label.%s.<all_locales>', $channel) => sprintf(
                        '*%s*',
                        $this->escapeValue($value)
                    ),
                ]
            ];
        }

        if (null !== $locale) {
            $clauses[] = [
                'wildcard' => [
                    sprintf('ancestors.label.<all_channels>.%s', $locale) => sprintf(
                        '*%s*',
                        $this->escapeValue($value)
                    ),
                ]
            ];
        }

        $clauses[] = [
            'wildcard' => [
                'ancestors.label.<all_channels>.<all_locales>' => sprintf('*%s*', $this->escapeValue($value)),
            ]
        ];

        $this->searchQueryBuilder->addFilter(
            [
                'bool' => [
                    'should' => $clauses,
                    'minimum_should_match' => 1,
                ],
            ]
        );
    }

    /**
     * @param string $value
     */
    protected function checkValue(string $value): void
    {
        FieldFilterHelper::checkString('self_and_ancestor.label_or_identifier', $value, static::class);
    }

    /**
     * Escapes particular values prior than doing a search query escaping whitespace or newlines.
     *
     * This is useful when using ES 'query_string' clauses in a search query.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_reserved_characters
     *
     * TODO: TIP-706 - This may move somewhere else
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeValue(string $value): string
    {
        $regex = '#[-+=|! &(){}\[\]^"~*<>?:/\\\]#';

        return preg_replace($regex, '\\\$0', $value);
    }
}
