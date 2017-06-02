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
namespace Diamante\DeskBundle\Api\Command;

use Diamante\DeskBundle\Entity\Branch;
use Symfony\Component\Validator\Constraints as Assert;
use Diamante\DeskBundle\Validator\Constraints\Any;
use Diamante\DeskBundle\Validator\Constraints\User;
use Diamante\DeskBundle\Validator\Constraints\PrintType;
use Doctrine\Common\Collections\ArrayCollection;

class CreateTicketCommand implements Shared\Command
{
    /**
     * @var int|null
     */
    public $id;

    /**
     * @var int|Branch
     */
    public $branch;

    /**
     * @Assert\NotNull(
     *              message="This is a required field"
     * )
     * @PrintType()
     */
    public $subject;

    /**
     * @Assert\NotNull(
     *              message="This is a required field"
     * )
     * @Assert\Type(type="string")
     * @Assert\NotBlank(
     *              message="This is a required field"
     * )
     */
    public $description;

    /**
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     */
    public $status;

    /**
     * @Assert\NotNull(
     *              message="This is a required field"
     * )
     * @User()
     */
    public $reporter;

    /**
     * @Any({@Assert\Type(type="integer"), @Assert\Type(type="object")})
     */
    public $assignee;

    /**
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     */
    public $priority;

    /**
     * @Assert\NotNull()
     * @Assert\Type(type="string")
     */
    public $source;

    /**
     * @var \Diamante\DeskBundle\Api\Dto\AttachmentInput[]
     */
    public $attachmentsInput;
}