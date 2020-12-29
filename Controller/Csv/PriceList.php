<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Csv;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Oporteo\Csvorderupload\Helper\Data as CsvOrderUploadHelper;
use Psr\Log\LoggerInterface;

/**
 * Class PriceList
 */
class PriceList extends AbstractCsv
{
    /**
     * @array
     */
    private const CSV_HEAD = [
        [
            'Sku',
            'Name',
            'Price',
            'Qty'
        ]
    ];

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var CsvOrderUploadHelper
     */
    private $csvOrderUploadHelper;

    /**
     * PriceList constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param PriceHelper $priceHelper
     * @param CsvOrderUploadHelper $csvOrderUploadHelper
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        Csv $csvWriter,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        PriceHelper $priceHelper,
        CsvOrderUploadHelper $csvOrderUploadHelper
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->priceHelper = $priceHelper;
        $this->csvOrderUploadHelper = $csvOrderUploadHelper;

        parent::__construct($context, $resultRawFactory, $fileFactory, $csvWriter, $directoryList, $logger);
    }

    /**
     * @inheritDoc
     */
    protected function getContent(): array
    {
        $productCollection = $this->csvOrderUploadHelper->getProductCollection();

        $data =  array_map(
            function ($product) {
                return [
                    $product->getSku(),
                    $product->getName(),
                    $this->priceHelper->currency($product->getPrice(), true, false),
                    '',
                ];
            },
            $productCollection->getItems()
        );

        return array_merge(self::CSV_HEAD, $data);
    }

    /**
     * @inheritDoc
     */
    protected function getOutputFile(): string
    {
        return sprintf("Price_List_%s.csv", date('Ymd_His'));
    }
}
