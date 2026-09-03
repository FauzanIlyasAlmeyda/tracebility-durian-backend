from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from typing import Iterable
from xml.sax.saxutils import escape
import zipfile


BASE_DIR = Path(__file__).resolve().parent
INPUT_MD = BASE_DIR / "Frontend_Integration_Guide.md"
OUTPUT_DOCX = BASE_DIR / "Frontend_Integration_Guide.docx"


@dataclass
class ParagraphBlock:
    text: str
    style: str = "Normal"


@dataclass
class CodeBlock:
    lines: list[str]


@dataclass
class TableBlock:
    rows: list[list[str]]


Block = ParagraphBlock | CodeBlock | TableBlock


def parse_markdown(lines: list[str]) -> list[Block]:
    blocks: list[Block] = []
    i = 0

    while i < len(lines):
        line = lines[i].rstrip("\n")
        stripped = line.strip()

        if not stripped:
            i += 1
            continue

        if stripped.startswith("```"):
            code_lines: list[str] = []
            i += 1
            while i < len(lines) and not lines[i].strip().startswith("```"):
                code_lines.append(lines[i].rstrip("\n"))
                i += 1
            blocks.append(CodeBlock(code_lines))
            i += 1
            continue

        if stripped.startswith("#"):
            level = len(stripped) - len(stripped.lstrip("#"))
            text = stripped[level:].strip()
            style = {
                1: "Title",
                2: "Heading1",
                3: "Heading2",
                4: "Heading3",
            }.get(level, "Heading3")
            blocks.append(ParagraphBlock(text=text, style=style))
            i += 1
            continue

        if stripped.startswith("|"):
            table_rows: list[list[str]] = []
            while i < len(lines):
                row = lines[i].strip()
                if not row.startswith("|"):
                    break
                cells = [cell.strip() for cell in row.strip("|").split("|")]
                if cells and all(set(cell) <= {"-", ":", " "} for cell in cells):
                    i += 1
                    continue
                table_rows.append(cells)
                i += 1
            if table_rows:
                blocks.append(TableBlock(table_rows))
            continue

        if stripped.startswith("- "):
            blocks.append(ParagraphBlock(text=stripped[2:].strip(), style="ListBullet"))
            i += 1
            continue

        blocks.append(ParagraphBlock(text=stripped, style="Normal"))
        i += 1

    return blocks


def xml_text(value: str) -> str:
    return escape(value).replace("\n", "&#10;")


def paragraph_xml(text: str, style: str = "Normal") -> str:
    if text == "":
        return "<w:p/>"

    run_props = ""
    if style == "CodeBlock":
        run_props = (
            "<w:rPr><w:rFonts w:ascii='Courier New' w:hAnsi='Courier New'/>"
            "<w:sz w:val='18'/></w:rPr>"
        )

    paragraph_props = f"<w:pPr><w:pStyle w:val='{style}'/></w:pPr>" if style != "Normal" else ""

    return (
        "<w:p>"
        f"{paragraph_props}"
        "<w:r>"
        f"{run_props}"
        f"<w:t xml:space='preserve'>{xml_text(text)}</w:t>"
        "</w:r>"
        "</w:p>"
    )


def code_block_xml(lines: Iterable[str]) -> str:
    paragraphs = []
    for line in lines:
        paragraphs.append(
            "<w:p><w:pPr><w:pStyle w:val='CodeBlock'/></w:pPr><w:r><w:t xml:space='preserve'>"
            f"{xml_text(line)}"
            "</w:t></w:r></w:p>"
        )
    return "".join(paragraphs)


def table_xml(rows: list[list[str]]) -> str:
    if not rows:
        return ""

    grid_cols = "".join("<w:gridCol w:w='4500'/>" for _ in rows[0])
    tr_xml = []
    for row_index, row in enumerate(rows):
        tc_xml = []
        for cell in row:
            cell_style = (
                "<w:shd w:val='clear' w:fill='EDEDED'/>"
                if row_index == 0
                else ""
            )
            tc_xml.append(
                "<w:tc>"
                "<w:tcPr>"
                "<w:tcBorders>"
                "<w:top w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
                "<w:left w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
                "<w:bottom w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
                "<w:right w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
                "</w:tcBorders>"
                f"{cell_style}"
                "</w:tcPr>"
                "<w:p><w:r><w:t xml:space='preserve'>"
                f"{xml_text(cell)}"
                "</w:t></w:r></w:p>"
                "</w:tc>"
            )
        tr_xml.append(f"<w:tr>{''.join(tc_xml)}</w:tr>")

    return (
        "<w:tbl>"
        "<w:tblPr>"
        "<w:tblW w:w='0' w:type='auto'/>"
        "<w:tblBorders>"
        "<w:top w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "<w:left w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "<w:bottom w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "<w:right w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "<w:insideH w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "<w:insideV w:val='single' w:sz='4' w:space='0' w:color='D0D0D0'/>"
        "</w:tblBorders>"
        "</w:tblPr>"
        f"<w:tblGrid>{grid_cols}</w:tblGrid>"
        f"{''.join(tr_xml)}"
        "</w:tbl>"
    )


