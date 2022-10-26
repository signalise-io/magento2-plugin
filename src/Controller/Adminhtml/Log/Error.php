<?php
/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */
declare(strict_types=1);

namespace Signalise\Plugin\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class Error extends Action
{
    public const ERROR_LOG_FILE = '%s/log/signalise.log';

    private JsonFactory $resultJsonFactory;

    private DirectoryList $dir;

    private File $file;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        DirectoryList $dir,
        File $file
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->dir               = $dir;
        $this->file              = $file;
    }

    /**
     * @throws FileSystemException
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        if ($this->isLogExists()) {
            $result = ['result' => $this->prepareLogText()];
        } else {
            $result = __('Log is empty');
        }
        return $resultJson->setData($result);
    }

    private function isLogExists(): bool
    {
        try {
            $logFile = sprintf(self::ERROR_LOG_FILE, $this->dir->getPath('var'));
            return $this->file->isExists($logFile);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @throws FileSystemException
     */
    private function prepareLogText(): array
    {
        $logFile = sprintf(self::ERROR_LOG_FILE, $this->dir->getPath('var'));
        $fileContent = explode(PHP_EOL, $this->file->fileGetContents($logFile));
        if (count($fileContent) > 100) {
            $fileContent = array_slice($fileContent, -100, 100, true);
        }
        $result = [];
        foreach ($fileContent as $line) {
            $data = explode('] ', $line);
            $date = ltrim(array_shift($data), '[');
            $data = implode('] ', $data);
            $data = explode(': ', $data);
            array_shift($data);
            $result[] = [
                'date' => $date,
                'msg' => implode(': ', $data)
            ];
        }
        return $result;
    }
}
