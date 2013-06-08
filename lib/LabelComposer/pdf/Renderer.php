<?php

namespace LabelComposer\pdf;

use SimpleXMLElement;
use tFPDF;

/**
 * Template class for Avery Zwekform labels
 *
 * Deal with square labels arranged in a layout of x * y labels.
 */
class Renderer {

  /**
   * The PDF object
   *
   * @var FPDF
   */
  protected $pdf;

  protected $cell;
  
  protected $spacing;

  protected $layout;

  /**
   * The cell rendering markup
   *
   * @var SimpleXMLElement[]
   */
  protected $content;

  /**
   * @var The first label position where the rendering will start.
   */
  protected $offset;

  /**
   * @var array Data to be rendered in the cells.
   */
  protected $data;

  /**
   * @var int Number of current row on the page.
   */
  protected $row = 0;

  /**
   * @var int Number of current column on the page
   */
  protected $column = 0;

  /**
   * Constructor
   *
   * @param Layout\Layout $layout
   *  An implementation of the layout interface.
   * @param $config
   *  The file name of the file that renders the cell content.
   */
  function __construct(SimpleXMLElement $xml) {

    $this->content = $xml->content;

    $template = simplexml_load_file(__DIR__  . '/template.xml');
    $type = $xml->attributes()->type;
    $layout = reset($template->xpath("//label[@type='$type']/@layout"));
    $layout_path = "//layout[@id='$layout']";
    $this->layout = reset($template->xpath($layout_path))->attributes();
    $page = reset($template->xpath($layout_path . '/page'))->attributes();
    $margin = reset($template->xpath($layout_path . '/margin'))->attributes();
    $this->cell = reset($template->xpath($layout_path . '/cell'))->attributes();
    $this->spacing = reset($template->xpath($layout_path . '/spacing'))->attributes();

    $this->pdf = new tFPDF((string) $page->orientation, (string) $page->unit, (string) $page->format);
    $this->pdf->SetMargins((float) $margin->left, (float) $margin->top, 0);
    $this->pdf->SetAutoPageBreak(FALSE);
  }


  /**
   * Set data
   *
   * @param array $data
   *  A numeric array. Each element is passed to on label.
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * Set start label
   *
   * @param $offset
   *  The number of the first label to print. Counting columns, rows.
   */
  public function setOffset($offset) {
    $this->offset = $offset;
  }

  /**
   * Trigger the drawing process
   */
  protected function draw() {
    // Print empty cells according to the offset.
    while ($this->offset > 0) {
      $this->drawCell(NULL);
      $this->offset--;
    }
    // Print actual cells.
    foreach ($this->data as $data) {
      $this->drawCell($data);
    }
  }

  private function getAttribute($item, $attribute, $default = '') {
    $value = $item->attributes()->$attribute ? (string) $item->attributes()->$attribute : $default;
    return $value;
  }


    private function getValue(SimpleXMLElement $item, $data) {
      $value = (string) $item;
      if ($this->content->attributes()->token == 'true') {
          preg_match_all('/\[([^\]]+)\]/', $value, $matches);
          foreach ($matches[1] as $delta => $match) {
            if ($data instanceof SimpleXMLElement) {
              $replacement = (string) $data->attributes()->$match;
            }
            else if ($match == 'data') {
              $replacement = $data;
            }
            $value = str_replace($matches[0][$delta], $replacement, $value);
        }
        $transform = (string) $item->attributes()->transform;
        if (function_exists($transform)) {
          $value = call_user_func($transform, $value);
        }
      }
      return utf8_decode($value);
    }

