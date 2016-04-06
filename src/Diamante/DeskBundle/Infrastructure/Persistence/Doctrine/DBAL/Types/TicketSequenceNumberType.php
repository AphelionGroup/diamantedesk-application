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
namespace Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types;

use Diamante\DeskBundle\Model\Ticket\TicketSequenceNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

class TicketSequenceNumberType extends IntegerType
{
    const TICKET_SEQUENCE_NUMBER_TYPE = 'ticket_sequence_number';

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::TICKET_SEQUENCE_NUMBER_TYPE;
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return TicketSequenceNumber
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return new TicketSequenceNumber((int)$value);
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return mixed|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return '';
        }
        if (false === ($value instanceof TicketSequenceNumber)) {
            throw new \RuntimeException("Value should be a Ticket Sequence Number type.");
        }
        return parent::convertToDatabaseValue($value->getValue(), $platform);
    }
}
