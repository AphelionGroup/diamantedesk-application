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
namespace Diamante\EmailProcessingBundle\Infrastructure\Message;

use Diamante\EmailProcessingBundle\Infrastructure\Message\Zend\ImapMessageProvider;
use Diamante\EmailProcessingBundle\Model\Message\MessageProvider;
use Diamante\EmailProcessingBundle\Model\Message\MessageProviderFactory;

class ZendImapMessageProviderFactory implements MessageProviderFactory
{
    /**
     * @var ImapMessageProvider
     */
    public $imapMessageProvider;

    public function __construct(ImapMessageProvider $imapMessageProvider)
    {
        $this->imapMessageProvider = $imapMessageProvider;
    }

    /**
     * Create message provider
     * @param array $params
     * @return MessageProvider
     */
    public function create(array $params)
    {
        if (isset($params['ssl']) && true === $params['ssl']) {
            $params['ssl'] = 'SSL';
        } elseif (isset($params['ssl']) && false === $params['ssl']) {
            unset($params['ssl']);
        }

        $this->imapMessageProvider->setImapStorage(new \Zend\Mail\Storage\Imap($params));

        return $this->imapMessageProvider;
    }
}
