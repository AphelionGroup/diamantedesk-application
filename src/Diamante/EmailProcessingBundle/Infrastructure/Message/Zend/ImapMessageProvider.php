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
namespace Diamante\EmailProcessingBundle\Infrastructure\Message\Zend;

use Diamante\EmailProcessingBundle\Model\Message;
use Diamante\EmailProcessingBundle\Model\Message\MessageProvider;
use Diamante\EmailProcessingBundle\Model\MessageProcessingException;
use Diamante\EmailProcessingBundle\Model\Service\EmailFilterService;
use Diamante\EmailProcessingBundle\Infrastructure\Message\Attachment;

class ImapMessageProvider extends AbstractMessageProvider implements MessageProvider
{
    const BATCH_SIZE_OF_MESSAGES_IN_BYTES = 20000000;
    const NAME_OF_FOLDER_OF_PROCESSED_MESSAGES = 'Processed';
    const DEFAULT_USED_ENCODING = 'UTF-8';

    /**
     * @var \Zend\Mail\Storage\Imap
     */
    private $zendImapStorage;

    private $batchSizeInBytes;

    /**
     * @var \Zend\Mail\Storage\Folder
     */
    private $folderOfProcessedMessages;

    public function __construct(
        \Zend\Mail\Storage\Imap $zendImapStorage,
        $batchSizeInBytes = self::BATCH_SIZE_OF_MESSAGES_IN_BYTES
    ) {
        $this->zendImapStorage  = $zendImapStorage;
        $this->batchSizeInBytes = $batchSizeInBytes;
    }

    /**
     * Fetch messages that should be processed
     * @return array|Message[]
     * @throws MessageProcessingException
     */
    public function fetchMessagesToProcess()
    {
        $messages = array();
        try {
            foreach ($this->computeMessageIdsToProcess() as $uniqueMessageId) {
                /** @var \Zend\Mail\Storage\Message $message */
                $imapMessage = $this->zendImapStorage->getMessage(
                    $this->zendImapStorage->getNumberByUniqueId($uniqueMessageId)
                );
                $headers            = $imapMessage->getHeaders();

                $messageId          = $this->processMessageId($headers);
                $messageSubject     = $this->processSubject($headers);
                $messageContent     = $this->processContent($imapMessage);
                $messageFrom        = $this->processFrom($headers);
                $messageTo          = $this->processTo($headers);
                $messageReference   = $this->processMessageReference($headers);
                $messageAttachments = $this->processAttachments($imapMessage);
                $isFailed           = $this->isFailed($headers);
                $recipients         = $this->processRecipients($headers);

                $messages[] = new Message($uniqueMessageId, $messageId, $messageSubject, $messageContent,
                    $messageFrom, $messageTo, $messageReference, $messageAttachments, $isFailed, $recipients);
            }
        } catch (\Exception $e) {
            throw new MessageProcessingException($e->getMessage());
        }
        return $messages;
    }

    /**
     * @param \Zend\Mail\Headers $headers
     *
     * @return bool
     */
    private function isFailed($headers)
    {
        return $headers->get('xfailedrecipients') ? true : false;
    }

    /**
     * Retrieves Message Subject
     *
     * @param \Zend\Mail\Headers $headers
     * @return string|null
     */
    private function processSubject($headers)
    {
        $messageSubject = $headers->get('subject') ? $headers->get('subject')->getFieldValue() : null;
        return $messageSubject;
    }

    /**
     * Retrieves Message Content
     *
     * @param \Zend\Mail\Storage\Message $imapMessage
     * @return string
     */
    private function processContent($imapMessage)
    {
        $messageContent = null;
        if ($imapMessage->isMultipart()) {
            foreach (new \RecursiveIteratorIterator($imapMessage) as $part) {
                $headers = $part->getHeaders();
                if ($headers->get('contenttype')) {
                    $type = $headers->get('contenttype')->getType();
                    if ($type == \Zend\Mime\Mime::TYPE_HTML) {
                        $messageContent = $this->getMessageContentDecoded($part);
                        break;
                    } elseif ($type == \Zend\Mime\Mime::TYPE_TEXT) {
                        $messageContent = $this->getMessageContentDecoded($part);
                    }
                }
            }
        } else {
            $messageContent = $this->getMessageContentDecoded($imapMessage);
        }

        $emailFilter = new EmailFilterService($messageContent);
        $messageContent = $emailFilter->recognizeUsefulContent();

        return $messageContent;
    }

