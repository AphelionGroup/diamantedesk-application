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

namespace Diamante\AutomationBundle\Command;

use Diamante\AutomationBundle\Entity\TimeTriggeredRule;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunTimeTriggeredRuleCommand extends ContainerAwareCommand
{
    /**
     * @var TimeTriggeredRule
     */
    protected $rule;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     *
     */
    protected function configure()
    {
        $this->setName("diamante:cron:automation:time:run")
            ->addOption("rule-id", "id", InputOption::VALUE_REQUIRED, "Time triggered rule id")
            ->addOption("dry-run", "d", InputOption::VALUE_OPTIONAL, "Do not execute actions configured in rule");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $id = $input->getOption('rule-id');
        $this->rule = $this->getContainer()
            ->get('doctrine')
            ->getRepository("DiamanteAutomationBundle:TimeTriggeredRule")
            ->get($id);

        if (empty($this->rule)) {
            throw new \InvalidArgumentException(sprintf("No rule with id %d found, aborting", $id));
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->rule->isActive()) {
            return 0;
        }
        
        $engine = $this->getContainer()->get('diamante_automation.engine');
        $dryRun = $input->hasParameterOption("--dry-run");

        $output->writeln(sprintf("<info>Started processing rule: %s</info>", $this->rule->getName()));

        if ($dryRun) {
            $output->writeln("<info>Dry Run option is enabled, none of the actions would be run</info>");
        }

        $result = $engine->processRule($this->rule, $dryRun);

        $output->writeln(sprintf("<info>Processing finished. %d entities processed.</info>", $result));

        $this->em->flush();

        return 0;
    }
}