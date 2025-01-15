<?php
declare(strict_types=1);

namespace Common;

class ExcelUtilities
{
    /**
     * Converte un numero intero nella stringa corrispondente alla colonna Excel
     * Assume che 1 = A
     * @param int $columnNumber
     * @return string
     */
    public static function GetExcelColumnName(int $columnNumber): string
    {
        $columnLetter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $columnLetter = chr($columnNumber % 26 + ord('A')) . $columnLetter;
            $columnNumber = (int)($columnNumber / 26);
        }
        return $columnLetter;
    }
}