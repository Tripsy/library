<?php
/**
 *
 * @version 1.0.0
 * @author Gabriel David <gabriel.david@play-zone.ro>
 *
 * https://github.com/phpoffice/phpspreadsheet
 *
 */

namespace Tripsy\Library\Excel;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Tripsy\Library\Exceptions\ConfigException;
use Tripsy\Library\Standard\BuildHeaders;
use Tripsy\Library\Standard\FileTools;

class Excel
{
    public function __construct()
    {
    }

    /**
     * Build letter range (eg: [A, B, C, D, E]
     * Notes:
     *      For $col as int => letter range starting with A and with $col number of elements
     *      For $col as string (only make sense for 2 letters) => letter range starting from A until $col value
     *      Prefix is appended when `strlen($col) == 1` (eg: [LA, LB, LC, LD ...]) else is ignored
     *
     * @param int|string $col
     * @param string $prefix
     * @return array
     */
    public function columnRange(int|string $col, string $prefix = ''): array
    {
        if (is_int($col)) { //build [] starting with A counting $col elements
            $arr_default = range('A', 'Z');
            $col = $col - 1;

            if ($col < 26) {
                $col = $arr_default[$col];
            } else {
                $s = $col % 26;
                $f = ceil($col / 26) - 2;

                if ($s == 0) {
                    $f++;
                }

                $col = $arr_default[$f] . $arr_default[$s];
            }
        }

        $c = strlen($col);

        if ($c == 1) {
            $range = range('A', $col);

            if ($prefix) {
                $range = array_map(function ($v, $prefix) {
                    return $prefix . $v;
                }, $range, array_fill(0, count($range), $prefix));
            }
        } else {
            $range = range('A', 'Z');
            $first = $col[0];
            $second = $col[1];
            $arr_prefix = range('A', $first);

            foreach ($arr_prefix as $prefix) {
                if ($first == $prefix) {
                    $range = array_merge($range, $this->columnRange($second, $prefix));
                } else {
                    $range = array_merge($range, $this->columnRange('Z', $prefix));
                }
            }
        }

        return $range;
    }

    /**
     * Open Excel file for read / write
     *
     * @param string $file_path
     * @param int|string|null $sheet If set load selected sheet (note: sheet as string is converted to int)
     * @return Worksheet
     * @throws Exception
     */
    public function open(string $file_path, int|string $sheet = null): Worksheet
    {
        $spreadsheet = IOFactory::load($file_path);

        if (is_null($sheet) === false) {
            if (is_int($sheet) === false) {
                $sheet = $spreadsheet->getIndex(
                    $spreadsheet->getSheetByName($sheet)
                );
            }

            $spreadsheet->setActiveSheetIndex($sheet);
        }

        return $spreadsheet->getActiveSheet();
    }

    /**
     * Convert Excel file to array
     *
     * @param Worksheet $objWorksheet
     * @param array $columns Associative array with letter (eg: column) and corresponding key
     * @param int $startRow Starting row number
     * @param int $limitRow Maximum number of rows to parse
     * @param array $required
     * @return array
     */
    public function convertToArray(Worksheet $objWorksheet, array $columns, int $startRow = 2, int $limitRow = 999, array $required = []): array
    {
        $data = [];

        for ($i = $startRow; $i <= $limitRow; $i++) {
            foreach ($columns as $letter => $key) {
                $v = $objWorksheet->getCell($letter . $i)->getFormattedValue();

                if (in_array($key, $required) && empty($v) === true) {
                    unset($data[$i]);

                    break 2;
                }

                $data[$i][$key] = $v;
            }
        }

        return $data;
    }

    /**
     * Save Excel file to specified path
     *
     * @param string $file_path
     * @param array $sheets array with DataSheet objects
     * @param DataProperties $properties
     * @return void
     * @throws ConfigException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function save(string $file_path, array $sheets, DataProperties $properties): void
    {
        $file_type = FileTools::getExtension($file_path);

        //excel -> save
        $writer = $this->writer(ucfirst($file_type), $sheets, $properties);
        $writer->save($file_path);
    }

    /**
     * Output as download a generated Excel file
     *
     * @param BuildHeaders $header
     * @param $file_name
     * @param array $sheets array with DataSheet objects
     * @param DataProperties $properties
     * @return void
     * @throws ConfigException
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function download(BuildHeaders $header, $file_name, array $sheets, DataProperties $properties): void
    {
        $file_type = FileTools::getExtension($file_name);

        //excel -> save
        $writer = $this->writer(ucfirst($file_type), $sheets, $properties);

        $header
            ->setContentType($file_type)
            ->add('Content-Disposition', 'attachment; filename="' . $file_name . '"')
            ->add('Cache-Control', 'no-store')
            ->output();

        $writer->save('php://output');

        exit();
    }

    /**
     * Used to build Excel file
     *
     * @param string $type
     * @param array $sheets
     * @param DataProperties $properties
     * @return IWriter
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws ConfigException
     * @throws Exception
     */
    private function writer(string $type, array $sheets, DataProperties $properties): IWriter
    {
        $spreadsheet = new Spreadsheet();

        $this->setSpreadsheetProperties($spreadsheet, $properties);

        foreach ($sheets as $sheetIndex => $sheetData) {
            if ($sheetIndex > 0) {
                $spreadsheet->createSheet();
            }

            $this->buildSpreadsheet($spreadsheet, $sheetIndex, $sheetData);
        }

        return IOFactory::createWriter($spreadsheet, $type);
    }

