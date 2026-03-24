<?php

declare(strict_types=1);

namespace Oporteo\OrderUpload\Controller\Template;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Store\Model\StoreManagerInterface;
use Oporteo\OrderUpload\Model\TemplateColumnsProvider;

class Download implements HttpGetActionInterface
{
    public function __construct(
        private readonly FileFactory $fileFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly TemplateColumnsProvider $templateColumnsProvider
    ) {
    }

    public function execute(): ResponseInterface
    {
        $content = implode(',', $this->templateColumnsProvider->getColumns(
            (int) $this->storeManager->getStore()->getId()
        )) . PHP_EOL;

        return $this->fileFactory->create(
            fileName: 'template.csv',
            content: $content,
            baseDir: DirectoryList::VAR_DIR,
            contentType: 'text/csv'
        );
    }
}
