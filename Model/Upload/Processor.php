<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Model\Upload;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Oporteo\OrderUpload\Model\ValidationStorage;
use RuntimeException;

class Processor
{
    public function __construct(
        private readonly ValidationStorage $validationStorage,
        private readonly ValidatedRowsProcessor $validatedRowsProcessor,
        private readonly CartRepositoryInterface $quoteRepository,
    ) {
    }

    public function process(string $token, CartInterface $quote): array
    {
        $rows = (array) ($this->validationStorage->consume($token)['valid_rows'] ?? []);
        $results = $this->validatedRowsProcessor->process(
            $rows,
            (int) $quote->getStoreId(),
            static function ($product, $buyRequest) use ($quote): void {
                $result = $quote->addProduct($product, $buyRequest);

                if (is_string($result)) {
                    throw new RuntimeException($result);
                }
            },
            'Validated order upload row failed during cart processing.'
        );

        if ($results['successes'] !== []) {
            $quote->collectTotals();
            $this->quoteRepository->save(quote: $quote);
        }

        return $results;
    }
}
