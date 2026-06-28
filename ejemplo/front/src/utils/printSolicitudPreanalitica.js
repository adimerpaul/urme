// src/utils/printSolicitudPreanalitica.js
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import moment from 'moment'

function safe(v, fallback = '-') {
  if (v === null || v === undefined || v === '') return fallback
  return String(v)
}

function estadoCounts(rows = []) {
  const c = (st) => rows.filter(r => (r.estado || '').toUpperCase() === st).length
  return {
    total: rows.length,
    creadas: c('CREADO'),
    atendiendo: c('ATENDIENDO'),
    rechazadas: c('MUESTRA RECHAZADA'),
    enviadoAnalitica: c('ENVIADO_ANALITICA'),
    analizado: c('ANALIZADO'),
  }
}

function serviciosToText(row) {
  const arr = row?.servicios || []
  if (!Array.isArray(arr) || arr.length === 0) return '-'
  return arr.map(s => safe(s?.nombre)).join(', ')
}

function codigoToText(row) {
  const codigo = row?.codigo
  const nro = row?.nro_registro
  if (!codigo) return 'Sin código'
  return nro ? `${codigo} - ${nro}` : `${codigo}`
}

export function printSolicitudPreanalitica({
                                             solicitud,              // array rows (lo que tú tienes en this.rows)
                                             establecimientoNombre,  // string
                                             areaNombre,             // string
                                             fechaSeleccionada,      // YYYY-MM-DD (opcional)
                                           } = {}) {
  const rows = Array.isArray(solicitud) ? solicitud : []

  const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
  const pageW = doc.internal.pageSize.getWidth()
  const marginX = 10
  const nowStr = moment().format('DD/MM/YYYY HH:mm')

  // ===== HEADER =====
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(14)
  doc.text('REPORTE DE SOLICITUDES - PREANALÍTICA', marginX, 14)

  doc.setFont('helvetica', 'normal')
  doc.setFontSize(10)
  doc.text(`Establecimiento: ${safe(establecimientoNombre, '---')}`, marginX, 20)
  doc.text(`Área: ${safe(areaNombre, '---')}`, marginX, 25)
  doc.text(`Impreso: ${nowStr}`, marginX, 30)

  if (fechaSeleccionada) {
    doc.text(`Fecha filtro: ${moment(fechaSeleccionada).format('DD/MM/YYYY')}`, marginX, 35)
  }

  // Línea
  doc.setDrawColor(0)
  doc.setLineWidth(0.2)
  doc.line(marginX, 38, pageW - marginX, 38)

  // ===== RESUMEN =====
  const counts = estadoCounts(rows)
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(10)
  doc.text(`Total: ${counts.total}`, marginX, 44)
  doc.setFont('helvetica', 'normal')
  doc.text(`Creadas: ${counts.creadas}`, marginX + 35, 44)
  doc.text(`Atendiendo: ${counts.atendiendo}`, marginX + 75, 44)
  doc.text(`Rechazadas: ${counts.rechazadas}`, marginX + 120, 44)

  doc.text(
    `Enviado Analítica: ${counts.enviadoAnalitica}  |  Analizado: ${counts.analizado}`,
    marginX,
    49
  )

  // ===== TABLA =====
  const tableBody = rows.map((r) => {
    const fecha = r?.fecha_creacion
      ? moment(r.fecha_creacion).format('DD/MM/YYYY HH:mm')
      : (r?.fecha_solicitud ? moment(r.fecha_solicitud).format('DD/MM/YYYY') : '-')

    const paciente = safe(r?.paciente_nombre || r?.paciente?.nombre_completo)
    const ci = safe(r?.paciente_ci || r?.paciente?.ci)
    const estado = safe(r?.estado)
    const tipo = (r?.tipo_atencion === 'SI')
      ? 'SUS'
      : safe(r?.tipo_otro, 'EXT')

    const establecimiento = safe(r?.establecimiento_salud)
    const responsable = safe(r?.user_preanalitica?.name, 'No asignado')

    // servicios puede ser largo -> lo dejamos pero aut-table lo cortará por ancho
    const servicios = serviciosToText(r)

    return [
      fecha,
      estado,
      `${paciente}\nCI: ${ci}`,
      codigoToText(r),
      tipo,
      establecimiento,
      responsable,
      servicios
    ]
  })

  autoTable(doc, {
    startY: 55,
    head: [[
      'Fecha',
      'Estado',
      'Paciente',
      'Código',
      'Tipo',
      'Establecimiento',
      'Resp. Preanalítica',
      'Prestaciones'
    ]],
    body: tableBody,
    styles: {
      font: 'helvetica',
      fontSize: 8,
      cellPadding: 1.5,
      overflow: 'linebreak',
      valign: 'top',
    },
    headStyles: {
      fontStyle: 'bold',
    },
    columnStyles: {
      0: { cellWidth: 22 }, // Fecha
      1: { cellWidth: 18 }, // Estado
      2: { cellWidth: 32 }, // Paciente
      3: { cellWidth: 20 }, // Código
      4: { cellWidth: 12 }, // Tipo
      5: { cellWidth: 24 }, // Establecimiento
      6: { cellWidth: 22 }, // Responsable
      7: { cellWidth: 'auto' } // Prestaciones
    },
    didDrawPage: (data) => {
      // footer page number
      const pageCount = doc.internal.getNumberOfPages()
      const pageCurrent = doc.internal.getCurrentPageInfo().pageNumber
      doc.setFontSize(9)
      doc.setFont('helvetica', 'normal')
      doc.text(
        `Página ${pageCurrent} / ${pageCount}`,
        pageW - marginX,
        doc.internal.pageSize.getHeight() - 8,
        { align: 'right' }
      )
    }
  })

  // Nombre archivo (opcional)
  const fileName = `preanalitica_${moment().format('YYYYMMDD_HHmm')}.pdf`
  doc.save(fileName)
}
