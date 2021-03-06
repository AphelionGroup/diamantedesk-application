<?php

namespace Diamante\DeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * WatcherList
 *
 * @ORM\Table(name="diamante_watcher_list")
 * @ORM\Entity(repositoryClass="Diamante\DeskBundle\Infrastructure\Persistence\DoctrineWatcherListRepository")
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW;CREATE;EDIT;DELETE",
 *              "group_name"="DiamanteDesk"
 *          },
 *          "dataaudit" = {"auditable"=true}
 *      }
 * )
 */
class WatcherList extends \Diamante\DeskBundle\Model\Ticket\WatcherList
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Ticket
     * @ORM\ManyToOne(targetEntity="Ticket", inversedBy="watcherList", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="cascade")
     *
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={"auditable"=true}
     *      }
     * )
     */
    protected $ticket;

    /**
     * @var string
     *
     * @ORM\Column(name="user_type", type="user_type", length=255, nullable=false)
     *
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={"auditable"=true}
     *      }
     * )
     */
    protected $userType;

    public static function getClassName()
    {
        return __CLASS__;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'Watcher';
    }
}
