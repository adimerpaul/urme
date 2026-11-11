import{t as e}from"./printd-CI3YqDZj.js";var t=e(),n=`
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
`,r=7;function i(e){let t=e.defaultView;e.querySelectorAll(`.titulo, td`).forEach(e=>{let n=parseFloat(t.getComputedStyle(e).fontSize)*72/96;for(;e.scrollWidth>e.clientWidth&&n>r;)n=Math.max(r,n-.25),e.style.fontSize=`${n}pt`;e.scrollWidth>e.clientWidth&&(e.style.whiteSpace=`normal`)})}function a(e){return String(e??``).replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`).replace(/"/g,`&quot;`).replace(/'/g,`&#039;`)}function o(){return new Intl.DateTimeFormat(`es-BO`,{day:`2-digit`,month:`2-digit`,year:`numeric`,timeZone:`America/La_Paz`}).format(new Date)}function s(e,r=``){let s=`
    <main class="pagina">
      <section class="rotulo">
        <img class="logo" src="${a(`${String(r).replace(/\/$/,``)}/images/logo-laboratorio-urme.jpg`)}" alt="Laboratorio de Diagnóstico Clínico URME">
        <div class="titulo">Laboratorio de Diagnóstico Clínico URME</div>
        <table>
          <tr><th>Nombre:</th><td>${a(e.paciente?.nombre_completo||`NO ASIGNADO`)}</td></tr>
          <tr><th>Médico:</th><td>${a(e.doctor?.nombre||`NO ASIGNADO`)}</td></tr>
          <tr><th>Institución:</th><td>${a(e.paciente?.seguro?.nombre||`PARTICULAR`)}</td></tr>
          <tr><th>Fecha de Transcripción:</th><td>${a(o())}</td></tr>
        </table>
      </section>
    </main>`,c=document.createElement(`div`);c.innerHTML=s,new t.Printd().print(c,[n],[],({iframe:e,launchPrint:t})=>{i(e.contentDocument),t()})}async function c(e,n){let{data:r}=await e.get(`solicitudes-laboratorio/${n}/impresion`,{responseType:`text`}),i=new DOMParser().parseFromString(r,`text/html`),a=Array.from(i.querySelectorAll(`style`),e=>e.textContent),o=document.createElement(`div`);return o.innerHTML=i.body.innerHTML,new Promise((e,n)=>{let r=new t.Printd;r.onAfterPrint(()=>r.getIFrame().remove()),r.print(o,a,[],({iframe:t,launchPrint:r})=>{try{t.contentWindow.focus(),r(),e()}catch(e){t.remove(),n(e)}})})}export{s as n,c as t};