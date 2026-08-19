import { Printd } from 'printd'

const CSS = `
  @page { size: letter portrait; margin: 12mm; }
  body { margin: 0; font-family: Arial, sans-serif; color: #172033; font-size: 10px; }
  h1 { margin: 0; color: #0d47a1; font-size: 20px; } h2 { margin: 18px 0 5px; font-size: 13px; color: #0d47a1; }
  .head { border-bottom: 3px solid #0d47a1; padding-bottom: 8px; } .muted { color: #64748b; }
  .summary { display: table; width: 100%; margin-top: 10px; border-spacing: 5px; }
  .summary div { display: table-cell; border: 1px solid #90caf9; padding: 7px; text-align: center; }
  .summary b { display: block; color: #0d47a1; font-size: 14px; }
  table { width: 100%; border-collapse: collapse; } th { background: #e3f2fd; color: #0d47a1; text-align: left; }
  th, td { border-bottom: 1px solid #dbe4ee; padding: 5px; } .right { text-align: right; }
`

function esc (value) {
  return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}
function money (value) { return Number(value || 0).toFixed(2) }

export function imprimirSeguro (detalle) {
  const pacientes = detalle.pacientes || []
  const internaciones = detalle.internaciones || []
  const resumen = detalle.resumen || {}
  const html = `<div class="head"><h1>Detalle de seguro</h1>
    <div><b>${esc(detalle.seguro?.nombre)}</b> · NIT: ${esc(detalle.seguro?.nit || '—')}</div>
    <div class="muted">Generado: ${esc(new Date().toLocaleString('es-BO'))}</div></div>
    <div class="summary"><div><b>${resumen.cantidad_pacientes || 0}</b>Pacientes afiliados</div>
    <div><b>${resumen.cantidad_internaciones || 0}</b>Internaciones</div>
    <div><b>Bs ${money(resumen.total)}</b>Total cargos</div></div>
    <h2>Pacientes afiliados</h2><table><thead><tr><th>Paciente</th><th>CI</th><th>Teléfono</th></tr></thead><tbody>
    ${pacientes.map(p => `<tr><td>${esc(p.nombre_completo)}</td><td>${esc(p.ci || '—')}</td><td>${esc(p.telefono || '—')}</td></tr>`).join('') || '<tr><td colspan="3">Sin pacientes afiliados</td></tr>'}
    </tbody></table><h2>Internaciones</h2><table><thead><tr><th>Paciente</th><th>Ingreso</th><th>Alta</th><th>Sala</th><th class="right">Total Bs</th></tr></thead><tbody>
    ${internaciones.map(i => `<tr><td>${esc(i.paciente?.nombre_completo || '—')}</td><td>${esc(i.fecha_ingreso || '—')}</td><td>${esc(i.fecha_alta || 'PENDIENTE')}</td><td>${esc(i.sala || '—')}</td><td class="right">${money((i.items || []).reduce((sum, item) => sum + Number(item.total || 0), 0))}</td></tr>`).join('') || '<tr><td colspan="5">Sin internaciones</td></tr>'}
    </tbody></table>`
  const element = document.createElement('div')
  element.innerHTML = html
  new Printd().print(element, [CSS], [], ({ launchPrint }) => launchPrint())
}
