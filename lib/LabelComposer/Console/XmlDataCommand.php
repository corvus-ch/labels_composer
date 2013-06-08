<?php
namespace LabelComposer\Console;

use LabelComposer\pdf\Renderer;
use LabelComposer\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class XmlDataCommand extends BaseCommand {

  protected function configure() {
    $this->setName('xmldata');
    $this->setDescription('Create labels');
    $this->addOption('offset', 'd', InputOption::VALUE_OPTIONAL, 'Number of the first label position ot start printing', 0);
    parent::configure();
  }

  protected function loadData(InputInterface $input, OutputInterface $output) {
    $data = array();
    if (isset($this->config->data) && $this->config->data->attributes()->type == 'xml') {
      $data = $this->config->data->children();
    }
    return $data;
  }

  protected function getOfset(InputInterface $input, OutputInterface $output) {
    return $input->getOption('offset');
  }

}