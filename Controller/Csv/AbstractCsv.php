<?php

namespace Oporteo\Csvorderupload\Controller\Csv;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractCsv
 */
abstract class AbstractCsv extends Action
{
    /**
     * @var string
     */
    private const CONTENT_TYPE = 'application/vnd.ms-excel';

    /**
     * @var string
     */
    private const TYPE = 'filename';

    /**
     * @var string
     */
    private const OUTPUT_DIRECTORY = DirectoryList::MEDIA;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Csv
     */
    private $csvWriter;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AbstractCsv constructor.
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param Csv $csvWriter
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Csv $csvWriter,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->csvWriter = $csvWriter;
        $this->directoryList = $directoryList;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        try {
            $outputFile = $this->getOutputFile();
            $filePath = $this->getFilePath($outputFile);

            $this->write($filePath, $this->getContent());

            return $this->createFile($outputFile);
        } catch (Exception $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(__('An error has occurred'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setRefererUrl();
    }

    /**
     * Get content
     *
     * @return array
     */
    abstract protected function getContent(): array;

    /**
     * Get output file
     *
     * @return string
     */
    abstract protected function getOutputFile(): string;

    /**
     * Get file path
     *
     * @param string $outputFile
     *
     * @return string
     *
     * @throws FileSystemException
     */
    protected function getFilePath(string $outputFile): string
    {
        return $this->directoryList->getPath(self::OUTPUT_DIRECTORY) . '/' . $outputFile;
    }

    /**
     * Create file
     *
     * @param string $outputFile
     *
     * @return ResponseInterface
     *
     * @throws Exception
     */
    protected function createFile(string $outputFile): ResponseInterface
    {
        return $this->fileFactory->create(
            $outputFile,
            [
                'type' => self::TYPE,
                'value' => $outputFile,
                'rm' => true,
            ],
            self::OUTPUT_DIRECTORY,
            self::CONTENT_TYPE
        );
    }

    /**
     * Write data to csv file
     *
     * @param string $filePath
     * @param array $csvData
     *
     * @return void
     *
     * @throws FileSystemException
     */
    protected function write(string $filePath, array $csvData): void
    {
        $this->csvWriter
            ->setEnclosure('"')
            ->setDelimiter(',')
            ->appendData($filePath, $csvData);
    }
}
