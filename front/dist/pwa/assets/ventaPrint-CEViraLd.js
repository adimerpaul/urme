import{r as e}from"./dateTime-Cgturobw.js";import{t}from"./printd-CI3YqDZj.js";var n=t(),r={nombre:`CLÍNICA URME`,direccion:`Calle Cochabamba entre Soria Galvarro y 6 de Octubre`,celular:`70431083`,pie:[`"CLÍNICA URME" Al servicio de Oruro, Atención de Emergencias`,`las 24 horas los 365 días del año.`,`Atención en todas las Especialidades`]},i=`
  @page { size: 5.5in 8.5in; margin: 10mm; }
  body { margin: 0; }
  .proforma { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #000; line-height: 1.25; position: relative; }
  .titulo-doc { display: inline-block; border: 1.5px solid #000; padding: 2px 10px; font-weight: bold; font-size: 13px; }
  table { width: 100%; border-collapse: collapse; }
  td { vertical-align: top; }
  .head { margin-top: 8px; }
  .logo { width: 90px; }
  .bold { font-weight: bold; }
  .center { text-align: center; }
  .right { text-align: right; }
  .dashed { border: none; border-top: 1.5px dashed #000; margin: 6px 0; }
  .detalle-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14px; margin: 8px 0 6px; }
  .items th { text-align: left; border-bottom: 1.5px solid #000; padding: 2px 4px; }
  .items td { padding: 2px 4px; }
  .items th.precio, .items td.precio { border-left: 1.5px solid #000; }
  .totales td { font-weight: bold; padding: 2px 4px; }
  .totales .monto { border-top: 1.5px solid #000; }
  .son { text-align: center; font-weight: bold; margin-top: 6px; }
  .obs { margin-top: 6px; }
  .pie { text-align: center; font-size: 11px; margin-top: 16px; }
  .anulado { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-25deg);
             font-size: 52px; font-weight: bold; color: rgba(200, 0, 0, 0.35); letter-spacing: 6px; }
`;function a(e){return String(e??``).replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`)}function o(e){return Number(e||0).toFixed(2).replace(`.`,`,`)}function s(e){let t=Number(e||0);return Number.isInteger(t)?String(t):t.toFixed(2).replace(`.`,`,`)}function c(e){let t=Math.floor(Math.abs(e)),n=Math.round((Math.abs(e)-t)*100);return`${l(t).toUpperCase()} CON ${String(n).padStart(2,`0`)}/100 Bs.`}function l(e){if(e===0)return`cero`;let t=[``,`uno`,`dos`,`tres`,`cuatro`,`cinco`,`seis`,`siete`,`ocho`,`nueve`,`diez`,`once`,`doce`,`trece`,`catorce`,`quince`,`dieciséis`,`diecisiete`,`dieciocho`,`diecinueve`,`veinte`],n=[``,`veintiuno`,`veintidós`,`veintitrés`,`veinticuatro`,`veinticinco`,`veintiséis`,`veintisiete`,`veintiocho`,`veintinueve`],r=[``,``,``,`treinta`,`cuarenta`,`cincuenta`,`sesenta`,`setenta`,`ochenta`,`noventa`],i=[``,`ciento`,`doscientos`,`trescientos`,`cuatrocientos`,`quinientos`,`seiscientos`,`setecientos`,`ochocientos`,`novecientos`];if(e<=20)return t[e];if(e<30)return n[e-20];if(e<100){let n=Math.floor(e/10),i=e%10;return i===0?r[n]:`${r[n]} y ${t[i]}`}if(e===100)return`cien`;if(e<1e3){let t=Math.floor(e/100),n=e%100;return i[t]+(n>0?` `+l(n):``)}if(e<2e3){let t=e%1e3;return`mil`+(t>0?` `+l(t):``)}if(e<1e6){let t=Math.floor(e/1e3),n=e%1e3;return l(t)+` mil`+(n>0?` `+l(n):``)}if(e<2e6){let t=e%1e6;return`un millón`+(t>0?` `+l(t):``)}if(e<1e9){let t=Math.floor(e/1e6),n=e%1e6;return l(t)+` millones`+(n>0?` `+l(n):``)}return String(e)}function u(t){let n=window.location.origin+`/logo.png`,i=e(t.fecha_hora),l=t.tipo_movimiento===`EGRESO`,u=t.paciente?.nombre_completo||t.cliente||(l?`GASTO DE CAJA`:`SIN NOMBRE`),d=t.detalles||[],f=typeof t.doctor==`string`?t.doctor:t.doctor?.nombre,p=d.length?d.map(e=>`
        <tr>
          <td>${a((e.producto?.codigo?e.producto.codigo+`-`:``)+(e.nombre||e.producto?.nombre||`—`))}</td>
          <td class="right">${s(e.cantidad)}</td>
          <td class="right precio">${o(e.precio)}</td>
          <td class="right">${o(e.total)}</td>
        </tr>`).join(``):`<tr><td colspan="4" class="center">Sin detalles</td></tr>`;return`
    <div class="proforma">
      ${t.estado===`ANULADO`?`<div class="anulado">ANULADO</div>`:``}
      <div class="titulo-doc">${l?`COMPROBANTE DE GASTO`:`PROFORMA DE PAGO`}</div>

      <table class="head">
        <tr>
          <td style="width:30%" class="center">
            <img class="logo" src="${n}" alt="Logo" onerror="this.style.display='none'">
          </td>
          <td style="width:70%">
            <span class="bold">${a(r.nombre)}</span><br>
            <span class="bold">Dirección:</span> ${a(r.direccion)}<br>
            <span class="bold">Celular:</span> ${a(r.celular)}<br>
            <span class="bold">${l?`GASTO`:`VENTA`} N° ${String(t.id).padStart(6,`0`)}</span>
          </td>
        </tr>
      </table>

      <hr class="dashed">

      <table>
        <tr>
          <td style="width:65%">
            <span class="bold">Cajero:</span> ${a(t.user?.name||`—`)}<br>
            <span class="bold">Fecha:</span> ${a(i)}<br>
            <span class="bold">Cliente:</span> ${a(u)}<br>
            ${f?`<span class="bold">Doctor:</span> ${a(f)}<br>`:``}
            <span class="bold">Pago:</span> ${a(t.tipo_pago||`—`)}
          </td>
          <td style="width:35%">
            <span class="bold">CI/NIT:</span> ${a(t.paciente?.ci||`0`)}<br>
            <span class="bold">Estado:</span> ${a(t.estado||`—`)}
          </td>
        </tr>
      </table>

      <div class="detalle-title">${l?`DETALLE DEL GASTO`:`SERVICIOS SOLICITADOS`}</div>

      <table class="items">
        <thead>
          <tr>
            <th style="width:56%">Descripción</th>
            <th style="width:12%" class="right">Cant.</th>
            <th style="width:16%" class="right precio">Precio</th>
            <th style="width:16%" class="right">Total</th>
          </tr>
        </thead>
        <tbody>${p}</tbody>
      </table>

      <table class="totales">
        <tr>
          <td style="width:68%" class="right">TOTAL Bs. :</td>
          <td style="width:32%" class="right monto">${o(t.total)}</td>
        </tr>
        ${l?``:`
        ${t.tipo_pago===`MIXTO`?`
        <tr>
          <td class="right">EFECTIVO Bs. :</td>
          <td class="right">${o(t.monto_efectivo)}</td>
        </tr>
        <tr>
          <td class="right">QR Bs. :</td>
          <td class="right">${o(t.monto_qr)}</td>
        </tr>`:``}
        <tr>
          <td class="right">PAGO Bs. :</td>
          <td class="right">${o(t.pago)}</td>
        </tr>
        <tr>
          <td class="right">CAMBIO Bs. :</td>
          <td class="right">${o(t.cambio)}</td>
        </tr>`}
      </table>

      <div class="son">SON: ${a(c(Number(t.total||0)))}</div>

      ${t.comentario?`<div class="obs"><span class="bold">Obs.:</span> ${a(t.comentario)}</div>`:``}

      <hr class="dashed" style="margin-top:16px">
      <div class="pie">${r.pie.map(a).join(`<br>`)}</div>
    </div>
  `}function d(e){let t=document.createElement(`div`);t.innerHTML=u(e),new n.Printd().print(t,[i],[],({launchPrint:e})=>e())}export{d as t};