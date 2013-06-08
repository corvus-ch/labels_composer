<?php
namespace LabelComposer;

use LabelComposer\Layout\Layout;
use tFPDF;

interface Config {

  function getData(Layout $layout, $pages);

  function renderCell(tFPDF $pdf, $top, $left, $data);
}