    /**
     * Used to set Excel file properties
     *
     * @param Spreadsheet $spreadsheet
     * @param DataProperties $dataProperties
     * @return void
     * @throws ConfigException
     */
    private function setSpreadsheetProperties(Spreadsheet &$spreadsheet, DataProperties $dataProperties): void
    {
        $spreadsheet->getProperties()->setCreator($dataProperties->get('creator'));
        $spreadsheet->getProperties()->setTitle($dataProperties->get('title'));
        $spreadsheet->getProperties()->setDescription($dataProperties->get('description'));
    }

    /**
     * Used to build the content for the Excel file
     *
     * @param Spreadsheet $spreadsheet
     * @param int $sheetIndex
     * @param DataSheet $sheetData
     * @return void
     * @throws Exception
     * @throws ConfigException
     */
    private function buildSpreadsheet(Spreadsheet &$spreadsheet, int $sheetIndex, DataSheet $sheetData): void
    {
        $spreadsheet->setActiveSheetIndex($sheetIndex);
        $objWorksheet = $spreadsheet->getActiveSheet();
        $objWorksheet->setTitle($sheetData->get('title'));

        $columns = $sheetData->get('columns');
        $rows = $sheetData->get('rows');
        $filter = $sheetData->get('filter');

        $range = $this->columnRange(count($columns));

        $rowIndex = 1;
        $columnIndex = 0;
        $columnLetterLast = 'A';

        //excel -> columns
        foreach ($columns as $k => $v) {
            $columnLetterLast = $range[$columnIndex];
            $columns[$k]['letter'] = $columnLetterLast;

            $objWorksheet->setCellValue($columnLetterLast . $rowIndex, $v['name']);

            $columnIndex++;
        }

        //excel -> data
        foreach ($rows as $rowData) {
            $rowIndex++;

            $this->addSheetRowData($objWorksheet, $columns, $rowIndex, $rowData);
        }

        //excel -> style
        $objWorksheet->getStyle('A1:' . $columnLetterLast . '1')->applyFromArray(array(
            'font' => array(
                'bold' => true,
            ),
            'alignment' => array(
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array(
                    'argb' => 'ffe7ebfa',
                ),
            ),
        ));

        //excel -> setup
        foreach ($columns as $v) {
            //alignment
            if (empty($v['horizontal']) === false) {
                $objWorksheet->getStyle($v['letter'] . '2:' . $v['letter'] . $rowIndex)->applyFromArray(array(
                    'alignment' => array(
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ),
                ));
            }

            //size
            if (empty($v['size']) === true) {
                $objWorksheet->getColumnDimension($v['letter'])->setAutoSize(true);
            } else {
                $objWorksheet->getColumnDimension($v['letter'])->setWidth($v['size']);
            }
        }

        //excel -> freeze
        $objWorksheet->freezePane('A2');

        //excel -> filter
        if ($filter) {
            $objWorksheet->setAutoFilter($filter);
        }
    }

    /**
     * Used to add a row with data in the Excel file
     *
     * @param Worksheet $objWorksheet
     * @param array $columns
     * @param int $rowIndex
     * @param array $rowData
     * @return void
     */
    private function addSheetRowData(Worksheet &$objWorksheet, array $columns, int $rowIndex, array $rowData): void
    {
        foreach ($rowData as $k => $v) {
            if (array_key_exists($k, $columns)) {
                $letter = $columns[$k]['letter'];
            } else {
                continue;
            }

            if (is_array($v)) {
                //excel -> data
                $objWorksheet->setCellValue($letter . $rowIndex, $v['value']);

                if (empty($v['format'])) {
                    //excel -> style
                    $objWorksheet->getStyle($letter . $rowIndex)->getNumberFormat()->setFormatCode($v['format']);
                    //0.00%;[Red]-0.00% > procent; red for negative values
                    //#,##0.00;[Red]-#,##0.00 > number; red for negative values
                    //[$€ ]#,##0.00_-
                    //$#,##0_-
                }

                if (empty($v['background'])) {
                    //excel -> style
                    $objWorksheet->getStyle($letter . $rowIndex)->applyFromArray(array(
                        'fill' => array(
                            'fillType' => Fill::FILL_SOLID,
                            'color' => array('rgb' => $sv)
                        ),
                    ));
                }
            } else {
                //excel -> data
                $objWorksheet->setCellValue($letter . $rowIndex, $v);
            }
        }
    }
}
