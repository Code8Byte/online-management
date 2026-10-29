import os
from datetime import datetime
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether
)
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# Register Figtree fonts
FONT_DIR = os.path.join(os.path.dirname(__file__), "fonts")
pdfmetrics.registerFont(TTFont("Figtree", os.path.join(FONT_DIR, "Figtree-Regular.ttf")))
pdfmetrics.registerFont(TTFont("Figtree-Bold", os.path.join(FONT_DIR, "Figtree-Bold.ttf")))
pdfmetrics.registerFont(TTFont("Figtree-SemiBold", os.path.join(FONT_DIR, "Figtree-SemiBold.ttf")))

def create_cc_pdf(filename="LAPORAN_CC_DEPARTEMEN_LIPAT.pdf", export_time_str=None):
    if not export_time_str:
        now = datetime.now()
        months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
                  "Juli", "Agustus", "September", "Oktober", "November", "Desember"]
        export_time_str = f"Dicetak: {now.day} {months[now.month-1]} {now.year}, {now.strftime('%H:%M')} WIB"

    # A4 Landscape: 841.89 x 595.28 points
    page_width, page_height = landscape(A4)
    margin = 22 # ~7.7mm margin

    doc = SimpleDocTemplate(
        filename,
        pagesize=landscape(A4),
        leftMargin=margin,
        rightMargin=margin,
        topMargin=margin,
        bottomMargin=margin
    )

    printable_width = page_width - (2 * margin) # 797.89 pt

    # Color Palette matching corporate standard
    C_NAVY_DARK = colors.HexColor("#1E3A8A")
    C_ORANGE_DARK = colors.HexColor("#C65911")
    C_ORANGE_HEAD = colors.HexColor("#ED7D31")
    C_ORANGE_SUB = colors.HexColor("#FCE4D6")
    C_RED_DARK = colors.HexColor("#C00000")
    C_RED_HEAD = colors.HexColor("#DC2626")
    C_GRAY_TOTAL = colors.HexColor("#F1F5F9")
    C_BORDER = colors.HexColor("#CBD5E1")
    C_TEXT = colors.HexColor("#0F172A")
    C_SUBTEXT = colors.HexColor("#475569")
    C_CONFIDENTIAL = colors.HexColor("#DC2626")

    # Typography Styles
    style_title = ParagraphStyle(
        "ReportTitle",
        fontName="Figtree-Bold",
        fontSize=13,
        leading=16,
        textColor=C_NAVY_DARK
    )
    style_subtitle = ParagraphStyle(
        "ReportSubtitle",
        fontName="Figtree-SemiBold",
        fontSize=8.5,
        leading=11,
        textColor=C_SUBTEXT
    )
    style_badge = ParagraphStyle(
        "ExportBadge",
        fontName="Figtree-SemiBold",
        fontSize=8,
        leading=10,
        alignment=2, # Right
        textColor=C_SUBTEXT
    )

    # Table cell paragraph styles
    th_white = ParagraphStyle("THWhite", fontName="Figtree-Bold", fontSize=7.5, leading=9, alignment=1, textColor=colors.white)
    th_orange_sub = ParagraphStyle("THOrangeSub", fontName="Figtree", fontSize=6.5, leading=7.5, alignment=1, textColor=colors.HexColor("#7C2D12"))
    
    td_center = ParagraphStyle("TDCenter", fontName="Figtree", fontSize=7.5, leading=9, alignment=1, textColor=C_TEXT)
    td_left = ParagraphStyle("TDLeft", fontName="Figtree-SemiBold", fontSize=7.5, leading=9, alignment=0, textColor=C_TEXT)
    td_right = ParagraphStyle("TDRight", fontName="Figtree", fontSize=7.5, leading=9, alignment=2, textColor=C_TEXT)
    td_right_bold = ParagraphStyle("TDRightBold", fontName="Figtree-Bold", fontSize=7.5, leading=9, alignment=2, textColor=C_TEXT)
    
    tot_label = ParagraphStyle("TotLabel", fontName="Figtree-Bold", fontSize=8, leading=10, alignment=1, textColor=C_TEXT)
    tot_right = ParagraphStyle("TotRight", fontName="Figtree-Bold", fontSize=8, leading=10, alignment=2, textColor=C_TEXT)

    story = []

    # 1. HEADER SECTION (Title, Subtitle, and Export Timestamp)
    header_data = [
        [
            Paragraph("<b>LAPORAN KERJA HARIAN - DEPARTEMEN LIPAT</b>", style_title),
            Paragraph(f"<b>{export_time_str}</b>", style_badge)
        ],
        [
            Paragraph("Tanggal: 19 Oktober 2026 &nbsp;&nbsp;|&nbsp;&nbsp; Shift: Shift 3 &nbsp;&nbsp;|&nbsp;&nbsp; Total Mesin: 2 Unit", style_subtitle),
            ""
        ]
    ]
    t_header = Table(header_data, colWidths=[printable_width * 0.70, printable_width * 0.30])
    t_header.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("SPAN", (1, 0), (1, 1)),
        ("ALIGN", (1, 0), (1, 0), "RIGHT"),
        ("LINEBELOW", (0, 1), (-1, 1), 1.2, C_TEXT),
        ("BOTTOMPADDING", (0, 1), (-1, 1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
    ]))
    story.append(t_header)
    story.append(Spacer(1, 6))

    # =========================================================================
    # 2. TABEL 1: TABEL UTAMA (Ringkasan Jam Kerja & Mesin)
    # 7 Columns: No, Nama Mesin, No PP, Jam Kerja, Total Down Time, Jam Jalan, Persiapan
    # =========================================================================
    w_t1 = [
        printable_width * 0.04, # No
        printable_width * 0.30, # Nama Mesin
        printable_width * 0.16, # No PP
        printable_width * 0.12, # Jam Kerja
        printable_width * 0.14, # Total Down Time
        printable_width * 0.12, # Jam Jalan
        printable_width * 0.12  # Persiapan
    ]

    t1_data = [
        [
            Paragraph("<b>No</b>", th_white),
            Paragraph("<b>Nama Mesin</b>", th_white),
            Paragraph("<b>No PP</b>", th_white),
            Paragraph("<b>Jam Kerja</b>", th_white),
            Paragraph("<b>Total Down Time</b>", th_white),
            Paragraph("<b>Jam Jalan</b>", th_white),
            Paragraph("<b>Persiapan</b>", th_white)
        ],
        [
            Paragraph("1", td_center),
            Paragraph("FOCUSIGHT", td_left),
            Paragraph("7843, 7844", td_center),
            Paragraph("8.00", td_right),
            Paragraph("150", td_right_bold),
            Paragraph("4.50", td_right_bold),
            Paragraph("30", td_right)
        ],
        [
            Paragraph("2", td_center),
            Paragraph("LUSTER CARTONHERO-650C", td_left),
            Paragraph("6059", td_center),
            Paragraph("8.00", td_right),
            Paragraph("120", td_right_bold),
            Paragraph("5.50", td_right_bold),
            Paragraph("30", td_right)
        ],
        [
            Paragraph("TOTAL", tot_label),
            "",
            "",
            Paragraph("16.00", tot_right),
            Paragraph("270", tot_right),
            Paragraph("10.00", tot_right),
            Paragraph("60", tot_right)
        ]
    ]

    t1 = Table(t1_data, colWidths=w_t1)
    t1.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), C_NAVY_DARK),
        ("GRID", (0, 0), (-1, -1), 0.5, C_BORDER),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
        ("SPAN", (0, 3), (2, 3)), # Total label merge
        ("BACKGROUND", (0, 3), (-1, 3), C_GRAY_TOTAL),
        ("LINEABOVE", (0, 3), (-1, 3), 1, C_TEXT),
        ("LINEBELOW", (0, 3), (-1, 3), 1.5, C_TEXT),
    ]))
    story.append(t1)
    story.append(Spacer(1, 7))

    # =========================================================================
    # 3. TABEL 2: DETAIL DOWNTIME
    # Title bar + 14 Columns:
    # No, Nama Mesin, No PP, ACCQC, BDEV, BEND, DEFECT, LAIN, PERBK, SERVC, SPC, STEL, T.BAHAN, Total DT
    # =========================================================================
    w_base = [printable_width * 0.04, printable_width * 0.22, printable_width * 0.12]
    dt_codes = ["ACCQC", "BDEV", "BEND", "DEFECT", "LAIN", "PERBK", "SERVC", "SPC", "STEL", "T.BAHAN"]
    w_dt_col = (printable_width * 0.52) / len(dt_codes)
    w_t2 = w_base + [w_dt_col] * len(dt_codes) + [printable_width * 0.10]

    dt_subs = [
        "Acc QC Model", "Ban Delivery", "Mesin Bendel", "Perbaikan Defect",
        "Lain - Lain", "Predictive Repair", "Preventive Service", "Kosongkan WIP",
        "Menyetel Mesin", "Tunggu Bahan"
    ]

    t2_data = [
        # Title Bar
        [Paragraph("<b>DETAIL DOWNTIME</b>", th_white)] + [""] * 13,
        # Header Row 1 (Kode)
        [
            Paragraph("<b>No</b>", th_white),
            Paragraph("<b>Nama Mesin</b>", th_white),
            Paragraph("<b>No PP</b>", th_white),
        ] + [Paragraph(f"<b>{k}</b>", th_white) for k in dt_codes] + [Paragraph("<b>Total DT</b>", th_white)],
        # Header Row 2 (Keterangan Arti)
        [
            "", "", ""
        ] + [Paragraph(s, th_orange_sub) for s in dt_subs] + [""],
        # Row 1: FOCUSIGHT (DEFECT: 30, STEL: 60, T.BAHAN: 60, Total: 150)
        [
            Paragraph("1", td_center),
            Paragraph("FOCUSIGHT", td_left),
            Paragraph("7843, 7844", td_center),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("30", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("60", td_right_bold),
            Paragraph("60", td_right_bold),
            Paragraph("150", td_right_bold)
        ],
        # Row 2: LUSTER CARTONHERO-650C (BDEV: 30, BEND: 30, DEFECT: 30, T.BAHAN: 30, Total: 120)
        [
            Paragraph("2", td_center),
            Paragraph("LUSTER CARTONHERO-650C", td_left),
            Paragraph("6059", td_center),
            Paragraph("0", td_right),
            Paragraph("30", td_right_bold),
            Paragraph("30", td_right_bold),
            Paragraph("30", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("30", td_right_bold),
            Paragraph("120", td_right_bold)
        ],
        # Row 3: TOTAL (BDEV: 30, BEND: 30, DEFECT: 60, STEL: 60, T.BAHAN: 90, Total: 270)
        [
            Paragraph("TOTAL", tot_label),
            "",
            "",
            Paragraph("0", tot_right),
            Paragraph("30", tot_right),
            Paragraph("30", tot_right),
            Paragraph("60", tot_right),
            Paragraph("0", tot_right),
            Paragraph("0", tot_right),
            Paragraph("0", tot_right),
            Paragraph("0", tot_right),
            Paragraph("60", tot_right),
            Paragraph("90", tot_right),
            Paragraph("270", tot_right)
        ]
    ]

    t2 = Table(t2_data, colWidths=w_t2)
    t2.setStyle(TableStyle([
        # Title bar
        ("SPAN", (0, 0), (-1, 0)),
        ("BACKGROUND", (0, 0), (-1, 0), C_ORANGE_DARK),
        # Column headers
        ("BACKGROUND", (0, 1), (-1, 1), C_ORANGE_HEAD),
        ("BACKGROUND", (3, 2), (12, 2), C_ORANGE_SUB),
        # Vertical merges for No, Mesin, PP, Total DT across row 1 & 2
        ("SPAN", (0, 1), (0, 2)),
        ("SPAN", (1, 1), (1, 2)),
        ("SPAN", (2, 1), (2, 2)),
        ("SPAN", (13, 1), (13, 2)),
        ("GRID", (0, 0), (-1, -1), 0.5, C_BORDER),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 2.5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2.5),
        # Total Row
        ("SPAN", (0, 5), (2, 5)),
        ("BACKGROUND", (0, 5), (-1, 5), C_GRAY_TOTAL),
        ("LINEABOVE", (0, 5), (-1, 5), 1, C_TEXT),
        ("LINEBELOW", (0, 5), (-1, 5), 1.5, C_TEXT),
    ]))
    story.append(t2)
    story.append(Spacer(1, 7))

    # =========================================================================
    # 4. TABEL 3: DETAIL WASTE
    # Title bar + 16 Columns:
    # No, Nama Mesin, No PP, Rusakan Stell, Reject WIP, Total Waste,
    # PREMIER, SILVER, WARNA, UV, METASOL, BARET, BLOBOR, NODA, EMBOSS, LAINNYA
    # =========================================================================
    waste_defects = ["PREMIER", "SILVER", "WARNA", "UV", "METASOL", "BARET", "BLOBOR", "NODA", "EMBOSS", "LAINNYA"]
    w_w_base = [printable_width * 0.04, printable_width * 0.20, printable_width * 0.10]
    w_w_summary = [printable_width * 0.075, printable_width * 0.075, printable_width * 0.08]
    w_w_defect = (printable_width - sum(w_w_base) - sum(w_w_summary)) / len(waste_defects)
    w_t3 = w_w_base + w_w_summary + [w_w_defect] * len(waste_defects)

    t3_data = [
        # Title bar
        [Paragraph("<b>DETAIL WASTE</b>", th_white)] + [""] * 15,
        # Header row
        [
            Paragraph("<b>No</b>", th_white),
            Paragraph("<b>Nama Mesin</b>", th_white),
            Paragraph("<b>No PP</b>", th_white),
            Paragraph("<b>Rusakan Stell</b>", th_white),
            Paragraph("<b>Reject WIP</b>", th_white),
            Paragraph("<b>Total Waste</b>", th_white)
        ] + [Paragraph(f"<b>{d}</b>", th_white) for d in waste_defects],
        # Row 1: FOCUSIGHT (Stell: 750, WIP: 20250, Tot: 21000 | WARNA: 2750, BLOBOR: 4000, NODA: 2000, EMBOSS: 3500, LAINNYA: 8000)
        [
            Paragraph("1", td_center),
            Paragraph("FOCUSIGHT", td_left),
            Paragraph("7843, 7844", td_center),
            Paragraph("750", td_right),
            Paragraph("20,250", td_right),
            Paragraph("21,000", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("2,750", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("4,000", td_right_bold),
            Paragraph("2,000", td_right_bold),
            Paragraph("3,500", td_right_bold),
            Paragraph("8,000", td_right_bold)
        ],
        # Row 2: LUSTER CARTONHERO-650C (Stell: 850, WIP: 37564, Tot: 38414 | UV: 2564, BLOBOR: 12000, NODA: 15000, EMBOSS: 3000, LAINNYA: 5000)
        [
            Paragraph("2", td_center),
            Paragraph("LUSTER CARTONHERO-650C", td_left),
            Paragraph("6059", td_center),
            Paragraph("850", td_right),
            Paragraph("37,564", td_right),
            Paragraph("38,414", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("2,564", td_right_bold),
            Paragraph("0", td_right),
            Paragraph("0", td_right),
            Paragraph("12,000", td_right_bold),
            Paragraph("15,000", td_right_bold),
            Paragraph("3,000", td_right_bold),
            Paragraph("5,000", td_right_bold)
        ],
        # Row 3: TOTAL (Stell: 1600, WIP: 57814, Tot: 59414 | WARNA: 2750, UV: 2564, BLOBOR: 16000, NODA: 17000, EMBOSS: 6500, LAINNYA: 13000)
        [
            Paragraph("TOTAL", tot_label),
            "",
            "",
            Paragraph("1,600", tot_right),
            Paragraph("57,814", tot_right),
            Paragraph("59,414", tot_right),
            Paragraph("0", tot_right),
            Paragraph("0", tot_right),
            Paragraph("2,750", tot_right),
            Paragraph("2,564", tot_right),
            Paragraph("0", tot_right),
            Paragraph("0", tot_right),
            Paragraph("16,000", tot_right),
            Paragraph("17,000", tot_right),
            Paragraph("6,500", tot_right),
            Paragraph("13,000", tot_right)
        ]
    ]

    t3 = Table(t3_data, colWidths=w_t3)
    t3.setStyle(TableStyle([
        ("SPAN", (0, 0), (-1, 0)),
        ("BACKGROUND", (0, 0), (-1, 0), C_RED_DARK),
        ("BACKGROUND", (0, 1), (-1, 1), C_RED_HEAD),
        ("GRID", (0, 0), (-1, -1), 0.5, C_BORDER),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 2.5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2.5),
        ("SPAN", (0, 4), (2, 4)),
        ("BACKGROUND", (0, 4), (-1, 4), C_GRAY_TOTAL),
        ("LINEABOVE", (0, 4), (-1, 4), 1, C_TEXT),
        ("LINEBELOW", (0, 4), (-1, 4), 1.5, C_TEXT),
    ]))
    story.append(t3)
    story.append(Spacer(1, 10))

    # =========================================================================
    # 5. KOLOM TANDA TANGAN (PIC / Operator Sortir & Cost Control)
    # No parentheses anywhere! Clean line for Cost Control.
    # =========================================================================
    sig_data = [
        [
            Paragraph("<b>Dibuat Oleh,</b>", ParagraphStyle("SigHead", fontName="Figtree-Bold", fontSize=8.5, alignment=1, textColor=C_TEXT)),
            "",
            Paragraph("<b>Diketahui Oleh,</b>", ParagraphStyle("SigHead2", fontName="Figtree-Bold", fontSize=8.5, alignment=1, textColor=C_TEXT)),
            ""
        ],
        [
            Spacer(1, 26),
            "",
            Spacer(1, 26),
            ""
        ],
        [
            Paragraph("<u><b>RENO</b></u>", ParagraphStyle("SigName1", fontName="Figtree-Bold", fontSize=9, alignment=1, textColor=C_TEXT)),
            "",
            Paragraph("____________________________________", ParagraphStyle("SigLine2", fontName="Figtree", fontSize=8.5, alignment=1, textColor=C_TEXT)),
            ""
        ],
        [
            Paragraph("PIC / Operator Sortir", ParagraphStyle("SigRole1", fontName="Figtree", fontSize=8, alignment=1, textColor=C_SUBTEXT)),
            "",
            Paragraph("Cost Control", ParagraphStyle("SigRole2", fontName="Figtree", fontSize=8, alignment=1, textColor=C_SUBTEXT)),
            ""
        ]
    ]

    w_sig = [printable_width * 0.35, printable_width * 0.30, printable_width * 0.35, 0]
    t_sig = Table(sig_data, colWidths=[printable_width * 0.35, printable_width * 0.30, printable_width * 0.35])
    t_sig.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("TOPPADDING", (0, 0), (-1, -1), 1),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 1),
    ]))
    story.append(t_sig)
    story.append(Spacer(1, 10))

    # =========================================================================
    # 6. FOOTER SECTION
    # Left: PRO/LKCL-REV_1
    # Center: CONFIDENTIAL (Bold Red)
    # Right: PT PURA BARUTAMA UNIT OFFSET
    # =========================================================================
    footer_data = [
        [
            Paragraph("<b>PT PURA BARUTAMA UNIT OFFSET</b>", ParagraphStyle("FLeft", fontName="Figtree-Bold", fontSize=8, textColor=C_SUBTEXT, alignment=2)),
            Paragraph("<b>CONFIDENTIAL</b>", ParagraphStyle("FCenter", fontName="Figtree-Bold", fontSize=8.5, textColor=C_CONFIDENTIAL, alignment=1)),
            Paragraph("<b>PRO/LKCL-REV_1</b>", ParagraphStyle("FRight", fontName="Figtree-Bold", fontSize=8, textColor=C_SUBTEXT, alignment=0))
        ]
    ]
    t_footer = Table(footer_data, colWidths=[printable_width * 0.30, printable_width * 0.40, printable_width * 0.30])
    t_footer.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LINEABOVE", (0, 0), (-1, 0), 1, C_TEXT),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
    ]))
    story.append(t_footer)

    # Build document
    doc.build(story)
    print(f"PDF successfully created: {filename}")

if __name__ == "__main__":
    create_cc_pdf()
