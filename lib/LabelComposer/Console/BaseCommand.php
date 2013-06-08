<?php
namespace LabelComposer\Console;

use LabelComposer\pdf\Renderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command {

  var $config;
  var $layout;

  protected function configure() {
    $this->addArgument('config', InputArgument::REQUIRED, 'The configuration');
    $this->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'File name', 'labels.pdf');
  }

  protected function loadLayout() {
    $template = simplexml_load_file(__DIR__  . '/../pdf/template.xml');
    $type = $this->config->attributes()->type;
    $layout = reset($template->xpath("//label[@type='$type']/@layout"));
    $layout_path = "//layout[@id='$layout']";
    $this->layout = reset($template->xpath($layout_path))->attributes();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $config_file = $input->getArgument('config');
    $this->config = simplexml_load_file($config_file);
    $this->loadLayout();

    $output_file = $input->getOption('output');

    $labels = new Renderer($this->config);

    $labels->setData($this->loadData($input, $output));
    $labels->setOffset($this->getOfset($input, $output));
    $labels->toFile($output_file);
  }

  abstract protected function loadData(InputInterface $input, OutputInterface $output);

  protected function getOfset(InputInterface $input, OutputInterface $output) {
    return 0;
  }
}