    /**
   * Draw a cell
   *
   * @param mixed $data
   *  The data to be printed at the cell.
   */
  protected function drawCell($data) {

    // Start a new page if we are at the first row and column.
    if ($this->row == 0 && $this->column == 0) {
      $this->pdf->AddPage();
    }

    $width = (float) $this->cell->w;
    $height = (float) $this->cell->h;

    // Create a new cell and update the cells origin.
    $this->pdf->Cell($width, $height, '', 0);
    $left = $this->pdf->GetX() - $width;
    $top = $this->pdf->GetY();

    // Trigger the cell rendering.
    if (!is_null($data)) {
      $cel_family = $this->getAttribute($this->content, 'family', 'Arial');
      $cel_style = $this->getAttribute($this->content, 'style');
      $cel_size = $this->getAttribute($this->content, 'size', '12');
      foreach ($this->content->children() as $item) {
        $x = $this->getAttribute($item, 'x') + $left;
        $y = $this->getAttribute($item, 'y') + $top;
        $family = $this->getAttribute($item, 'family', $cel_family);
        $style = $this->getAttribute($item, 'style', $cel_style);
        $size = $this->getAttribute($item, 'size', $cel_size);
        $this->pdf->SetFont($family, $style, $size);
        $object = $item->getName();
        $value = $this->getValue($item, $data);
        switch ($object) {
          case 'Image':
            $w = $this->getAttribute($item, 'w', 0);
            $h = $this->getAttribute($item, 'h', 0);
            $type = array_pop(explode('.', $item));
            $this->pdf->Image($value, $x, $y, $w, $h, $type);
            break;
          case 'Text':
            $this->pdf->Text($x, $y, $value);
            break;
          case 'Code':

            $class = 'BCG' . $this->getAttribute($item, 'type', 'code128');
            require_once __DIR__ . "/../../barcodegen/class/$class.barcode.php";
            $class = '\\' . $class;

            $image = tempnam(sys_get_temp_dir(), 'php');

            $color_background = new \BCGColor(255, 255, 255);
            $color_foreground = new \BCGColor(0, 0, 0);


            $scale = 5;

            $barcode = new $class();
            $thickness = $this->getAttribute($item, 'thickness');
            if ($thickness) {
              $barcode->setThickness($thickness);
            }
            $barcode->setScale($scale);
            $font = ucfirst($family);
            $barcode->setFont(new \BCGFont(__DIR__ . "/../../barcodegen/class/font/$font.ttf", $size * $scale));
            $barcode->setBackgroundColor($color_background);
            $barcode->setForegroundColor($color_foreground);
            $barcode->parse($value);

            $drawing = new \BCGDrawing($image, $color_background);
            $drawing->setBarcode($barcode);
            $drawing->setDPI(600);
            $drawing->draw();
            $drawing->finish(1);
            $w = $this->getAttribute($item, 'w', 0);
            $h = $this->getAttribute($item, 'h', 0);

            if ($this->getAttribute($item, 'x') == 'c') {
              $img_info = $this->pdf->_parsepng($image);
              $img_width = $img_info['w'];
              $img_height = $img_info['h'];
              $factor = $h / $img_height;
              $img_width = $img_width * $factor;
              $img_height = $img_height * $factor;
              $x = ($this->cell->w - $img_width) / 2;
              $x = $left + $this->cell->w / 2 - $img_width / 2;
            }

            $this->pdf->Image($image, $x, $y, $w, $h, 'png');
            break;
        }
      }
    }

    // Reached the end of a row
    if ($this->column == $this->layout->cols - 1) {
      // Reached the end of a page
      // Reset row and column index so a new page will be created.
      if ($this->row == $this->layout->rows -1) {
        $this->row = 0;
        $this->column = 0;
      }
      // Create a new line
      else {
        $this->pdf->Ln($height + (float) $this->spacing->v);
        $this->row++;
        $this->column = 0;
      }
    }
    // There is space left in the current row. Add spacing.
    else {
      $this->pdf->Cell((float) $this->spacing->h, $height, '', 0);
      $this->column++;
    }
  }

  /**
   * Print to file
   *
   * Triggers the drawing process, capture the output and write it to
   * a file.
   * @param $filename
   *  The name of the output file. Will be created if not exists.
   *  Already existing files will be overithen without notice.
   */
  function toFile($filename) {
    $this->draw();
    ob_start();
    $this->pdf->Output();
    $contents = ob_get_contents();
    ob_end_clean();
    file_put_contents($filename, $contents);
  }
}