def build_document_xml(blocks: list[Block]) -> str:
    body_parts: list[str] = []
    body_parts.append(paragraph_xml("DurianTrace Frontend Integration Guide", "Title"))
    body_parts.append(paragraph_xml(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", "Subtitle"))

    for block in blocks:
        if isinstance(block, ParagraphBlock):
            body_parts.append(paragraph_xml(block.text, block.style))
        elif isinstance(block, CodeBlock):
            body_parts.append(code_block_xml(block.lines))
        elif isinstance(block, TableBlock):
            body_parts.append(table_xml(block.rows))

    body_parts.append(
        "<w:sectPr>"
        "<w:pgSz w:w='11906' w:h='16838'/>"
        "<w:pgMar w:top='1440' w:right='1440' w:bottom='1440' w:left='1440' w:header='708' w:footer='708' w:gutter='0'/>"
        "</w:sectPr>"
    )

    return (
        "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>"
        "<w:document "
        "xmlns:wpc='http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas' "
        "xmlns:mo='http://schemas.microsoft.com/office/mac/office/2008/main' "
        "xmlns:mc='http://schemas.openxmlformats.org/markup-compatibility/2006' "
        "xmlns:o='urn:schemas-microsoft-com:office:office' "
        "xmlns:r='http://schemas.openxmlformats.org/officeDocument/2006/relationships' "
        "xmlns:m='http://schemas.openxmlformats.org/officeDocument/2006/math' "
        "xmlns:v='urn:schemas-microsoft-com:vml' "
        "xmlns:wp14='http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing' "
        "xmlns:wp='http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing' "
        "xmlns:w10='urn:schemas-microsoft-com:office:word' "
        "xmlns:w='http://schemas.openxmlformats.org/wordprocessingml/2006/main' "
        "xmlns:w14='http://schemas.microsoft.com/office/word/2010/wordml' "
        "xmlns:wpg='http://schemas.microsoft.com/office/word/2010/wordprocessingGroup' "
        "xmlns:wpi='http://schemas.microsoft.com/office/word/2010/wordprocessingInk' "
        "xmlns:wne='http://schemas.openxmlformats.org/wordprocessingml/2006/main' "
        "xmlns:wps='http://schemas.microsoft.com/office/word/2010/wordprocessingShape' "
        "mc:Ignorable='w14 wp14'>"
        f"<w:body>{''.join(body_parts)}</w:body>"
        "</w:document>"
    )


def build_styles_xml() -> str:
    return """<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<w:styles xmlns:w='http://schemas.openxmlformats.org/wordprocessingml/2006/main'>
  <w:style w:type='paragraph' w:default='1' w:styleId='Normal'>
    <w:name w:val='Normal'/>
    <w:qFormat/>
    <w:rPr>
      <w:rFonts w:ascii='Aptos' w:hAnsi='Aptos'/>
      <w:sz w:val='22'/>
    </w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Title'>
    <w:name w:val='Title'/>
    <w:basedOn w:val='Normal'/>
    <w:qFormat/>
    <w:rPr><w:b/><w:sz w:val='36'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Subtitle'>
    <w:name w:val='Subtitle'/>
    <w:basedOn w:val='Normal'/>
    <w:rPr><w:i/><w:sz w:val='20'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading1'>
    <w:name w:val='heading 1'/>
    <w:basedOn w:val='Normal'/>
    <w:rPr><w:b/><w:sz w:val='28'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading2'>
    <w:name w:val='heading 2'/>
    <w:basedOn w:val='Normal'/>
    <w:rPr><w:b/><w:sz w:val='24'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='Heading3'>
    <w:name w:val='heading 3'/>
    <w:basedOn w:val='Normal'/>
    <w:rPr><w:b/><w:sz w:val='22'/></w:rPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='ListBullet'>
    <w:name w:val='List Bullet'/>
    <w:basedOn w:val='Normal'/>
    <w:pPr><w:ind w:left='360'/></w:pPr>
  </w:style>
  <w:style w:type='paragraph' w:styleId='CodeBlock'>
    <w:name w:val='CodeBlock'/>
    <w:basedOn w:val='Normal'/>
    <w:pPr><w:ind w:left='360'/></w:pPr>
    <w:rPr><w:rFonts w:ascii='Courier New' w:hAnsi='Courier New'/><w:sz w:val='18'/></w:rPr>
  </w:style>
</w:styles>
"""


def build_content_types_xml() -> str:
    return """<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Types xmlns='http://schemas.openxmlformats.org/package/2006/content-types'>
  <Default Extension='rels' ContentType='application/vnd.openxmlformats-package.relationships+xml'/>
  <Default Extension='xml' ContentType='application/xml'/>
  <Override PartName='/word/document.xml' ContentType='application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'/>
  <Override PartName='/word/styles.xml' ContentType='application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml'/>
</Types>
"""


def build_root_rels_xml() -> str:
    return """<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Relationships xmlns='http://schemas.openxmlformats.org/package/2006/relationships'>
  <Relationship Id='rId1' Type='http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument' Target='word/document.xml'/>
</Relationships>
"""


def build_doc_rels_xml() -> str:
    return """<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<Relationships xmlns='http://schemas.openxmlformats.org/package/2006/relationships'/>
"""


def main() -> None:
    blocks = parse_markdown(INPUT_MD.read_text(encoding="utf-8").splitlines())

    with zipfile.ZipFile(OUTPUT_DOCX, "w", compression=zipfile.ZIP_DEFLATED) as docx:
        docx.writestr("[Content_Types].xml", build_content_types_xml())
        docx.writestr("_rels/.rels", build_root_rels_xml())
        docx.writestr("word/document.xml", build_document_xml(blocks))
        docx.writestr("word/styles.xml", build_styles_xml())
        docx.writestr("word/_rels/document.xml.rels", build_doc_rels_xml())

    print(f"Created {OUTPUT_DOCX}")


if __name__ == "__main__":
    main()
