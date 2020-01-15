<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Model\Connector\DataFieldAutoMapper;
use Dotdigitalgroup\Email\Model\Connector\DataFieldAutoMapperFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AutoMapDataFieldsCommand extends Command
{
    /**
     * @var DataFieldAutoMapperFactory
     */
    private $dataFieldAutoMapperFactory;

    /**
     * @param DataFieldAutoMapperFactory $dataFieldAutoMapperFactory
     */
    public function __construct(DataFieldAutoMapperFactory $dataFieldAutoMapperFactory)
    {
        $this->dataFieldAutoMapperFactory = $dataFieldAutoMapperFactory;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('dotdigital:connector:automap')
            ->setDescription(__('Auto-map data fields'));

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(__('Auto-mapping data fields')->getText());

        /** @var DataFieldAutoMapper $dataFieldMapper */
        $dataFieldMapper = $this->dataFieldAutoMapperFactory->create()
            ->run();

        if (!empty($dataFieldMapper->getMappingErrors())) {
            $output->writeln(__('There was a problem mapping data fields. Please check the connector log.')->getText());
        } else {
            $output->writeln(__('Data fields have been mapped.')->getText());
        }
    }
}