    /**
     * Retrieves Message Attachments
     *
     * @param \Zend\Mail\Storage\Message $imapMessage
     * @return array
     */
    private function processAttachments($imapMessage)
    {
        $attachments = array();
        if ($imapMessage->isMultipart()) {
            foreach (new \RecursiveIteratorIterator($imapMessage) as $part) {
                $headers = $part->getHeaders();
                if ($headers->get('contentdisposition')) {
                    $contentDisposition = $headers->get('contentdisposition');
                    $disposition = \Zend\Mime\Decode::splitHeaderField($contentDisposition->getFieldValue());

                    if ($disposition[0] == (\Zend\Mime\Mime::DISPOSITION_ATTACHMENT || \Zend\Mime\Mime::DISPOSITION_INLINE) && isset($disposition['filename'])) {
                        $fileName    = $disposition['filename'];
                        $fileContent = $part->getContent();
                        $attachments[] = new Attachment($fileName, base64_decode($fileContent));
                    }
                }
            }
        }

        return $attachments;
    }

    /**
     * Retrieve unique message ids that should be processed in current batch.
     * Computing is depending on size of messages
     * @return array
     */
    private function computeMessageIdsToProcess()
    {
        $totalSize = 0;
        $messageIds = array();
        foreach ($this->zendImapStorage->getSize() as $messageId => $size) {
            if (($totalSize + $size) > $this->batchSizeInBytes) {
                if (empty($messageIds)) {
                    $messageIds[] = $messageId;
                }
                break;
            }
            $messageIds[] = $this->zendImapStorage->getUniqueId($messageId);
            $totalSize += $size;
        }
        return $messageIds;
    }

    /**
     * Mark given messages as Processed
     * @param array|Message[] $messages
     * @return void
     */
    public function markMessagesAsProcessed(array $messages)
    {
        \Assert\that($messages)->all()->isInstanceOf('Diamante\EmailProcessingBundle\Model\Message');
        foreach ($messages as $message) {
            $this->zendImapStorage->moveMessage(
                $this->zendImapStorage->getNumberByUniqueId($message->getUniqueId()),
                $this->folderOfProcessedMessages()
            );
        }
    }

    /**
     * Delete processed messages
     * @param array $messages
     * @return void
     */
    public function deleteProcessedMessages(array $messages)
    {
        \Assert\that($messages)->all()->isInstanceOf('Diamante\EmailProcessingBundle\Model\Message');
        foreach ($messages as $message) {
            $this->zendImapStorage->removeMessage(
                $this->zendImapStorage->getNumberByUniqueId($message->getUniqueId())
            );
        }
    }

    /**
     * Initialize mailbox folder with name 'Processing'. If folder is not exists - it will be created
     * @return void
     */
    private function initialize()
    {
        if (is_null($this->folderOfProcessedMessages)) {
            $exists = false;
            $iterator = new \RecursiveIteratorIterator($this->zendImapStorage->getFolders());
            foreach ($iterator as $folder) {
                if ($folder->getLocalName() == self::NAME_OF_FOLDER_OF_PROCESSED_MESSAGES) {
                    $exists = true;
                    break;
                }
            }
            if (false === $exists) {
                $this->zendImapStorage
                    ->createFolder(
                        self::NAME_OF_FOLDER_OF_PROCESSED_MESSAGES,
                        new \Zend\Mail\Storage\Folder('INBOX')
                    );
            }
            $this->folderOfProcessedMessages = new \Zend\Mail\Storage\Folder(
                'INBOX/' . self::NAME_OF_FOLDER_OF_PROCESSED_MESSAGES
            );
        }
    }

    /**
     * @return \Zend\Mail\Storage\Folder
     */
    private function folderOfProcessedMessages()
    {
        if (is_null($this->folderOfProcessedMessages)) {
            $this->initialize();
        }
        return $this->folderOfProcessedMessages;
    }

    /**
     * @param \Zend\Mail\Storage\Part $part
     * @return string
     */
    private function getMessageContentDecoded($part)
    {
        $headers = $part->getHeaders();
        $content = $part->getContent();
        $decodedContent = $content;

        if ($headers->has('Content-Transfer-Encoding')) {
            $contentEncoding = $headers->get('contenttransferencoding')->getFieldValue();
            switch ($contentEncoding) {
                case 'base64':
                    $decodedContent = base64_decode($content);
                    break;
                case 'quoted-printable':
                    $decodedContent = quoted_printable_decode($content);
                    break;
                default:
                    $decodedContent = $content;
            }
        }

        if ($headers->has('Content-Type')) {
            /** @var \Zend\Mail\Header\ContentType $contentType */
            $contentType = $headers->get('contenttype');
            if ($charset = $contentType->getParameter('charset')) {
                if ($decodedCharsetContent = iconv($charset, static::DEFAULT_USED_ENCODING, $decodedContent)) {
                    $decodedContent = $decodedCharsetContent;
                }
            }
        }

        return $decodedContent;
    }
}
