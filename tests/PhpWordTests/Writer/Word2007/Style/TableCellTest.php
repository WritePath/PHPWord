<?php

/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PHPWord
 *
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWordTests\Writer\Word2007\Style;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWordTests\TestHelperDOCX;

/**
 * Test class for PhpOffice\PhpWord\Writer\Word2007\Style\Table.
 *
 * @coversDefaultClass \PhpOffice\PhpWord\Writer\Word2007\Style\Table
 *
 * @runTestsInSeparateProcesses
 */
class TableCellTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Executed before each method of the class.
     */
    protected function tearDown(): void
    {
        TestHelperDOCX::clear();
    }

    /**
     * Test write styles.
     */
    public function testCellPadding(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $table->addRow();

        $testValTop = Converter::pixelToTwip(10);
        $testValRight = Converter::pixelToTwip(11);
        $testValBottom = Converter::pixelToTwip(12);
        $testValLeft = Converter::pixelToTwip(13);

        $cellStyle = [
            'paddingTop' => $testValTop,
            'paddingRight' => $testValRight,
            'paddingBottom' => $testValBottom,
            'paddingLeft' => $testValLeft,
        ];
        $table->addCell(null, $cellStyle)->addText('Some text');
        $doc = TestHelperDOCX::getDocument($phpWord, 'Word2007');

        $path = '/w:document/w:body/w:tbl/w:tr/w:tc/w:tcPr/w:tcMar/w:top';
        self::assertTrue($doc->elementExists($path));
        self::assertEquals($testValTop, $doc->getElementAttribute($path, 'w:w'));
        self::assertEquals(TblWidth::TWIP, $doc->getElementAttribute($path, 'w:type'));

        $path = '/w:document/w:body/w:tbl/w:tr/w:tc/w:tcPr/w:tcMar/w:start';
        self::assertTrue($doc->elementExists($path));
        self::assertEquals($testValLeft, $doc->getElementAttribute($path, 'w:w'));
        self::assertEquals(TblWidth::TWIP, $doc->getElementAttribute($path, 'w:type'));

        $path = '/w:document/w:body/w:tbl/w:tr/w:tc/w:tcPr/w:tcMar/w:bottom';
        self::assertTrue($doc->elementExists($path));
        self::assertEquals($testValBottom, $doc->getElementAttribute($path, 'w:w'));
        self::assertEquals(TblWidth::TWIP, $doc->getElementAttribute($path, 'w:type'));

        $path = '/w:document/w:body/w:tbl/w:tr/w:tc/w:tcPr/w:tcMar/w:end';
        self::assertTrue($doc->elementExists($path));
        self::assertEquals($testValRight, $doc->getElementAttribute($path, 'w:w'));
        self::assertEquals(TblWidth::TWIP, $doc->getElementAttribute($path, 'w:type'));
    }

    /**
     * 'down' must write a top-left-to-bottom-right border (w:tl2br, "\"), and
     * 'up' must write a top-right-to-bottom-left border (w:tr2bl, "/"), with no
     * border written for the other direction.
     */
    public function testCellDiagonal(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(null, ['diagonal' => 'down'])->addText('Down');
        $table->addCell(null, ['diagonal' => 'up'])->addText('Up');
        $doc = TestHelperDOCX::getDocument($phpWord, 'Word2007');

        $downCellPath = '/w:document/w:body/w:tbl/w:tr/w:tc[1]/w:tcPr/w:tcBorders';
        self::assertTrue($doc->elementExists($downCellPath . '/w:tl2br'));
        self::assertEquals('single', $doc->getElementAttribute($downCellPath . '/w:tl2br', 'w:val'));
        self::assertFalse($doc->elementExists($downCellPath . '/w:tr2bl'));

        $upCellPath = '/w:document/w:body/w:tbl/w:tr/w:tc[2]/w:tcPr/w:tcBorders';
        self::assertTrue($doc->elementExists($upCellPath . '/w:tr2bl'));
        self::assertEquals('single', $doc->getElementAttribute($upCellPath . '/w:tr2bl', 'w:val'));
        self::assertFalse($doc->elementExists($upCellPath . '/w:tl2br'));
    }

    /**
     * Diagonal borders must be written after the ordinary side borders,
     * as required by the w:tcBorders schema sequence.
     */
    public function testCellDiagonalWithSideBorders(): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(null, ['borderSize' => 6, 'borderColor' => 'FF0000', 'diagonal' => 'down'])->addText('Down');
        $table->addCell(null, ['borderSize' => 6, 'borderColor' => 'FF0000', 'diagonal' => 'up'])->addText('Up');
        $doc = TestHelperDOCX::getDocument($phpWord, 'Word2007');

        foreach ([1 => 'w:tl2br', 2 => 'w:tr2bl'] as $cell => $diagonal) {
            $path = '/w:document/w:body/w:tbl/w:tr/w:tc[' . $cell . ']/w:tcPr/w:tcBorders';
            foreach (['w:top', 'w:left', 'w:bottom', 'w:right'] as $side) {
                self::assertTrue($doc->elementExists($path . '/' . $side));
                self::assertTrue($doc->elementExists($path . '/' . $diagonal . '/preceding-sibling::' . $side));
            }
            self::assertSame($diagonal, $doc->getElement($path . '/*[last()]')->nodeName);
        }
    }
}
