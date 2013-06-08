<?php
namespace LabelComposer\Console;

use LabelComposer\pdf\Renderer;
use LabelComposer\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SingleCommand extends BaseCommand {

  protected function configure() {
    $this->setName('single');
    $this->setDescription('Create labels');
    $this->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'Number of pages to generate', 1);
    parent::configure();
  }

  protected function loadData(InputInterface $input, OutputInterface $output) {
    $pages = $input->getOption('pages');
    return array_fill(0, $this->layout->rows * $this->layout->cols * $pages, '');
  }
}