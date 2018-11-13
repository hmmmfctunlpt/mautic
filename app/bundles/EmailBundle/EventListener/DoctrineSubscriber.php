<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Monolog\Logger;

/**
 * Class DoctrineSubscriber.
 */
class DoctrineSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * DoctrineSubscriber constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchema,
        ];
    }

    /**
     * @param GenerateSchemaEventArgs $args
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $args)
    {
        $schema = $args->getSchema();

        try {
            if (!$schema->hasTable(MAUTIC_TABLE_PREFIX.'email_stats')) {
                return;
            }

            $statsTable = $schema->getTable(MAUTIC_TABLE_PREFIX.'email_stats');

            if ($statsTable->hasColumn('generated_sent_date')) {
                return;
            }

            $statsTable->addColumn(
                'generated_sent_date',
                GeneratedType::NAME,
                [
                    'columnDefinition' => "DATE AS (CONCAT(YEAR(date_sent), '-', LPAD(MONTH(date_sent), 2, '0'), '-', LPAD(DAY(date_sent), 2, '0'))) COMMENT '(DC2Type:generated)'",
                    'notNull'          => false,
                ]
            );

            $statsTable->addIndex(['email_id', 'generated_sent_date'], MAUTIC_TABLE_PREFIX.'email_id_date_string');
        } catch (\Exception $e) {
            //table doesn't exist or something bad happened so oh well
            $this->logger->addError('SCHEMA ERROR: '.$e->getMessage());
        }
    }
}
