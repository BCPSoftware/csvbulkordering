<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use Magento\Framework\DataObject;
use Magento\Sales\Model\AdminOrder\Create;
use Oporteo\OrderUpload\Model\ValidationStorage;

class AdminOrderProcessor
{
    public function __construct(
        private readonly ValidationStorage $validationStorage,
        private readonly ValidatedRowsProcessor $validatedRowsProcessor
    ) {
    }

    public function process(string $token, Create $adminOrderCreate): array
    {
        $rows = (array) ($this->validationStorage->consume($token)['valid_rows'] ?? []);
        $results = $this->validatedRowsProcessor->process(
            $rows,
            (int) $adminOrderCreate->getQuote()->getStoreId(),
            static function ($product, DataObject $buyRequest) use ($adminOrderCreate): void {
                $adminOrderCreate->addProduct($product, $buyRequest->getData());
            },
            'Validated admin order upload row failed during order processing.'
        );

        if ($results['successes'] !== []) {
            $adminOrderCreate->saveQuote();
        }

        return $results;
    }
}
