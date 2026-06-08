from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    FrameBreak,
    KeepTogether,
    NextPageTemplate,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "docs" / "politicas"
PDF_PATH = OUT / "politica-de-seguridad-vendly.pdf"

BLACK = colors.HexColor("#111111")
INK = colors.HexColor("#1D2433")
MUTED = colors.HexColor("#667085")
ORANGE = colors.HexColor("#FF6A00")
SOFT_ORANGE = colors.HexColor("#FFF3EA")
SOFT_GRAY = colors.HexColor("#F4F6F8")
BORDER = colors.HexColor("#E4E7EC")


class SecurityPolicyDoc(BaseDocTemplate):
    def __init__(self, filename):
        super().__init__(
            filename,
            pagesize=letter,
            leftMargin=0.78 * inch,
            rightMargin=0.78 * inch,
            topMargin=0.78 * inch,
            bottomMargin=0.7 * inch,
            title="Politica de Seguridad - VendlySuite",
            author="VendlySuite",
        )
        body = Frame(
            self.leftMargin,
            self.bottomMargin,
            self.width,
            self.height,
            id="body",
            showBoundary=0,
        )
        cover = Frame(
            self.leftMargin,
            self.bottomMargin,
            self.width,
            self.height,
            id="cover",
            showBoundary=0,
        )
        self.addPageTemplates(
            [
                PageTemplate(id="Cover", frames=[cover], onPage=self.cover_page),
                PageTemplate(id="Body", frames=[body], onPage=self.body_page),
            ]
        )

    def cover_page(self, canvas, doc):
        canvas.saveState()
        canvas.setFillColor(BLACK)
        canvas.rect(0, 0, letter[0], letter[1], fill=1, stroke=0)
        canvas.setFillColor(ORANGE)
        canvas.circle(0.78 * inch, letter[1] - 0.82 * inch, 0.13 * inch, fill=1, stroke=0)
        canvas.setFillColor(colors.white)
        canvas.setFont("Helvetica-Bold", 10)
        canvas.drawString(1.02 * inch, letter[1] - 0.9 * inch, "vendly")
        canvas.setFillColor(colors.HexColor("#262626"))
        canvas.roundRect(0.78 * inch, 0.82 * inch, letter[0] - 1.56 * inch, 1.05 * inch, 14, fill=1, stroke=0)
        canvas.setFillColor(ORANGE)
        canvas.setFont("Helvetica-Bold", 8)
        canvas.drawString(1.05 * inch, 1.48 * inch, "DOCUMENTO DE REFERENCIA")
        canvas.setFillColor(colors.white)
        canvas.setFont("Helvetica", 9)
        canvas.drawString(1.05 * inch, 1.22 * inch, "Completar datos legales antes de publicar en el sitio web.")
        canvas.restoreState()

    def body_page(self, canvas, doc):
        canvas.saveState()
        canvas.setStrokeColor(BORDER)
        canvas.line(0.78 * inch, letter[1] - 0.55 * inch, letter[0] - 0.78 * inch, letter[1] - 0.55 * inch)
        canvas.setFillColor(MUTED)
        canvas.setFont("Helvetica", 8)
        canvas.drawString(0.78 * inch, letter[1] - 0.42 * inch, "VendlySuite | Politica de Seguridad")
        canvas.drawRightString(letter[0] - 0.78 * inch, 0.42 * inch, f"Pagina {doc.page}")
        canvas.restoreState()


