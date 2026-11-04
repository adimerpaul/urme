import { Printd } from 'printd'

const CSS = `
  @page { size: letter portrait; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #171717; }
  .pagina {
    width: 216mm;
    height: 279mm;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .rotulo {
    width: 178mm;
    min-height: 73mm;
    padding: 0 10mm 8mm;
    border: 0.65mm solid #222;
    border-radius: 9mm;
  }
  /* El JPG (70 x 39,4 mm) trae ~6,6 mm de blanco sobre el dibujo: se ocultan 3 mm
     de ese blanco al mostrarlo, sin tocar la imagen ni salirse del recuadro. */
  .logo { display: block; width: 70mm; height: 36.4mm; object-fit: cover; object-position: center bottom; margin: 0 auto 1mm; }
  .titulo {
    margin: 0 0 3mm;
    color: #2d82b7;
    font-size: 13pt;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
    white-space: nowrap;
  }
  table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10.5pt; line-height: 1.35; }
  th { width: 46mm; padding: 0.45mm 2mm 0.45mm 0; color: #2d82b7; text-align: right; vertical-align: top; white-space: nowrap; }
  td { padding: 0.45mm 0; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
`

const FUENTE_MINIMA_PT = 7

/*
 * Cada línea va en una sola línea: si un texto no cabe en su espacio, se reduce
 * la letra solo de esa línea (de a 0,25 pt, mínimo 7 pt) midiendo el ancho real
 * en el documento que se imprime. Nunca se recorta. Si ni a 7 pt cabe, esa línea
 * vuelve a partirse para no salirse del recuadro.
 */
function ajustarLineas (doc) {
  const vista = doc.defaultView
  doc.querySelectorAll('.titulo, td').forEach(el => {
    let pt = parseFloat(vista.getComputedStyle(el).fontSize) * 72 / 96
    while (el.scrollWidth > el.clientWidth && pt > FUENTE_MINIMA_PT) {
      pt = Math.max(FUENTE_MINIMA_PT, pt - 0.25)
      el.style.fontSize = `${pt}pt`
    }
    if (el.scrollWidth > el.clientWidth) el.style.whiteSpace = 'normal'
  })
}

function esc (value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function fechaTranscripcion () {
  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'America/La_Paz',
  }).format(new Date())
}

export function imprimirRotuloSolicitudLaboratorio (solicitud, imgBase = '') {
  const logoUrl = `${String(imgBase).replace(/\/$/, '')}/images/logo-laboratorio-urme.jpg`
  const html = `
    <main class="pagina">
      <section class="rotulo">
        <img class="logo" src="${esc(logoUrl)}" alt="Laboratorio de Diagnóstico Clínico URME">
        <div class="titulo">Laboratorio de Diagnóstico Clínico URME</div>
        <table>
          <tr><th>Nombre:</th><td>${esc(solicitud.paciente?.nombre_completo || 'NO ASIGNADO')}</td></tr>
          <tr><th>Médico:</th><td>${esc(solicitud.doctor?.nombre || 'NO ASIGNADO')}</td></tr>
          <tr><th>Institución:</th><td>${esc(solicitud.paciente?.seguro?.nombre || 'PARTICULAR')}</td></tr>
          <tr><th>Fecha de Transcripción:</th><td>${esc(fechaTranscripcion())}</td></tr>
        </table>
      </section>
    </main>`

  const element = document.createElement('div')
  element.innerHTML = html
  new Printd().print(element, [CSS], [], ({ iframe, launchPrint }) => {
    ajustarLineas(iframe.contentDocument)
    launchPrint()
  })
}
