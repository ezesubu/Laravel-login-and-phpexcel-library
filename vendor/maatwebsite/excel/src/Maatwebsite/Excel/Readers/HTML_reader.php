<?php

/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2013 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_Reader
 * @copyright  Copyright (c) 2006 - 2013 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt    LGPL
 * @version    ##VERSION##, ##DATE##
 */

namespace Maatwebsite\Excel\Readers;

use \PHPExcel;
use \PHPExcel_Reader_HTML;
use \PHPExcel_Style_Color;
use \PHPExcel_Style_Border;

class HTML_reader extends \PHPExcel_Reader_HTML
{

    /**
     * Input encoding
     *
     * @var string
     */
    private $_inputEncoding = 'ANSI';

    /**
     * Sheet index to read
     *
     * @var int
     */
    private $_sheetIndex    = 0;

    private $_formats = array(
        'h1' => array(
            'font' => array(
                'bold' => true,
                'size' => 24,
            ),
        ),
        'strong' => array(
            'font' => array(
                'bold' => true,
                'size' => 12,
            ),
        ),
        'b' => array(
            'font' => array(
                'bold' => true,
                'size' => 12,
            ),
        ),
        'i' => array(
            'font' => array(
                'italic' => true,
                'size' => 12,
            ),
        ),  //  Bold, 24pt
       'h2' => array(
            'font' => array(
                'bold' => true,
                'size' => 18,
                ),
            ),  //  Bold, 18pt
       'h3' => array(
            'font' => array(
                'bold' => true,
                'size' => 13.5,
                ),
            ),  //  Bold, 13.5pt
       'h4' => array(
            'font' => array(
                'bold' => true,
                'size' => 12,
                ),
            ),  //  Bold, 12pt
       'h5' => array(
            'font' => array(
                'bold' => true,
                'size' => 10,
                ),
            ),  //  Bold, 10pt
       'h6' => array(
            'font' => array(
                'bold' => true,
                'size' => 7.5,
                ),
            ),  //  Bold, 7.5pt
       'a'  => array(
            'font' => array(
                'underline' => true,
                'color' => array( 'argb' => PHPExcel_Style_Color::COLOR_BLUE),
            ),
        ),  //  Blue underlined
       'hr' => array(
            'borders' => array(
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array(PHPExcel_Style_Color::COLOR_BLACK)
                ),
            ),
        ),  //  Bottom border
     );

    /**
     * Loads PHPExcel from file
     *
     * @param   string      $pFilename
     * @return  PHPExcel
     * @throws  PHPExcel_Reader_Exception
     */
    public function load($pFilename, $isString = false)
    {
        // Create new PHPExcel
        $objPHPExcel = new \PHPExcel();

        // Load into this instance
        return $this->loadIntoExisting($pFilename, $objPHPExcel, $isString);
    }

    /**
     * Loads PHPExcel from file into PHPExcel instance
     *
     * @param   string      $pFilename
     * @param   PHPExcel    $objPHPExcel
     * @return  PHPExcel
     * @throws  PHPExcel_Reader_Exception
     */
    public function loadIntoExisting($pFilename, PHPExcel $objPHPExcel, $isString = false)
    {

        $isHtmlFile = FALSE;

        // Check if it's a string or file
        if(!$isString)
        {
            // Double check if it's a file
            if(is_file($pFilename)){
               $isHtmlFile = TRUE;
               // Open file to validate
               $this->_openFile($pFilename);

               if (!$this->_isValidFormat()) {
                 fclose ($this->_fileHandle);
                 throw new PHPExcel_Reader_Exception($pFilename . " is an Invalid HTML file.");
               }

                fclose ($this->_fileHandle);
            }
        }

        // Create new PHPExcel
        while ($objPHPExcel->getSheetCount() <= $this->_sheetIndex) {
            $objPHPExcel->createSheet();
        }
        $objPHPExcel->setActiveSheetIndex( $this->_sheetIndex );

        //  Create a new DOM object
        $dom = new \domDocument;
        //  Reload the HTML file into the DOM object

        // Check if we need to load the file or the HTML
        if($isHtmlFile)
        {
            // Load HTML from file
            $loaded = $dom->loadHTMLFile($pFilename);
        }
        else
        {
            // Load HTML from string
            $loaded = @$dom->loadHTML(mb_convert_encoding($pFilename, 'HTML-ENTITIES', 'UTF-8'));
        }

        if ($loaded === FALSE) {
            throw new \PHPExcel_Reader_Exception('Failed to load ',$pFilename,' as a DOM Document');
        }

        //  Discard white space
        $dom->preserveWhiteSpace = true;


        $row = 0;
        $column = 'A';
        $content = '';
        $this->_processDomElement($dom,$objPHPExcel->getActiveSheet(),$row,$column,$content);
        $this->autosizeColumn($objPHPExcel);

        return $objPHPExcel;
    }

    /**
     * Autosize column for document
     *
     * @return int
     */
    public static function autosizeColumn(\PHPExcel $objPHPExcel)
    {
        foreach ($objPHPExcel->getAllSheets() as $sheet) {
            $toCol = $sheet->getHighestColumn();

            $toCol++;
            for ($i = 'A'; $i !== $toCol; $i++) {
                $sheet->getColumnDimension($i)->setAutoSize(true);
            }

            $sheet->calculateColumnWidths();
        }
    }

    private function _processDomElement(\DOMNode $element, $sheet, &$row, &$column, &$cellContent){
        foreach($element->childNodes as $child){
            if ($child instanceof \DOMText) {
                $domText = preg_replace('/\s+/',' ',trim($child->nodeValue));
                if (is_string($cellContent)) {
                    //  simply append the text if the cell content is a plain text string
                    $cellContent .= $domText;
                } else {
                    //  but if we have a rich text run instead, we need to append it correctly
                    //  TODO
                }
            } elseif($child instanceof \DOMElement) {
//              echo '<b>DOM ELEMENT: </b>' , strtoupper($child->nodeName) , '<br />';

                $attributeArray = array();
                foreach($child->attributes as $attribute) {
//                  echo '<b>ATTRIBUTE: </b>' , $attribute->name , ' => ' , $attribute->value , '<br />';
                    $attributeArray[$attribute->name] = $attribute->value;
                }

                switch($child->nodeName) {
                    case 'meta' :
                        foreach($attributeArray as $attributeName => $attributeValue) {
                            switch($attributeName) {
                                case 'content':
                                    //  TODO
                                    //  Extract character set, so we can convert to UTF-8 if required
                                    break;
                            }
                        }
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                        break;
                    case 'title' :
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                        $sheet->setTitle($cellContent);
                        $cellContent = '';
                        break;
                    case 'span'  :
                    case 'div'   :
                    case 'font'  :
                    case 'i'     :
                    case 'em'    :
                    case 'strong':
                    case 'b'     :

//                      echo 'STYLING, SPAN OR DIV<br />';
                        if ($cellContent > '')
                            $cellContent .= ' ';
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                        if ($cellContent > '')
                            $cellContent .= ' ';

                        // Set the styling
                        if (isset($this->_formats[$child->nodeName])) {
                            $sheet->getStyle($column.$row)->applyFromArray($this->_formats[$child->nodeName]);
                        }

//                      echo 'END OF STYLING, SPAN OR DIV<br />';
                        break;
                    case 'hr' :
                        $this->_flushCell($sheet,$column,$row,$cellContent);
                        ++$row;
                        if (isset($this->_formats[$child->nodeName])) {
                            $sheet->getStyle($column.$row)->applyFromArray($this->_formats[$child->nodeName]);
                        } else {
                            $cellContent = '----------';
                            $this->_flushCell($sheet,$column,$row,$cellContent);
                        }
                        ++$row;
                    case 'br' :
                        if ($this->_tableLevel > 0) {
                            //  If we're inside a table, replace with a \n
                            $cellContent .= "\n";
                        } else {
                            //  Otherwise flush our existing content and move the row cursor on
                            $this->_flushCell($sheet,$column,$row,$cellContent);
                            ++$row;
                        }
//                      echo 'HARD LINE BREAK: ' , '<br />';
                        break;
                    case 'a'  :
//                      echo 'START OF HYPERLINK: ' , '<br />';
                        foreach($attributeArray as $attributeName => $attributeValue) {
                            switch($attributeName) {
                                case 'href':
//                                  echo 'Link to ' , $attributeValue , '<br />';
                                    $sheet->getCell($column.$row)->getHyperlink()->setUrl($attributeValue);

                                    if (isset($this->_formats[$child->nodeName])) {
                                        $sheet->getStyle($column.$row)->applyFromArray($this->_formats[$child->nodeName]);
                                    }
                                    break;
                            }
                        }
                        $cellContent .= ' ';
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                      echo 'END OF HYPERLINK:' , '<br />';
                        break;
                    case 'h1' :
                    case 'h2' :
                    case 'h3' :
                    case 'h4' :
                    case 'h5' :
                    case 'h6' :
                    case 'ol' :
                    case 'ul' :
                    case 'p'  :

                        if ($this->_tableLevel > 0) {
                            //  If we're inside a table, replace with a \n
                            $cellContent .= "\n";
//                          echo 'LIST ENTRY: ' , '<br />';
                            $this->_processDomElement($child,$sheet,$row,$column,$cellContent);

                            $this->_flushCell($sheet,$column,$row,$cellContent);

                            if (isset($this->_formats[$child->nodeName])) {
                                $sheet->getStyle($column.$row)->applyFromArray($this->_formats[$child->nodeName]);
                            }

//                          echo 'END OF LIST ENTRY:' , '<br />';
                        } else {
                            if ($cellContent > '') {
                                $this->_flushCell($sheet,$column,$row,$cellContent);
                                $row += 2;
                            }
//                          echo 'START OF PARAGRAPH: ' , '<br />';
                            $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                          echo 'END OF PARAGRAPH:' , '<br />';
                            $this->_flushCell($sheet,$column,$row,$cellContent);

                            if (isset($this->_formats[$child->nodeName])) {
                                $sheet->getStyle($column.$row)->applyFromArray($this->_formats[$child->nodeName]);
                            }

                            $row += 2;
                            $column = 'A';
                        }
                        break;
                    case 'li'  :
                        if ($this->_tableLevel > 0) {
                            //  If we're inside a table, replace with a \n
                            $cellContent .= "\n";
//                          echo 'LIST ENTRY: ' , '<br />';
                            $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                          echo 'END OF LIST ENTRY:' , '<br />';
                        } else {
                            if ($cellContent > '') {
                                $this->_flushCell($sheet,$column,$row,$cellContent);
                            }
                            ++$row;
//                          echo 'LIST ENTRY: ' , '<br />';
                            $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                          echo 'END OF LIST ENTRY:' , '<br />';
                            $this->_flushCell($sheet,$column,$row,$cellContent);
                            $column = 'A';
                        }
                        break;
                    case 'table' :
                        $this->_flushCell($sheet,$column,$row,$cellContent);
                        $column = $this->_setTableStartColumn($column);
//                      echo 'START OF TABLE LEVEL ' , $this->_tableLevel , '<br />';
                        if ($this->_tableLevel > 1)
                            --$row;
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                      echo 'END OF TABLE LEVEL ' , $this->_tableLevel , '<br />';
                        $column = $this->_releaseTableStartColumn();
                        if ($this->_tableLevel > 1) {
                            ++$column;
                        } else {
                            ++$row;
                        }
                        break;
                    case 'thead' :
                    case 'tbody' :
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                        break;
                    case 'tr' :
                        $column = $this->_getTableStartColumn();
                        $cellContent = '';
//                      echo 'START OF TABLE ' , $this->_tableLevel , ' ROW<br />';
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                      echo 'END OF TABLE ' , $this->_tableLevel , ' ROW<br />';
//
//                      Count the rows after the element was parsed
                        ++$row;
                        break;
                    case 'th' :
                    case 'td' :
//                      echo 'START OF TABLE ' , $this->_tableLevel , ' CELL<br />';
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
//                      echo 'END OF TABLE ' , $this->_tableLevel , ' CELL<br />';
                        $this->_flushCell($sheet,$column,$row,$cellContent);
                        ++$column;
                        break;
                    case 'body' :
                        $row = 1;
                        $column = 'A';
                        $content = '';
                        $this->_tableLevel = 0;
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                        break;
                    default:
                        $this->_processDomElement($child,$sheet,$row,$column,$cellContent);
                }
            }
        }
    }

    //  Data Array used for testing only, should write to PHPExcel object on completion of tests
    private $_dataArray = array();

    private $_tableLevel = 0;
    private $_nestedColumn = array('A');

    private function _setTableStartColumn($column) {
        if ($this->_tableLevel == 0)
            $column = 'A';
        ++$this->_tableLevel;
        $this->_nestedColumn[$this->_tableLevel] = $column;

        return $this->_nestedColumn[$this->_tableLevel];
    }

    private function _getTableStartColumn() {
        return $this->_nestedColumn[$this->_tableLevel];
    }

    private function _releaseTableStartColumn() {
        --$this->_tableLevel;
        return array_pop($this->_nestedColumn);
    }

    private function _flushCell($sheet,$column,$row,&$cellContent) {
        if (is_string($cellContent)) {
            //  Simple String content
            if (trim($cellContent) > '') {
                //  Only actually write it if there's content in the string
//              echo 'FLUSH CELL: ' , $column , $row , ' => ' , $cellContent , '<br />';
                //  Write to worksheet to be done here...
                //  ... we return the cell so we can mess about with styles more easily
                $cell = $sheet->setCellValue($column.$row,$cellContent,true);
                $this->_dataArray[$row][$column] = $cellContent;
            }
        } else {
            //  We have a Rich Text run
            //  TODO
            $this->_dataArray[$row][$column] = 'RICH TEXT: ' . $cellContent;
        }
        $cellContent = (string) '';
    }

}