<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Columns;

use Magento\Framework\Exception\LocalizedException;

class Collection
{
    private array $columns = [];

    /**
     * @throws LocalizedException
     */
    public function __construct(array $columns = [])
    {
        foreach ($columns as $column) {
            $this->addColumn((string)$column);
        }
    }

    /**
     * @param string $column
     * @return void
     * @throws LocalizedException
     */
    public function addColumn(string $column): void
    {
        $column = trim($column);

        if ($column === '') {
            throw new LocalizedException(__('Column name must not be empty.'));
        }

        $key = strtolower($column);

        if (isset($this->columns[$key])) {
            throw new LocalizedException(__('Column "%1" is already registered.', $column));
        }

        $this->columns[$key] = $column;
    }

    /**
     * @param string $column
     * @return bool
     * @throws LocalizedException
     */
    public function hasColumn(string $column): bool
    {
        $column = trim($column);

        if ($column === '') {
            throw new LocalizedException(__('Column name must not be empty.'));
        }

        $key = strtolower($column);

        return isset($this->columns[$key]);
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return array_values($this->columns);
    }
}
