<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Diamante\DeskBundle\Model\Attachment;

use Diamante\DeskBundle\Model\Shared\Repository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Imagine\Image\Box;
use Liip\ImagineBundle\Binary\Loader\FileSystemLoader;
use Symfony\Bridge\Monolog\Logger;

class ManagerImpl implements Manager
{
    const DEFAULT_THUMB_EXT = 'png';

    /**
     * @var Services\FileStorageService
     */
    private $fileStorageService;

    /**
     * @var AttachmentFactory
     */
    private $factory;

    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var \Diamante\DeskBundle\Infrastructure\Attachment\Imagine\Data\Loader\FileSystemAttachmentLoader
     */
    private $loader;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $doctrineRegistry
     * @param \Diamante\DeskBundle\Model\Attachment\Services\FileStorageService $fileStorageService
     * @param \Diamante\DeskBundle\Model\Attachment\AttachmentFactory $factory
     * @param \Diamante\DeskBundle\Model\Shared\Repository $repository
     * @param \Liip\ImagineBundle\Binary\Loader\FileSystemLoader $loader
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function __construct(
        Registry $doctrineRegistry,
        Services\FileStorageService $fileStorageService,
        AttachmentFactory $factory,
        Repository $repository,
        FileSystemLoader $loader,
        Logger $logger
    ) {
        $this->fileStorageService = $fileStorageService;
        $this->factory = $factory;
        $this->repository = $repository;
        $this->loader = $loader;
        $this->logger = $logger;

        $this->registry = $doctrineRegistry;
    }

    /**
     * Create new attachment and assign it to the attachment holder
     * @param string $filename
     * @param string $content
     * @param AttachmentHolder $holder
     * @param bool $flush
     * @return Attachment
     */
    public function createNewAttachment($filename, $content, AttachmentHolder $holder, $flush = false)
    {
        $this->validateFilename($filename);
        $this->validateContent($content);
        $filename = urldecode($filename);

        $filenamePrefix = $this->exposeFilenamePrefixFrom($holder);

        $path = $this->fileStorageService->upload($filenamePrefix . '/' . $filename, $content);

        $file = new File($path);

        $hash = $this->generateFileHash($file);

        if ($this->isImage($file)) {
            $this->createThumbnail($file, $hash, $filenamePrefix);
        }

        $attachment = $this->factory->create($file, $hash);

        $holder->addAttachment($attachment);
        $this->registry->getManager()->persist($attachment);

        if (true === $flush) {
            $this->registry->getManager()->flush();
        }

        return $attachment;
    }

    /**
     * Delete attachment
     * @param Attachment $attachment
     * @param boolean $flush
     * @return void
     */
    public function deleteAttachment(Attachment $attachment, $flush = false)
    {
        $this->fileStorageService->remove($attachment->getFilename());
        $this->registry->getManager()->remove($attachment);

        if (true === $flush) {
            $this->registry->getManager()->flush();
        }
    }

    /**
     * @param string $filename
     */
    private function validateFilename($filename)
    {
        if (false === is_string($filename)) {
            throw new \LogicException('Given filename is invalid.');
        }
    }

    /**
     * @param string $content
     */
    private function validateContent($content)
    {
        if (false === is_string($content)) {
            throw new \LogicException('Given file content is invalid.');
        }
    }

    /**
     * @param AttachmentHolder $attachmentHolder
     * @return string
     */
    private function exposeFilenamePrefixFrom(AttachmentHolder $attachmentHolder)
    {
        $parts = explode("\\", get_class($attachmentHolder));
        $prefix = strtolower(array_pop($parts));
        return $prefix;
    }

    /**
     * @param \Diamante\DeskBundle\Model\Attachment\File $file
     * @return string
     */
    private function generateFileHash(File $file)
    {
        return md5($file->getFilename() . time());
    }

    /**
     * @param \Diamante\DeskBundle\Model\Attachment\File $file
     * @return bool
     */
    protected function isImage(File $file)
    {
        $ext = strtolower($file->getExtension());

        return in_array($ext,['jpg','jpeg','png','gif','bmp']);
    }

    /**
     * @param \Diamante\DeskBundle\Model\Attachment\File $file
     * @param                                            $hash
     * @param                                            $fileNamePrefix
     * @return \Imagine\Image\ManipulatorInterface
     */
    public function createThumbnail(File $file, $hash, $fileNamePrefix)
    {
        $image = $this->loader->getImagine()->open($file->getPathname());
        $thumbnail = $image->thumbnail(new Box(100,100));

        $destinationFolder = sprintf('%s/thumbnails', $this->getDestination($fileNamePrefix));

        try {
            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder);
                chmod($destinationFolder, 0777);
            }
            $destination = sprintf("%s/%s.%s", $destinationFolder, $hash, self::DEFAULT_THUMB_EXT);
            $thumbnail->save($destination);
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Attachment directory is not accessible! Reason: %s', $e->getMessage()));
            throw new \RuntimeException('Thumbnail could not be created. ' . $e->getMessage());
        }
    }

    /**
     * @param    string $prefix
     * @return   string
     */
    protected function getDestination($prefix)
    {
        $folder = $this->fileStorageService->getConfiguredUploadDir();

        return $folder . '/' . $prefix;
    }
}
