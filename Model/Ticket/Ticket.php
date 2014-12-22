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
namespace Diamante\DeskBundle\Model\Ticket;

use Diamante\DeskBundle\Model\Attachment\Attachment;
use Diamante\DeskBundle\Model\Attachment\AttachmentHolder;
use Diamante\DeskBundle\Model\Branch\Branch;
use Diamante\DeskBundle\Model\Shared\DomainEventProvider;
use Diamante\DeskBundle\Model\Shared\Entity;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\AttachmentWasAddedToTicket;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\AttachmentWasDeletedFromTicket;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\CommentWasAddedToTicket;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketAssigneeWasChanged;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketStatusWasChanged;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketWasCreated;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketWasDeleted;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketWasUnassigned;
use Diamante\DeskBundle\Model\Ticket\Notifications\Events\TicketWasUpdated;
use Doctrine\Common\Collections\ArrayCollection;
use Diamante\DeskBundle\Model\User\User;
use Oro\Bundle\UserBundle\Entity\User as OroUser;

class Ticket extends DomainEventProvider implements Entity, AttachmentHolder
{
    const UNASSIGNED_LABEL = 'Unassigned';

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var UniqueId
     */
    protected $uniqueId;

    /**
     * @var TicketSequenceNumber
     */
    protected $sequenceNumber;

    /**
     * @var TicketKey
     */
    protected $key;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Source
     */
    protected $source;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var Priority
     */
    protected $priority;

    /**
     * @var Branch
     */
    protected $branch;

    /**
     * @var User
     */
    protected $reporter;

