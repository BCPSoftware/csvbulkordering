<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Csv;

/**
 * Class Template
 */
class Template extends AbstractCsv
{
    /**
     * @var array
     */
    private const CSV_HEAD = [
        [
            'Sku',
            'Qty',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected function getContent(): array
    {
        return self::CSV_HEAD;
    }

    /**
     * @inheritDoc
     */
    protected function getOutputFile(): string
    {
        return sprintf('Template_%s.csv', date('Ymd_His'));
    }
}
