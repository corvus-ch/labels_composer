<?php
namespace LabelComposer\Console;

use LabelComposer\pdf\Renderer;
use LabelComposer\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RandomDataCommand extends BaseCommand {

  var $timestamp;

  protected function configure() {
    $this->setName('random');
    $this->setDescription('Create labels');
    $this->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'Number of pages to generate', 1);
    $this->timestamp = time();
    parent::configure();
  }

  protected function loadData(InputInterface $input, OutputInterface $output) {
    $pages = $input->getOption('pages');

    $data = array();
    $count = $this->layout->rows * $this->layout->cols * $pages;
    while (count($data) < $count) {
      $barcodestring = strtoupper(dechex($this->timestamp) . dechex(rand(4096, 65535)));
      if (!in_array($barcodestring, $data)) {
        $data[] = $barcodestring;
      }
    }
    return $data;
  }
}