<?php
/*
 * Copyright (c) 2015 Eltrino LLC (http://eltrino.com)
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

namespace Diamante\DeskBundle\Infrastructure\Notification;

use Diamante\DeskBundle\Entity\MessageReference;
use Diamante\DeskBundle\Entity\Ticket;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Translation\TranslatorInterface;
use Diamante\DeskBundle\Model\Ticket\EmailProcessing\MessageReferenceRepository;

class NotificationManager
{
    const TEMPLATE_TYPE_HTML            = 'html';
    const TEMPLATE_TYPE_TXT             = 'txt';

    const SENDER_EMAIL_CONFIG_PATH      = 'oro_notification.email_notification_sender_email';
    const SENDER_NAME_CONFIG_PATH       = 'oro_notification.email_notification_sender_name';

    const SYSTEM_MESSAGE_HEADER         = 'diamante-system-message';
    const CREATE_TICKET_HEADER          = 'diamante-create-ticket';

    /**
     * @var array
     */
    protected $templates = [];

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var Ticket
     */
    protected $ticket;

    /**
     * @var string
     */
    protected $fromEmail;

    /**
     * @var string
     */
    protected $fromName;

    /**
     * @var string
     */
    protected $toEmail;
    /**
     * @var string
     */
    protected $toName;

    /**
     * @var array
     */
    public $templateOptions = [];

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var NotificationOptionsProvider[]
     */
    protected $providers = [];

    /**
     * @var ConfigManager
     */
    protected $config;

    /**
     * @var MessageReferenceRepository
     */
    protected $messageReferenceRepository;

    /**
     * NotificationManager constructor.
     *
     * @param \Twig_Environment          $twig
     * @param \Swift_Mailer              $mailer
     * @param TranslatorInterface        $translator
     * @param ConfigManager              $configManager
     * @param MessageReferenceRepository $messageReferenceRepository
     */
    public function __construct(
        \Twig_Environment $twig,
        \Swift_Mailer $mailer,
        TranslatorInterface $translator,
        ConfigManager $configManager,
        MessageReferenceRepository $messageReferenceRepository
    ) {
        $this->twig                       = $twig;
        $this->mailer                     = $mailer;
        $this->translator                 = $translator;
        $this->config                     = $configManager;
        $this->messageReferenceRepository = $messageReferenceRepository;
    }

    /**
     * Clear instance
     */
    public function clear()
    {
        $this->subject = '';
        $this->toEmail = '';
        $this->toName = '';
        $this->fromEmail = '';
        $this->fromName = '';
        $this->templates = [];
        $this->templateOptions = [];
    }

    /**
     * @param string $path
     */
    public function addHtmlTemplate($path)
    {
        $this->templates[self::TEMPLATE_TYPE_HTML] = $path;
    }

    /**
     * @param string $path
     */
    public function addTxtTemplate($path)
    {
        $this->templates[self::TEMPLATE_TYPE_TXT] = $path;
    }

    /**
     * @param string $subject
     * @param bool $translatable
     */
    public function setSubject($subject, $translatable = false)
    {
        if ($translatable) {
            $subject = $this->translator->trans($subject);
        }

        $this->subject = $subject;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function addTemplateOption($key, $value)
    {
        $this->templateOptions[$key] = $value;
    }

    /**
     * @param array $options
     */
    public function setTemplateOptions(array $options)
    {
        $this->templateOptions = $options;
    }

    public function notify()
    {
        $message = \Swift_Message::newInstance();
        $message->setSubject($this->subject);
        $message->setFrom($this->fromEmail, $this->fromName);
        $message->setTo($this->toEmail, $this->toName);
        $message->setBody($this->twig->render(
            $this->templates[self::TEMPLATE_TYPE_HTML],
            $this->templateOptions
        ), 'text/html');

        $headers = $message->getHeaders();
        $headers->addTextHeader(static::SYSTEM_MESSAGE_HEADER, true);
        $headers->addIdHeader('References', $this->referencesHeader());

        if (isset($this->templates[self::TEMPLATE_TYPE_TXT])) {
            $message->addPart($this->twig->render(
                $this->templates[self::TEMPLATE_TYPE_TXT],
                $this->templateOptions
            ), 'text/plain');

        }

        $this->mailer->send($message);

        if (!is_null($this->ticket)) {
            $reference = new MessageReference($message->getId(), $this->ticket);
            $this->messageReferenceRepository->store($reference);
        }
    }

    /**
     * @param Ticket $ticket
     */
    public function setTicket(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * @return array
     */
    private function referencesHeader()
    {
        $ids = [];

        if (is_null($this->ticket)) {
            return $ids;
        }

        foreach ($this->messageReferenceRepository->findAllByTicket($this->ticket) as $reference) {
            $ids[] = $reference->getMessageId();
        }
        return $ids;
    }

    /**
     * @param string $email
     * @param null $name
     */
    public function setFrom($email, $name = null)
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    /**
     * @param string $email
     * @param null $name
     */
    public function setTo($email, $name = null)
    {
        $this->toEmail = $email;
        $this->toName = $name;
    }

    /**
     * @param NotificationOptionsProvider $provider
     */
    public function addOptionsProvider(NotificationOptionsProvider $provider)
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @param $name
     * @param $recipient
     * @param array $options
     */
    public function notifyByScenario($name, $recipient, array $options = [])
    {
        if (!array_key_exists($name, $this->providers)) {
            throw new \RuntimeException(sprintf('Option provider with name "%s" is not found or was not properly configured', $name));
        }

        $provider = $this->providers[$name];
        $provider->setRecipient($recipient);

        $templateOptions = array_merge($provider->getDefaultOptions(), $options, $this->getUrlOptions());

        foreach ($provider->getDefaultOptions() as $option => $value) {
            if (!array_key_exists($option, $templateOptions)) {
                throw new \RuntimeException(sprintf("Required parameter %s is missing.", $option));
            }
        }

        $this->setTemplateOptions($templateOptions);

        $this->setTo($provider->getRecipientEmail(), $provider->getRecipientName());
        $this->addHtmlTemplate($provider->getHtmlTemplate());
        $this->addTxtTemplate($provider->getTxtTemplate());
        $this->setSubject($provider->getSubject(), $provider->subjectIsTranslatable());

        $this->setFrom(
            $this->config->get(self::SENDER_EMAIL_CONFIG_PATH),
            $this->config->get(self::SENDER_NAME_CONFIG_PATH)
        );

        $this->notify();
        $this->clear();
    }

    /**
     * @return array
     */
    protected function getUrlOptions()
    {
        $applicationUrl = $this->config->get('oro_ui.application_url');

        if (empty($applicationUrl)) {
            return [];
        }

        $urlParts = parse_url($applicationUrl);

        if (!$urlParts || !isset($urlParts['scheme']) || !isset($urlParts['host'])) {
            return [];
        }

        return ['host' => $urlParts['host'], 'scheme' => $urlParts['scheme']];
    }
}
