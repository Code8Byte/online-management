<?php
/**
 * SimpleXlsxWriter
 * Lightweight, zero-dependency streaming OpenXML (.xlsx) generator.
 * Produces 100% valid Excel spreadsheets adhering strictly to ECMA-376 schema.
 */
class SimpleXlsxWriter {
    private $zip;
    private $tempFile;
    private $sheetFile;
    private $sheetWriter;
    private $mergeCells = [];
    private $colWidths = [];
    private $freezeRow = 6;
    private $orientation = 'landscape';
    private $paperSize = 9; // A4

    public function __construct() {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $this->zip = new ZipArchive();
        $this->zip->open($this->tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $this->sheetFile = tempnam(sys_get_temp_dir(), 'sheet_');
        $this->sheetWriter = fopen($this->sheetFile, 'w');
        fwrite($this->sheetWriter, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($this->sheetWriter, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n");
        fwrite($this->sheetWriter, '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" ySplit="6" topLeftCell="A7" activePane="bottomLeft"/></sheetView></sheetViews>' . "\n");
        fwrite($this->sheetWriter, '<sheetFormatPr defaultRowHeight="18"/>' . "\n");
    }

    public function setFreezePane($row = 4) {
        $this->freezeRow = (int)$row;
    }

    public function setPageSetup($orientation = 'landscape', $paperSize = 9) {
        $this->orientation = $orientation;
        $this->paperSize = (int)$paperSize;
    }

    public function setColWidths(array $widths) {
        $this->colWidths = $widths;
    }

    public function addMerge($ref) {
        $this->mergeCells[] = $ref;
    }

    public function writeRow($rowNum, array $cells, $height = null) {
        $hAttr = $height ? ' ht="' . $height . '" customHeight="1"' : '';
        fwrite($this->sheetWriter, '<row r="' . $rowNum . '"' . $hAttr . '>' . "\n");
        foreach ($cells as $colNum => $cell) {
            $colLetter = $this->colLetter($colNum);
            $cellRef = $colLetter . $rowNum;
            $s = isset($cell['style']) ? ' s="' . $cell['style'] . '"' : '';
            $val = $cell['value'] ?? null;
            $type = $cell['type'] ?? 'string';

            if ($val === null || $val === '') {
                if ($s) {
                    fwrite($this->sheetWriter, '<c r="' . $cellRef . '"' . $s . '/>' . "\n");
                }
                continue;
            }

            if (is_string($val) && str_starts_with($val, '=')) {
                $formula = substr($val, 1);
                fwrite($this->sheetWriter, '<c r="' . $cellRef . '"' . $s . '><f>' . htmlspecialchars($formula, ENT_XML1, 'UTF-8') . '</f></c>' . "\n");
            } elseif ($type === 'number') {
                fwrite($this->sheetWriter, '<c r="' . $cellRef . '"' . $s . '><v>' . $val . '</v></c>' . "\n");
            } else {
                $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                fwrite($this->sheetWriter, '<c r="' . $cellRef . '"' . $s . ' t="inlineStr"><is><t>' . $escaped . '</t></is></c>' . "\n");
            }
        }
        fwrite($this->sheetWriter, '</row>' . "\n");
    }

    public function colLetter($colIdx) {
        $letter = '';
        while ($colIdx > 0) {
            $mod = ($colIdx - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIdx = (int)(($colIdx - $mod) / 26);
        }
        return $letter;
    }

    public function output($filename) {
        fclose($this->sheetWriter);

        $finalSheet = tempnam(sys_get_temp_dir(), 'fullsheet_');
        $fw = fopen($finalSheet, 'w');
        
        $src = fopen($this->sheetFile, 'r');
        $line1 = fgets($src); fwrite($fw, $line1); // xml
        $line2 = fgets($src); fwrite($fw, $line2); // worksheet
        // Sheet properties with fitToPage
        fwrite($fw, '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' . "\n");
        
        // Sheet views with customizable freeze pane
        fgets($src); // skip original sheetViews line
        if ($this->freezeRow > 0) {
            $topCell = 'A' . ($this->freezeRow + 1);
            fwrite($fw, '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" ySplit="' . $this->freezeRow . '" topLeftCell="' . $topCell . '" activePane="bottomLeft"/></sheetView></sheetViews>' . "\n");
        } else {
            fwrite($fw, '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>' . "\n");
        }
        $line4 = fgets($src); fwrite($fw, $line4); // sheetFormatPr

        // Add cols
        if (!empty($this->colWidths)) {
            fwrite($fw, '<cols>' . "\n");
            foreach ($this->colWidths as $col => $w) {
                fwrite($fw, '<col min="' . $col . '" max="' . $col . '" width="' . $w . '" customWidth="1"/>' . "\n");
            }
            fwrite($fw, '</cols>' . "\n");
        }

        fwrite($fw, '<sheetData>' . "\n");
        while (!feof($src)) {
            $chunk = fread($src, 65536);
            fwrite($fw, $chunk);
        }
        fclose($src);
        fwrite($fw, '</sheetData>' . "\n");

        // Sheet Protection (read-only allowed to select cells)
        fwrite($fw, '<sheetProtection sheet="1" objects="1" scenarios="1" selectLockedCells="1" selectUnlockedCells="1"/>' . "\n");

        if (!empty($this->mergeCells)) {
            fwrite($fw, '<mergeCells count="' . count($this->mergeCells) . '">' . "\n");
            foreach ($this->mergeCells as $ref) {
                fwrite($fw, '<mergeCell ref="' . $ref . '"/>' . "\n");
            }
            fwrite($fw, '</mergeCells>' . "\n");
        }

        // Print settings: ECMA-376 sequence: pageMargins -> pageSetup -> headerFooter
        fwrite($fw, '<pageMargins left="0.3" right="0.3" top="0.4" bottom="0.4" header="0.2" footer="0.2"/>' . "\n");
        fwrite($fw, '<pageSetup paperSize="' . $this->paperSize . '" orientation="' . $this->orientation . '" fitToWidth="1" fitToHeight="1"/>' . "\n");
        fwrite($fw, '<headerFooter><oddFooter>&amp;LPRO/LKCL-REV_1&amp;C&amp;KDC2626CONFIDENTIAL&amp;RPT PURA BARUTAMA UNIT OFFSET</oddFooter></headerFooter>' . "\n");

        fwrite($fw, '</worksheet>' . "\n");
        fclose($fw);

        $this->zip->addFile($finalSheet, 'xl/worksheets/sheet1.xml');
        $this->addStaticXmls();
        $this->zip->close();

        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Content-Length: ' . filesize($this->tempFile));
            readfile($this->tempFile);
        } else {
            copy($this->tempFile, $filename);
        }

        @unlink($this->sheetFile);
        @unlink($finalSheet);
        @unlink($this->tempFile);
    }

    private function addStaticXmls() {
        $this->zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $this->zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $this->zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $this->zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <fileSharing readOnlyRecommended="1"/>
  <sheets>
    <sheet name="Laporan LKH Sortir" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        $this->zip->addFromString('xl/styles.xml', $this->buildStylesXml());
    }

    private function buildStylesXml() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="3">
    <numFmt numFmtId="164" formatCode="#,##0"/>
    <numFmt numFmtId="165" formatCode="#,##0.00"/>
    <numFmt numFmtId="166" formatCode="0.0%"/>
  </numFmts>
  <fonts count="10">
    <font><sz val="10"/><name val="Figtree"/><color theme="1"/></font>
    <font><b/><sz val="10"/><name val="Figtree"/><color rgb="FFFFFFFF"/></font>
    <font><b/><sz val="11"/><name val="Figtree"/><color rgb="FFFFFFFF"/></font>
    <font><b/><sz val="8.5"/><name val="Figtree"/><color rgb="FFFFFFFF"/></font>
    <font><b/><sz val="8.5"/><name val="Figtree"/><color rgb="FF78350F"/></font>
    <font><b/><sz val="10"/><name val="Figtree"/><color rgb="FF000000"/></font>
    <font><b/><sz val="14"/><name val="Figtree"/><color rgb="FF1E3A8A"/></font>
    <font><i/><sz val="9"/><name val="Figtree"/><color rgb="FF64748B"/></font>
    <font><b/><sz val="10"/><name val="Figtree"/><color rgb="FF16A34A"/></font>
    <font><b/><sz val="10"/><name val="Figtree"/><color rgb="FFB91C1C"/></font>
  </fonts>
  <fills count="24">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFB45309"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDC2626"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF5B21B6"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0D9488"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF6D28D9"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF15803D"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF334155"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFFFBEB"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE0E7FF"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF0FDFA"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF2F2"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFCCFBF1"/></patternFill></fill>
  </fills>
  <borders count="3">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
    </border>
    <border>
      <left style="thin"><color rgb="FF94A3B8"/></left>
      <right style="thin"><color rgb="FF94A3B8"/></right>
      <top style="thin"><color rgb="FF000000"/></top>
      <bottom style="double"><color rgb="FF000000"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="46">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="7" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="8" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="9" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="10" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="1" fillId="11" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="12" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="5" fillId="13" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="5" fillId="15" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="16" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="17" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="18" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="19" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="5" fillId="14" borderId="2" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="164" fontId="5" fillId="14" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="165" fontId="5" fillId="14" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="166" fontId="5" fillId="14" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="0" fillId="20" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="4" fillId="21" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="165" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="166" fontId="8" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="9" fillId="22" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="5" fillId="23" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="164" fontId="4" fillId="21" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="166" fontId="8" fillId="14" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="14" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="12" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="9" fillId="22" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1" applyBorder="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyAlignment="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>';
    }
}
