<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Api;

interface FileReaderInterface
{
    public const string COL_SKU = 'sku';
    public const string COL_QTY = 'qty';

    public function read(string $filePath): array;
}
