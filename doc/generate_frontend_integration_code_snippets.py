from __future__ import annotations

from datetime import datetime
from pathlib import Path
import zipfile

from generate_frontend_integration_guide import (
    build_content_types_xml,
    build_doc_rels_xml,
    build_root_rels_xml,
    build_styles_xml,
    code_block_xml,
    paragraph_xml,
    parse_markdown,
    table_xml,
)


BASE_DIR = Path(__file__).resolve().parent
INPUT_MD = BASE_DIR / "Frontend_Integration_Code_Snippets.md"
OUTPUT_DOCX = BASE_DIR / "Frontend_Integration_Code_Snippets.docx"


def build_document_xml(blocks) -> str:
    body_parts: list[str] = []
    body_parts.append(paragraph_xml("Frontend Integration Code Snippets", "Title"))
    body_parts.append(paragraph_xml(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", "Subtitle"))

    for block in blocks:
        if hasattr(block, "text") and hasattr(block, "style"):
            body_parts.append(paragraph_xml(block.text, block.style))
        elif hasattr(block, "lines"):
            body_parts.append(code_block_xml(block.lines))
        elif hasattr(block, "rows"):
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
        "xmlns:wne='http://schemas.microsoft.com/office/word/2006/wordml' "
        "xmlns:wps='http://schemas.microsoft.com/office/word/2010/wordprocessingShape' "
        "mc:Ignorable='w14 wp14'>"
        f"<w:body>{''.join(body_parts)}</w:body>"
        "</w:document>"
    )


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