def styles():
    base = getSampleStyleSheet()
    base.add(
        ParagraphStyle(
            "CoverKicker",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=10,
            textColor=ORANGE,
            alignment=TA_CENTER,
            spaceAfter=18,
            leading=13,
        )
    )
    base.add(
        ParagraphStyle(
            "CoverTitle",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=34,
            textColor=colors.white,
            alignment=TA_CENTER,
            leading=39,
            spaceAfter=12,
        )
    )
    base.add(
        ParagraphStyle(
            "CoverSubtitle",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=12.5,
            textColor=colors.HexColor("#D0D5DD"),
            alignment=TA_CENTER,
            leading=18,
            spaceAfter=28,
        )
    )
    base.add(
        ParagraphStyle(
            "DocTitle",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=21,
            textColor=BLACK,
            leading=26,
            spaceAfter=10,
        )
    )
    base.add(
        ParagraphStyle(
            "H1",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=13.4,
            textColor=ORANGE,
            leading=16,
            spaceBefore=12,
            spaceAfter=6,
        )
    )
    base.add(
        ParagraphStyle(
            "Body",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=9.9,
            textColor=INK,
            leading=14.2,
            spaceAfter=6,
            alignment=TA_LEFT,
        )
    )
    base.add(
        ParagraphStyle(
            "Small",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8.7,
            textColor=MUTED,
            leading=12,
            spaceAfter=4,
        )
    )
    base.add(
        ParagraphStyle(
            "CardTitle",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=10,
            textColor=BLACK,
            leading=13,
            spaceAfter=3,
        )
    )
    return base


def p(text, style):
    return Paragraph(text, style)


def card(title, text, st, fill=SOFT_GRAY):
    table = Table(
        [[p(title, st["CardTitle"]), p(text, st["Small"])]],
        colWidths=[1.65 * inch, 4.95 * inch],
        hAlign="LEFT",
    )
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), fill),
                ("BOX", (0, 0), (-1, -1), 0.7, BORDER),
                ("INNERGRID", (0, 0), (-1, -1), 0.3, BORDER),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 9),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 9),
            ]
        )
    )
    return table


def section(title, paragraphs, st):
    items = [p(title, st["H1"])]
    items.extend(p(text, st["Body"]) for text in paragraphs)
    return KeepTogether(items)


