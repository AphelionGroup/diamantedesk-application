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
namespace Diamante\DeskBundle;

use Diamante\DeskBundle\DependencyInjection\Compiler;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DiamanteDeskBundle extends Bundle
{
    /**
     * @var array
     */
    private $dataAuditTypes
        = [
            'status',
            'priority',
            'user_type',
            'attachment_file',
            'source'
        ];

    public function boot()
    {
        if (!Type::hasType('branch_logo')) {
            Type::addType(
                'branch_logo',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\BranchLogoType'
            );
        }
        if (!Type::hasType('priority')) {
            Type::addType(
                'priority',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\TicketPriorityType'
            );
        }
        if (!Type::hasType('attachment_file')) {
            Type::addType(
                'attachment_file',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\AttachmentFileType'
            );
        }
        if (!Type::hasType('status')) {
            Type::addType(
                'status',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\TicketStatusType'
            );
        }
        if (!Type::hasType('source')) {
            Type::addType(
                'source',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\TicketSourceType'
            );
        }
        if (!Type::hasType('ticket_sequence_number')) {
            Type::addType(
                'ticket_sequence_number',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\TicketSequenceNumberType'
            );
        }
        if (!Type::hasType('ticket_unique_id')) {
            Type::addType(
                'ticket_unique_id',
                'Diamante\DeskBundle\Infrastructure\Persistence\Doctrine\DBAL\Types\TicketUniqueIdType'
            );
        }

        foreach ($this->dataAuditTypes as $type) {
            if (!AuditFieldTypeRegistry::hasType($type)) {
                AuditFieldTypeRegistry::addType($type, $type);
            }
        }

        $em = $this->container->get('doctrine.orm.default_entity_manager');
        $conn = $em->getConnection();

        $conn->getDatabasePlatform()
            ->registerDoctrineTypeMapping('FILE', 'string');
    }

    /**
     * @see Symfony\Component\HttpKernel\Bundle\Bundle::build()
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\TagCompilerPass())
            ->addCompilerPass(new Compiler\RegisterSubscribersPass())
            ->addCompilerPass(new Compiler\RegisterNotificationOptionsProvidersPass())
            ->addCompilerPass(new Compiler\FilterTypesPass())
            ->addCompilerPass(new Compiler\RegisterPropertyHandlersPass())
            ->addCompilerPass(new Compiler\AssetExtensionPass());
    }
}
