<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Oporteo\OrderUpload\Model\Columns\Collection;

class TemplateColumnsProvider
{
    public function __construct(
        private readonly EventManagerInterface $eventManager
    ) {
    }

    /**
     * @return string[]
     */
    public function getColumns(?int $storeId = null): array
    {
        $columns = new Collection(['sku', 'qty']);
        $this->eventManager->dispatch(
            'oporteo_orderupload_collect_template_columns',
            [
                'columns' => $columns,
                'store_id' => $storeId,
            ]
        );

        return $columns->toArray();
    }
}