def build_pdf():
    OUT.mkdir(parents=True, exist_ok=True)
    st = styles()
    doc = SecurityPolicyDoc(str(PDF_PATH))
    story = []

    story.append(Spacer(1, 1.35 * inch))
    story.append(p("VENDLYSUITE", st["CoverKicker"]))
    story.append(p("Politica de<br/>Seguridad", st["CoverTitle"]))
    story.append(
        p(
            "Proteccion de la informacion, cuentas, tiendas, clientes, WhatsApp, pagos e inteligencia artificial.",
            st["CoverSubtitle"],
        )
    )

    pillars = Table(
        [
            [
                p("<b>Confidencialidad</b><br/>Acceso solo para personas y sistemas autorizados.", st["Small"]),
                p("<b>Integridad</b><br/>Datos protegidos contra alteraciones no autorizadas.", st["Small"]),
                p("<b>Disponibilidad</b><br/>Continuidad razonable del servicio y recuperacion.", st["Small"]),
            ]
        ],
        colWidths=[2.05 * inch, 2.05 * inch, 2.05 * inch],
        hAlign="CENTER",
    )
    pillars.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), colors.HexColor("#1B1B1B")),
                ("BOX", (0, 0), (-1, -1), 0.7, colors.HexColor("#2F2F2F")),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#333333")),
                ("LEFTPADDING", (0, 0), (-1, -1), 10),
                ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                ("TOPPADDING", (0, 0), (-1, -1), 12),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 12),
            ]
        )
    )
    story.append(pillars)
    story.append(NextPageTemplate("Body"))
    story.append(PageBreak())

    story.append(p("Resumen ejecutivo", st["DocTitle"]))
    story.append(
        p(
            "Esta politica define los controles, responsabilidades y buenas practicas que VendlySuite adopta para proteger la informacion de usuarios, tiendas, clientes finales y servicios integrados.",
            st["Body"],
        )
    )
    story.append(
        card(
            "Nota legal",
            "Este documento es una base de trabajo. Antes de publicarlo debe completarse con razon social, NIT, ciudad, correos oficiales y revision juridica final.",
            st,
            SOFT_ORANGE,
        )
    )
    story.append(Spacer(1, 8))
    story.append(
        card(
            "Datos del documento",
            "Version 1.0 | Ultima actualizacion: [Dia] de [Mes] de [Ano] | Responsable: [Razon social / VendlySuite] | Contacto: [correo de seguridad]",
            st,
        )
    )

    sections = [
        ("1. Alcance", [
            "Aplica a usuarios que crean una cuenta en Vendly, propietarios o administradores de tiendas, clientes finales, visitantes del sitio web, personal autorizado, proveedores y terceros con acceso autorizado a informacion del sistema.",
            "Tambien aplica al sitio web, panel administrativo, tiendas publicadas, formularios, integraciones, sistemas de mensajeria, pagos, inteligencia artificial y demas funcionalidades.",
        ]),
        ("2. Principios de seguridad", [
            "Vendly protege la informacion bajo principios de confidencialidad, integridad, disponibilidad, minimizacion, responsabilidad y trazabilidad.",
            "Solo se deben solicitar y tratar los datos necesarios para prestar el servicio, operar la plataforma, prevenir abuso, cumplir obligaciones legales y mejorar la seguridad.",
        ]),
        ("3. Informacion que protegemos", [
            "Vendly puede tratar nombres, correos, telefonos, contrasenas, informacion de tiendas, productos, categorias, imagenes, precios, pedidos, direcciones de envio, ciudades, departamentos, metodos de entrega, configuraciones, reseñas, logs tecnicos y eventos de seguridad.",
            "La plataforma no debe solicitar contrasenas bancarias, codigos OTP financieros, claves privadas, datos sensibles innecesarios ni informacion que no sea requerida.",
        ]),
        ("4. Proteccion de cuentas y contrasenas", [
            "Los usuarios son responsables de mantener la confidencialidad de sus credenciales, cerrar sesion en dispositivos compartidos y reportar actividad sospechosa.",
            "Vendly podra bloquear, limitar o suspender cuentas cuando detecte actividad sospechosa, abuso, fraude o riesgos para otros usuarios.",
        ]),
        ("5. Control de acceso", [
            "Cada usuario debe acceder unicamente a la informacion y funcionalidades correspondientes a su rol, plan o tienda.",
            "Esto incluye separacion entre administrador general, propietario de tienda y cliente final; restricciones por plan; validaciones de permisos; proteccion de rutas administrativas; y registro de eventos relevantes.",
        ]),
        ("6. Seguridad en comunicaciones", [
            "Vendly debe operar mediante HTTPS/TLS en produccion.",
            "Las comunicaciones con pasarelas de pago, WhatsApp Business API, correo, almacenamiento, inteligencia artificial, verificacion antiabuso y servicios externos deben realizarse por canales seguros y credenciales protegidas.",
        ]),
        ("7. Pagos y metodos de pago", [
            "Vendly puede permitir metodos de pago como WhatsApp, pagos manuales, transferencias, pasarelas externas o proveedores como Mercado Pago, segun el plan y configuracion disponible.",
            "Cuando se usen pasarelas externas, la informacion de pago sera procesada por el proveedor correspondiente. Vendly no debe almacenar datos completos de tarjetas, codigos CVV ni credenciales bancarias.",
        ]),
        ("8. Seguridad en WhatsApp Business API", [
            "Cuando Vendly utilice WhatsApp Business API para verificacion, bienvenida, pedidos o notificaciones, se aplicaran controles como tokens protegidos, plantillas aprobadas, consentimiento cuando aplique y registro limitado de eventos.",
            "Los mensajes dependen de disponibilidad, reglas, aprobacion de plantillas y politicas de Meta.",
        ]),
        ("9. Inteligencia artificial", [
            "Vendly puede ofrecer IA para generar o mejorar nombres de productos, descripciones, caracteristicas, etiquetas, avisos promocionales, imagenes o portadas.",
            "El usuario debe evitar ingresar informacion sensible, confidencial o de terceros sin autorizacion. Los resultados generados por IA deben ser revisados antes de publicarse.",
        ]),
        ("10. Proteccion contra abuso y fraude", [
            "Vendly puede usar verificacion por WhatsApp, Cloudflare Turnstile, limites de intentos, bloqueo temporal, validacion de numeros, correos o dominios y registro de actividad tecnica.",
            "Vendly podra negar, suspender o limitar el acceso ante indicios razonables de abuso, manipulacion, fraude o afectacion a la seguridad.",
        ]),
        ("11. Almacenamiento y copias de seguridad", [
            "Vendly puede realizar respaldos tecnicos y registros operativos para mantener continuidad del servicio, prevenir perdida de informacion y facilitar recuperacion.",
            "La restauracion dependera de disponibilidad tecnica, alcance del incidente y condiciones del servicio contratado.",
        ]),
        ("12. Proveedores y servicios de terceros", [
            "Vendly puede apoyarse en proveedores de hosting, dominios, DNS, pasarelas de pago, WhatsApp Business API, correo, almacenamiento, analitica, IA, proteccion antiabuso, seguridad y monitoreo.",
            "Cada proveedor puede tener sus propios terminos, politicas, disponibilidad y responsabilidades.",
        ]),
        ("13. Reporte de vulnerabilidades", [
            "Cualquier persona que identifique una posible vulnerabilidad debe reportarla responsablemente al correo [correo de seguridad o soporte], incluyendo descripcion, pasos de reproduccion, modulo afectado e impacto estimado.",
            "No esta permitido explotar vulnerabilidades, acceder a informacion de terceros, modificar datos, interrumpir servicios, realizar ataques, ingenieria social, spam o extraccion masiva de datos.",
        ]),
        ("14. Gestion de incidentes de seguridad", [
            "Ante un incidente, Vendly podra investigar, contener accesos no autorizados, revocar tokens, forzar cambios de contrasena, desactivar funciones afectadas, restaurar servicios, notificar usuarios e informar autoridades cuando corresponda.",
            "La respuesta dependera de la naturaleza, alcance y severidad del incidente.",
        ]),
        ("15. Responsabilidades del usuario", [
            "El usuario se compromete a usar la plataforma legalmente, no acceder a cuentas de terceros, no cargar contenido malicioso o ilegal, no usar Vendly para phishing, spam, estafas o suplantacion y cumplir normas de proteccion de datos.",
            "Cada tienda es responsable de la informacion que publica, productos que vende, condiciones comerciales y tratamiento de datos de sus clientes finales.",
        ]),
        ("16. Tratamiento de datos personales", [
            "Vendly trata datos personales conforme a la normativa aplicable, incluyendo la Ley 1581 de 2012 en Colombia, cuando corresponda.",
            "Los titulares podran ejercer derechos de conocer, actualizar, rectificar, suprimir informacion y revocar autorizaciones, de acuerdo con la ley aplicable y la politica de tratamiento de datos personales.",
        ]),
        ("17. Retencion de informacion", [
            "Vendly conservara informacion durante el tiempo necesario para prestar el servicio, cumplir obligaciones legales, resolver disputas, prevenir fraude, mejorar seguridad y atender requerimientos administrativos, contables o judiciales.",
            "Cuando deje de ser necesaria, Vendly podra eliminarla, anonimizarla o conservarla bloqueada si existe obligacion legal o interes legitimo de seguridad.",
        ]),
        ("18. Cambios en esta politica", [
            "Vendly podra actualizar esta Politica de Seguridad para reflejar cambios legales, tecnicos, operativos o comerciales.",
            "Cuando los cambios sean relevantes, Vendly podra informar mediante pagina web, correo electronico, panel administrativo u otros canales disponibles.",
        ]),
        ("19. Contacto", [
            "Nombre comercial: VendlySuite. Correo de soporte: [correo]. Correo de seguridad: [correo]. WhatsApp de soporte: [numero]. Sitio web: [dominio]. Ciudad y pais: [ciudad], Colombia.",
        ]),
    ]

    for title, paragraphs in sections:
        story.append(section(title, paragraphs, st))

    story.append(p("Fuentes de referencia", st["H1"]))
    for source in [
        "Ley 1581 de 2012 - Regimen General de Proteccion de Datos Personales en Colombia.",
        "Superintendencia de Industria y Comercio - lineamientos de proteccion de datos personales.",
        "Documentacion y politicas aplicables de proveedores externos como Meta, pasarelas de pago, Cloudflare y servicios de inteligencia artificial.",
    ]:
        story.append(p(f"- {source}", st["Body"]))

    doc.build(story)
    print(PDF_PATH)


if __name__ == "__main__":
    build_pdf()
