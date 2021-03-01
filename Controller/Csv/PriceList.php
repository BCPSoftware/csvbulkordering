<?php

declare(strict_types=1);

namespace Oporteo\Csvorderupload\Controller\Csv;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Oporteo\Csvorderupload\Api\GetStockProductQtysInterface;
use Oporteo\Csvorderupload\Helper\Data as CsvOrderUploadHelper;
use Psr\Log\LoggerInterface;

/**
 * Class PriceList
 */
class PriceList extends AbstractCsv
{
    /**
     * @var array
     */
    private const CSV_HEAD = [
        [
            'Sku',
            'Name',
            'Price',
            'Qty',
        ],
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
     * @var GetStockProductQtysInterface
     */
    private $stockProductQtys;

    /**
     * PriceList constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param CollectionFactory $productCollectionFactory
     * @param PriceHelper $priceHelper
     * @param CsvOrderUploadHelper $csvOrderUploadHelper
     * @param GetStockProductQtysInterface $stockProductQtys
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Csv $csvWriter,
        DirectoryList $directoryList,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        PriceHelper $priceHelper,
        CsvOrderUploadHelper $csvOrderUploadHelper,
        GetStockProductQtysInterface $stockProductQtys
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->priceHelper = $priceHelper;
        $this->csvOrderUploadHelper = $csvOrderUploadHelper;
        $this->stockProductQtys = $stockProductQtys;

        parent::__construct($context, $fileFactory, $csvWriter, $directoryList, $logger);
    }

    /**
     * @inheritDoc
     */
    protected function getContent(): array
    {
        $productCollection = $this->csvOrderUploadHelper->getProductCollection();
        $stockQtys = $this->stockProductQtys->execute($productCollection->getColumnValues('sku'));

        $data =  array_map(
            function ($product) use ($stockQtys) {
                return [
                    $product->getSku(),
                    $product->getName(),
                    $this->priceHelper->currency($product->getPrice(), true, false),
                    array_key_exists($product->getSku(), $stockQtys) ? $stockQtys[$product->getSku()] : '0',
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
        return sprintf('Price_List_%s.csv', date('Ymd_His'));
    }
}