    /**
     * @var \Oro\Bundle\UserBundle\Entity\User
     */
    protected $assignee;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $comments;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    protected $attachments;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @param UniqueId $uniqueId
     * @param TicketSequenceNumber $sequenceNumber
     * @param $subject
     * @param $description
     * @param Branch $branch
     * @param User $reporter
     * @param OroUser $assignee
     * @param Source $source
     * @param Priority $priority
     * @param Status $status
     */
    public function __construct(
        UniqueId $uniqueId,
        TicketSequenceNumber $sequenceNumber,
        $subject, $description,
        Branch $branch,
        User $reporter,
        OroUser $assignee = null,
        Source $source,
        Priority $priority,
        Status $status
    ) {
        $this->uniqueId = $uniqueId;
        $this->sequenceNumber = $sequenceNumber;
        $this->subject = $subject;
        $this->description = $description;
        $this->branch = $branch;
        $this->status = $status;
        $this->priority = $priority;
        $this->reporter = $reporter;
        $this->assignee = $assignee;
        $this->comments  = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
        $this->source = $source;

        $this->raise(
            new TicketWasCreated(
                (string) $this->uniqueId,
                $this->branch->getName(),
                $this->subject,
                $this->description,
                $this->getReporterFullName(),
                $this->getAssigneeFullName(),
                (string) $this->priority,
                (string) $this->status,
                (string) $this->source
            )
        );
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UniqueId
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return TicketSequenceNumber
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @return TicketKey
     */
    public function getKey()
    {
        $this->initializeKey();
        return $this->key;
    }

    /**
     * Initialize TicketKey
     * @return void
     */
    private function initializeKey()
    {
        if ($this->sequenceNumber->getValue() && is_null($this->key)) {
            $this->key = new TicketKey($this->branch->getKey(), $this->sequenceNumber->getValue());
        }
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return Branch
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @return string
     */
    public function getBranchName()
    {
        return $this->branch->getName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return mixed
     */
    public function getReporter()
    {
        return $this->reporter;
    }

    /**
     * @return string
     */
    public function getReporterId()
    {
        return $this->reporter->getId();
    }

    /**
     * @return \Oro\Bundle\UserBundle\Entity\User
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return null|string
     */
    public function getAssigneeFullName()
    {
        if (!empty($this->assignee)) {
            return $this->assignee->getFirstName() . ' ' . $this->assignee->getLastName();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getReporterFullName()
    {
        return 'Reporter';
    }

    public function postNewComment(Comment $comment)
    {
        $this->comments->add($comment);
        $this->raise(
            new CommentWasAddedToTicket(
                $this->uniqueId, $this->subject, $comment->getContent()
            )
        );
    }

    /** LEGACY CODE START */

    /**
     * @param string $subject
     * @param string $description
     * @param User $reporter
     * @param Priority $priority
     * @param Status $status
     * @param Source $source
     * @param OroUser|null $assignee
     */
    public function update(
        $subject, $description, User $reporter, Priority $priority,
        Status $status, Source $source, OroUser $assignee = null
    ) {
        $hasChanges = false;
        if ($this->subject !== $subject || $this->description !== $description || $this->reporter !== $reporter
            || $this->assignee != $assignee || $this->priority->getValue() !== $priority->getValue()
            || $this->status->notEquals($status) || $this->source->getValue() !== $source->getValue()
        ) {
            $hasChanges = true;
        }

        $this->subject     = $subject;
        $this->description = $description;
        $this->reporter    = $reporter;
        $this->priority    = $priority;
        $this->source      = $source;
        $this->updatedAt   = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->processUpdateStatus($status);

        if (is_null($assignee)) {
            $this->processUnAssign();
        } else {
            $this->processAssign($assignee);
        }

        if ($hasChanges) {
            $this->raise(
                new TicketWasUpdated(
                    (string) $this->uniqueId, $this->subject, $this->description, $this->getReporterFullName(),
                    (string) $this->priority, (string) $this->status, (string) $this->source
                )
            );
        }
    }

    /**
     * Update ticket status
     * @param Status $status
     * @return void
     */
    public function updateStatus(Status $status)
    {
        if ($this->status->notEquals($status)) {
            $this->processUpdateStatus($status);
            $this->raise(
                new TicketStatusWasChanged(
                    (string) $this->uniqueId, $this->subject, (string) $this->status
                )
            );
        }
    }

    /**
     * @param Status $status
     */
    private function processUpdateStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Assign new assignee (User) to ticket
     * @param OroUser $newAssignee
     * @return void
     */
    public function assign(OroUser $newAssignee)
    {
        if (is_null($this->assignee) || $newAssignee->getId() != $this->assignee->getId()) {
            $this->processAssign($newAssignee);
            $this->raise(new TicketAssigneeWasChanged($this->uniqueId, $this->subject, $this->getAssigneeFullName()));
        }
    }

    /**
     * @param OroUser $newAssignee
     * @retur void
     */
    private function processAssign(OroUser $newAssignee)
    {
        $this->assignee = $newAssignee;
    }

    /**
     * Un assign ticket
     * @return void
     */
    public function unAssign()
    {
        $this->processUnAssign();
        $this->raise(new TicketWasUnassigned($this->uniqueId, $this->subject));
    }

    /**
     * @return void
     */
    private function processUnAssign()
    {
        $this->assignee = null;
    }

    /**
     * Retrieves ticket comments
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Attachment $attachment
     */
    public function addAttachment(Attachment $attachment)
    {
        $this->attachments->add($attachment);
        $event = null;
        foreach ($this->recorderEvents as $each) {
            if ($each instanceof TicketWasCreated || $each instanceof TicketWasUpdated) {
                $event = $each;
            }
        }
        if (is_null($event)) {
            $this->raise(new AttachmentWasAddedToTicket($this->uniqueId, $this->subject, $attachment->getFilename()));
        } elseif ($event) {
            $event->pushAttachment($attachment->getFilename());
        }
    }

    /**
     * @param Attachment $attachment
     */
    public function removeAttachment(Attachment $attachment)
    {
        $this->attachments->remove($attachment->getId());
        $this->raise(new AttachmentWasDeletedFromTicket($this->uniqueId, $this->subject, $attachment->getFilename()));
    }

    /**
     * Returns unmodifiable collection
     * @return ArrayCollection
     */
    public function getAttachments()
    {
        return new ArrayCollection($this->attachments->toArray());
    }

    /**
     * Retrieves Attachment
     * @param $attachmentId
     * @return Attachment
     */
    public function getAttachment($attachmentId)
    {
        $attachment = $this->attachments->filter(function($elm) use ($attachmentId) {
            /**
             * @var $elm Attachment
             */
            return $elm->getId() == $attachmentId;
        })->first();
        return $attachment;
    }

    /**
     * @return string
     */
    public function getUnassignedLabel()
    {
        return self::UNASSIGNED_LABEL;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    public function delete()
    {
        $this->raise(
            new TicketWasDeleted($this->uniqueId, $this->subject)
        );
    }
}
