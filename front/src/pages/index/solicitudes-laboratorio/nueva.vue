<template>
  <q-page class="q-pa-sm lab-compacto column no-wrap">

    <!-- Cabecera -->
    <div class="row items-center q-mb-xs">
      <q-btn flat dense round icon="arrow_back" color="grey-7" class="q-mr-sm" to="/solicitudes-laboratorio">
        <q-tooltip>Volver</q-tooltip>
      </q-btn>
      <div>
        <div class="text-subtitle1 text-weight-bold">{{ editId ? 'Modificar laboratorio' : 'Crear laboratorio' }}</div>
      </div>
      <q-space />
      <div class="text-caption text-grey-7">
        {{ seleccionados.length }} prueba(s) · <b class="text-primary">Bs {{ money(total) }}</b>
      </div>
    </div>

    <q-form @submit.prevent="guardar" class="col column no-wrap">

      <!-- Datos de la solicitud: una sola fila compacta -->
      <q-card flat bordered class="q-pa-xs q-mb-xs">
        <div class="row q-col-gutter-xs">
          <div class="col-12 col-md-3">
            <q-select v-model="form.paciente_id" :options="pacientesOpciones" option-value="id"
                      :option-label="pacienteLabel" emit-value map-options use-input input-debounce="200"
                      dense outlined label="Paciente *" :rules="[required]" hide-bottom-space
                      :loading="pacientesLoading"
                      @filter="filtrarPacientes" @virtual-scroll="cargarMasPacientes">
              <template #prepend><q-icon name="person" size="16px" /></template>
            </q-select>
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="form.doctor_id" :options="doctores" option-value="id" option-label="nombre"
                      emit-value map-options clearable dense outlined label="Doctor solicitante"
                      use-input input-debounce="200" hide-bottom-space @filter="filtrarDoctores">
              <template #prepend><q-icon name="medical_information" size="16px" /></template>
              <template #no-option>
                <q-item clickable @click="abrirDoctorNuevo">
                  <q-item-section avatar><q-icon name="add" color="primary" /></q-item-section>
                  <q-item-section class="text-primary">Crear doctor nuevo</q-item-section>
                </q-item>
              </template>
              <template #after>
                <q-btn flat round dense icon="add" color="primary" size="sm" @click="abrirDoctorNuevo">
                  <q-tooltip>Agregar doctor que no existe</q-tooltip>
                </q-btn>
              </template>
            </q-select>
          </div>
          <div class="col-6 col-md-2">
            <q-input v-model="form.fecha_solicitud" type="date" dense outlined label="Fecha *"
                     :rules="[required]" hide-bottom-space />
          </div>
          <div class="col-6 col-md-1">
            <q-input v-model="form.hora_solicitud" type="time" dense outlined label="Hora *"
                     :rules="[required]" hide-bottom-space />
          </div>
          <div class="col-12 col-md-3">
            <q-input v-model="form.diagnostico_clinico" v-uppercase dense outlined
                     label="Diagnóstico clínico" hide-bottom-space />
          </div>
          <div class="col-12">
            <q-input v-model="form.observaciones" v-uppercase dense outlined
                     label="Observaciones" hide-bottom-space />
          </div>
        </div>
      </q-card>

      <!-- Cuerpo: catálogo a la izquierda, resultados a la derecha (scroll independiente) -->
      <div class="row q-col-gutter-xs col lab-cuerpo">

        <!-- Catálogo de pruebas -->
        <div class="col-12 col-md-4 column no-wrap">
          <q-card flat bordered class="column no-wrap full-height">
            <div class="row items-center q-pa-xs q-gutter-xs bg-grey-1">
              <div class="text-caption text-weight-bold text-grey-8">
                PRUEBAS · {{ seleccionados.length }}/{{ laboratorios.length }}
              </div>
              <q-space />
              <q-input v-model="buscarLaboratorio" dense outlined clearable placeholder="Buscar…"
                       bg-color="white" style="width:150px" hide-bottom-space>
                <template #prepend><q-icon name="search" size="16px" /></template>
              </q-input>
            </div>
            <q-separator />
            <div class="col lab-scroll">
              <div v-if="!laboratoriosFiltrados.length" class="text-center text-grey-5 q-pa-md text-caption">
                Sin resultados
              </div>
              <div v-for="laboratorio in laboratoriosFiltrados" :key="laboratorio.id"
                   class="row items-center no-wrap lab-item cursor-pointer"
                   :class="{ 'bg-green-1': form.producto_ids.includes(laboratorio.id) }"
                   @click="toggleLaboratorio(laboratorio.id)">
                <q-checkbox dense size="xs" :model-value="form.producto_ids.includes(laboratorio.id)"
                            @click.stop @update:model-value="toggleLaboratorio(laboratorio.id)" />
                <div class="col q-ml-xs ellipsis">
                  <div class="text-caption text-weight-medium ellipsis">{{ laboratorio.nombre }}</div>
                  <div class="lab-sub text-grey-6 ellipsis">
                    {{ laboratorio.codigo || 'S/C' }} · {{ laboratorio.laboratorio_datos?.length || 0 }} datos
                  </div>
                </div>
                <div class="lab-sub text-primary text-weight-bold q-ml-xs">{{ money(laboratorio.precio) }}</div>
              </div>
            </div>
          </q-card>
        </div>

        <!-- Datos a completar -->
        <div class="col-12 col-md-8 column no-wrap">
          <q-card flat bordered class="column no-wrap full-height">
            <div class="row items-center q-pa-xs bg-grey-1">
              <div class="text-caption text-weight-bold text-grey-8">DATOS DE LAS PRUEBAS SELECCIONADAS</div>
              <q-space />
              <q-btn v-if="seleccionados.length" flat dense no-caps size="sm" color="grey-7"
                     :icon="compacto ? 'unfold_more' : 'unfold_less'"
                     :label="compacto ? 'Expandir' : 'Compactar'" @click="compacto = !compacto" />
            </div>
            <q-separator />
            <div class="col lab-scroll q-pa-xs">
              <div v-if="!seleccionados.length" class="text-center text-grey-5 q-pa-md text-caption">
                Seleccione pruebas del listado de la izquierda
              </div>
              <div v-for="laboratorio in seleccionados" :key="laboratorio.id" class="q-mb-xs">
                <div class="row items-center bg-blue-grey-1 q-px-xs q-py-none rounded-borders">
                  <q-icon name="biotech" size="14px" color="primary" class="q-mr-xs" />
                  <span class="text-caption text-weight-bold">{{ laboratorio.nombre }}</span>
                  <q-space />
                  <span class="lab-sub text-grey-7">Bs {{ money(laboratorio.precio) }}</span>
                  <q-btn flat dense round size="xs" icon="close" color="negative" class="q-ml-xs"
                         @click="toggleLaboratorio(laboratorio.id)">
                    <q-tooltip>Quitar prueba</q-tooltip>
                  </q-btn>
                </div>
                <div class="row q-col-gutter-xs q-mt-none">
                  <div v-for="dato in laboratorio.laboratorio_datos" :key="dato.id"
                       :class="compacto ? 'col-6 col-md-3' : 'col-12 col-md-6'">
                    <!-- Dato con lista cerrada de valores -->
                    <q-select v-if="!dato.formula && dato.opciones?.length"
                              :model-value="valores[dato.id] || ''"
                              dense outlined clearable hide-bottom-space
                              :label="dato.nombre" :suffix="dato.unidad || undefined"
                              :options="dato.opciones.map(o => o.valor)"
                              @update:model-value="valor => valores[dato.id] = valor">
                      <q-tooltip v-if="dato.rango_referencia">Referencia: {{ dato.rango_referencia }}</q-tooltip>
                    </q-select>
                    <q-input v-else-if="!dato.formula" :model-value="valores[dato.id] || ''"
                             dense outlined :label="dato.nombre" hide-bottom-space
                             :suffix="dato.unidad || undefined"
                             @update:model-value="valor => valores[dato.id] = valor">
                      <q-tooltip v-if="dato.rango_referencia">Referencia: {{ dato.rango_referencia }}</q-tooltip>
                    </q-input>
                    <q-input v-else :model-value="calcularDato(laboratorio, dato)"
                             dense outlined readonly color="deep-purple" hide-bottom-space
                             :label="dato.nombre" :suffix="dato.unidad || undefined">
                      <template #prepend><q-icon name="functions" size="14px" color="deep-purple" /></template>
                      <q-tooltip>Fórmula: {{ dato.formula.formula }}</q-tooltip>
                    </q-input>
                  </div>
                </div>
              </div>
            </div>
          </q-card>
        </div>
      </div>

      <!-- Barra de acciones fija -->
      <div class="row items-center q-gutter-sm q-mt-xs">
        <q-btn v-if="alertas.length" dense flat no-caps color="orange-9" icon="warning"
               :label="`${alertas.length} validación(es) sin cumplir`" @click="alertasDialog = true">
          <q-tooltip>Ver los mensajes de validación pendientes</q-tooltip>
        </q-btn>
        <q-space />
        <div class="text-subtitle1 text-weight-bold">Total: <span class="text-primary">Bs {{ money(total) }}</span></div>
        <q-btn flat dense label="Cancelar" no-caps color="grey-7" to="/solicitudes-laboratorio" />
        <q-btn type="submit" color="positive" icon="save" dense padding="4px 14px"
               :label="editId ? 'Guardar e imprimir' : 'Crear e imprimir'"
               no-caps :loading="saving" :disable="!form.producto_ids.length" />
      </div>
    </q-form>

    <!-- DIALOG MENSAJES DE VALIDACIÓN -->
    <q-dialog v-model="alertasDialog">
      <q-card style="width:min(96vw,560px)">
        <q-card-section class="row items-center bg-orange-9 text-white q-py-sm">
          <q-icon name="warning" size="20px" class="q-mr-sm" />
          <span class="text-subtitle2 text-weight-bold">Validaciones sin cumplir</span>
          <q-space /><q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-card-section class="q-pa-sm" style="max-height:60vh;overflow-y:auto">
          <q-list bordered separator dense>
            <q-item v-for="(alerta, idx) in alertas" :key="idx">
              <q-item-section avatar><q-icon name="error_outline" color="orange-9" /></q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ alerta.mensaje }}</q-item-label>
                <q-item-label caption>
                  {{ alerta.laboratorio }} · <code>{{ alerta.expresion }}</code> = <b>{{ alerta.obtenido }}</b>
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
        <q-card-actions align="right" class="q-pa-sm">
          <q-btn flat dense color="grey-7" label="Corregir" no-caps v-close-popup />
          <q-btn v-if="confirmandoGuardado" dense padding="4px 14px" color="orange-9" no-caps
                 label="Guardar de todos modos" icon-right="save" @click="continuarGuardado" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- DIALOG NUEVO DOCTOR -->
    <q-dialog v-model="doctorDialog" persistent>
      <q-card style="width:min(96vw,420px)">
        <q-card-section class="bg-primary text-white q-py-sm">
          <span class="text-subtitle2 text-weight-bold">Nuevo doctor</span>
        </q-card-section>
        <q-card-section>
          <q-form @submit.prevent="guardarDoctor">
            <q-input v-model="doctorNuevo.nombre" label="Nombre completo *" dense outlined class="q-mb-sm"
                     :rules="[required]" v-uppercase autofocus />
            <q-select v-model="doctorNuevo.especialidad_ids" label="Especialidades" dense outlined class="q-mb-md"
                      multiple use-chips :options="especialidades"
                      option-value="id" option-label="nombre" emit-value map-options />
            <div class="row justify-end q-gutter-sm">
              <q-btn flat color="grey-7" label="Cancelar" no-caps @click="doctorDialog = false" />
              <q-btn color="primary" label="Crear" type="submit" no-caps :loading="savingDoctor" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue'

const { proxy } = getCurrentInstance()
const pacientesOpciones = ref([])
const pacientesLoading = ref(false)
const pacientesPage = ref(1)
const pacientesLastPage = ref(1)
const pacienteSearch = ref('')
const doctores = ref([])
const doctoresTodos = ref([])
const especialidades = ref([])
const doctorDialog = ref(false)
const savingDoctor = ref(false)
const doctorNuevo = ref({ nombre: '', especialidad_ids: [] })
const laboratorios = ref([])
const buscarLaboratorio = ref('')
const compacto = ref(true)
const saving = ref(false)
const alertasDialog = ref(false)
const confirmandoGuardado = ref(false)
const valores = ref({})
const editId = ref(proxy.$route.query.id || null)
const now = new Date()
const form = ref({
  paciente_id: null,
  doctor_id: null,
  fecha_solicitud: now.toISOString().slice(0, 10),
  hora_solicitud: now.toTimeString().slice(0, 5),
  diagnostico_clinico: '',
  observaciones: '',
  producto_ids: [],
})

const laboratoriosFiltrados = computed(() => {
  const search = buscarLaboratorio.value.toLowerCase().trim()
  return search
    ? laboratorios.value.filter(item => `${item.nombre} ${item.codigo || ''}`.toLowerCase().includes(search))
    : laboratorios.value
})
const seleccionados = computed(() => laboratorios.value.filter(item => form.value.producto_ids.includes(item.id)))
const total = computed(() => seleccionados.value.reduce((sum, item) => sum + Number(item.precio || 0), 0))

// Mensajes de validación configurados en cada laboratorio que no se cumplen
const alertas = computed(() => seleccionados.value.flatMap(laboratorio =>
  (laboratorio.laboratorio_validaciones || [])
    .filter(validacion => validacion.activo !== false)
    .map(validacion => evaluarValidacion(laboratorio, validacion))
    .filter(Boolean)
))

async function cargarDatos () {
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/form-data')
    doctoresTodos.value = data.doctores
    doctores.value = data.doctores
    laboratorios.value = data.laboratorios
    await buscarPacientes('', 1)
    if (editId.value) await cargarSolicitud()
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || 'No se pudieron cargar los datos')
  }
}
async function cargarSolicitud () {
  const { data } = await proxy.$axios.get(`solicitudes-laboratorio/${editId.value}`)
  form.value = {
    paciente_id: data.paciente_id,
    doctor_id: data.doctor_id,
    fecha_solicitud: data.fecha_solicitud,
    hora_solicitud: String(data.hora_solicitud || '').slice(0, 5),
    diagnostico_clinico: data.diagnostico_clinico || '',
    observaciones: data.observaciones || '',
    producto_ids: (data.laboratorio_items || []).map(item => item.producto_id).filter(Boolean),
  }
  if (data.paciente && !pacientesOpciones.value.some(item => item.id === data.paciente.id)) {
    pacientesOpciones.value.unshift(data.paciente)
  }
  for (const item of data.laboratorio_items || []) {
    for (const resultado of item.resultados || []) {
      if (resultado.producto_laboratorio_dato_id) {
        valores.value[resultado.producto_laboratorio_dato_id] = resultado.valor || ''
      }
    }
  }
}
async function buscarPacientes (search, page, append = false) {
  pacientesLoading.value = true
  try {
    const { data } = await proxy.$axios.get('solicitudes-laboratorio/pacientes', {
      params: { q: search || undefined, page, per_page: 20 },
    })
    pacientesOpciones.value = append
      ? [...pacientesOpciones.value, ...data.data]
      : data.data
    pacientesPage.value = data.current_page
    pacientesLastPage.value = data.last_page
  } finally {
    pacientesLoading.value = false
  }
}
function filtrarPacientes (value, update, abort) {
  buscarPacientes(value.trim(), 1)
    .then(() => {
      pacienteSearch.value = value.trim()
      update(() => {})
    })
    .catch(abort)
}
async function cargarMasPacientes ({ to, ref }) {
  if (pacientesLoading.value || pacientesPage.value >= pacientesLastPage.value) return
  if (to < pacientesOpciones.value.length - 5) return
  await buscarPacientes(pacienteSearch.value, pacientesPage.value + 1, true)
  ref.refresh()
}
function pacienteLabel (paciente) {
  return `${paciente.nombre_completo}${paciente.ci ? ` · CI ${paciente.ci}` : ''}`
}
function filtrarDoctores (value, update) {
  const search = value.toLowerCase().trim()
  update(() => {
    doctores.value = search
      ? doctoresTodos.value.filter(item => String(item.nombre || '').toLowerCase().includes(search))
      : doctoresTodos.value
  })
}
async function abrirDoctorNuevo () {
  doctorNuevo.value = { nombre: '', especialidad_ids: [] }
  doctorDialog.value = true
  if (!especialidades.value.length) {
    try {
      const { data } = await proxy.$axios.get('especialidades')
      especialidades.value = data || []
    } catch { /* silent */ }
  }
}
async function guardarDoctor () {
  savingDoctor.value = true
  try {
    const { data } = await proxy.$axios.post('doctores', doctorNuevo.value)
    doctoresTodos.value = [data, ...doctoresTodos.value]
    doctores.value = doctoresTodos.value
    form.value.doctor_id = data.id
    doctorDialog.value = false
    proxy.$alert.success('Doctor creado')
  } catch (error) {
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el doctor')
  } finally {
    savingDoctor.value = false
  }
}
function toggleLaboratorio (id) {
  const index = form.value.producto_ids.indexOf(id)
  if (index >= 0) form.value.producto_ids.splice(index, 1)
  else {
    form.value.producto_ids.push(id)
    precargarDefectos(id)
  }
}

/* Precarga el valor por defecto de cada dato al agregar una prueba. Solo
   rellena lo que está vacío: nunca pisa un resultado ya escrito ni uno
   recuperado al editar la solicitud. */
function precargarDefectos (productoId) {
  const laboratorio = laboratorios.value.find(item => item.id === productoId)
  for (const dato of laboratorio?.laboratorio_datos || []) {
    if (dato.formula || !dato.valor_defecto) continue
    if (!valores.value[dato.id]) valores.value[dato.id] = dato.valor_defecto
  }
}
async function guardar () {
  if (!form.value.producto_ids.length) {
    proxy.$alert.error('Seleccione al menos una prueba de laboratorio')
    return
  }
  // Si alguna regla configurada no se cumple, se avisa antes de guardar
  if (alertas.value.length) {
    confirmandoGuardado.value = true
    alertasDialog.value = true
    return
  }
  await guardarConfirmado()
}
function continuarGuardado () {
  alertasDialog.value = false
  confirmandoGuardado.value = false
  guardarConfirmado()
}
async function guardarConfirmado () {
  confirmandoGuardado.value = false
  saving.value = true
  const ventanaImpresion = window.open('', '_blank')
  try {
    const resultados = seleccionados.value.flatMap(laboratorio =>
      (laboratorio.laboratorio_datos || []).map(dato => ({
        producto_laboratorio_dato_id: dato.id,
        valor: dato.formula ? calcularDato(laboratorio, dato) : (valores.value[dato.id] || null),
      }))
    )
    const payload = {
      ...form.value,
      resultados,
    }
    const { data } = editId.value
      ? await proxy.$axios.put(`solicitudes-laboratorio/${editId.value}`, payload)
      : await proxy.$axios.post('solicitudes-laboratorio', payload)
    await abrirPdf(data.id, ventanaImpresion)
    proxy.$alert.success(`Laboratorio ${data.codigo_solicitud} ${editId.value ? 'actualizado' : 'creado'}`)
    proxy.$router.push('/solicitudes-laboratorio')
  } catch (error) {
    ventanaImpresion?.close()
    proxy.$alert.error(error.response?.data?.message || firstValidationError(error) || 'No se pudo crear el laboratorio')
  } finally {
    saving.value = false
  }
}
async function abrirPdf (id, ventana) {
  const response = await proxy.$axios.get(`solicitudes-laboratorio/${id}/pdf`, { responseType: 'blob' })
  const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
  if (ventana) ventana.location.href = url
  else window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 60000)
}
function required (value) {
  return value !== null && value !== undefined && value !== '' || 'Campo requerido'
}
function firstValidationError (error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : null
}
function money (value) {
  return Number(value || 0).toFixed(2)
}
// Evalúa una regla; devuelve el detalle de la alerta si NO se cumple, o null
function evaluarValidacion (laboratorio, validacion) {
  const porVariable = Object.fromEntries((laboratorio.laboratorio_datos || [])
    .map(item => [item.nombre_variable, item]))
  let resultado
  try {
    resultado = evaluarExpresion(validacion.expresion, variable => {
      const dependencia = porVariable[variable]
      if (!dependencia) throw new Error('Variable desconocida')
      const valor = dependencia.formula
        ? calcularDato(laboratorio, dependencia)
        : valores.value[dependencia.id]
      const numero = Number(valor)
      if (valor === '' || valor === null || valor === undefined || !Number.isFinite(numero)) {
        throw new Error('Dato pendiente')
      }
      return numero
    })
  } catch {
    return null // faltan datos por llenar: todavía no se evalúa
  }
  if (!Number.isFinite(resultado)) return null

  const valor = Number(validacion.valor)
  const hasta = Number(validacion.valor_hasta)
  const obtenido = Math.round(resultado * 10000) / 10000
  const cumple = {
    '=': obtenido === valor,
    '!=': obtenido !== valor,
    '>': obtenido > valor,
    '>=': obtenido >= valor,
    '<': obtenido < valor,
    '<=': obtenido <= valor,
    entre: obtenido >= valor && obtenido <= hasta,
  }[validacion.operador]

  return cumple
    ? null
    : { laboratorio: laboratorio.nombre, mensaje: validacion.mensaje, expresion: validacion.expresion, obtenido }
}

function calcularDato (laboratorio, dato, procesando = new Set()) {
  if (!dato.formula) return valores.value[dato.id] || ''
  if (procesando.has(dato.id)) return ''
  const siguientes = new Set(procesando)
  siguientes.add(dato.id)
  const porVariable = Object.fromEntries((laboratorio.laboratorio_datos || []).map(item => [item.nombre_variable, item]))

  try {
    const resultado = evaluarExpresion(dato.formula.formula, variable => {
      const dependencia = porVariable[variable]
      if (!dependencia) throw new Error('Variable desconocida')
      const valor = dependencia.formula
        ? calcularDato(laboratorio, dependencia, siguientes)
        : valores.value[dependencia.id]
      const numero = Number(valor)
      if (valor === '' || valor === null || valor === undefined || !Number.isFinite(numero)) throw new Error('Dato pendiente')
      return numero
    })
    return Number.isFinite(resultado) ? String(Math.round(resultado * 100) / 100) : ''
  } catch {
    return ''
  }
}
function evaluarExpresion (expression, resolveVariable) {
  const tokens = expression.match(/[a-z][a-z0-9_]*|\d+(?:\.\d+)?|[()+\-*/]/gi) || []
  if (tokens.join('') !== expression.replace(/\s+/g, '')) throw new Error('Expresión inválida')
  let position = 0
  const parseFactor = () => {
    const token = tokens[position++]
    if (token === '(') {
      const value = parseExpression()
      if (tokens[position++] !== ')') throw new Error('Paréntesis inválidos')
      return value
    }
    if (token === '-') return -parseFactor()
    if (/^\d/.test(token || '')) return Number(token)
    if (/^[a-z]/i.test(token || '')) return resolveVariable(token.toLowerCase())
    throw new Error('Factor inválido')
  }
  const parseTerm = () => {
    let value = parseFactor()
    while (tokens[position] === '*' || tokens[position] === '/') {
      const operator = tokens[position++]
      const right = parseFactor()
      value = operator === '*' ? value * right : value / right
    }
    return value
  }
  const parseExpression = () => {
    let value = parseTerm()
    while (tokens[position] === '+' || tokens[position] === '-') {
      const operator = tokens[position++]
      const right = parseTerm()
      value = operator === '+' ? value + right : value - right
    }
    return value
  }
  const result = parseExpression()
  if (position !== tokens.length) throw new Error('Expresión incompleta')
  return result
}

cargarDatos()
</script>

<style scoped>
/* Altura fija: el cuerpo hace scroll interno, la página no crece */
.lab-compacto > .q-form,
.lab-cuerpo,
.lab-cuerpo > div,
.lab-cuerpo .q-card {
  min-height: 0;
}
.lab-cuerpo {
  height: calc(100vh - 250px);
}
.lab-scroll {
  overflow-y: auto;
  min-height: 140px;
}
/* En pantallas chicas se apilan y la página vuelve a hacer scroll normal */
@media (max-width: 1023px) {
  .lab-cuerpo {
    height: auto;
  }
  .lab-scroll {
    max-height: 45vh;
  }
}
.lab-item {
  padding: 1px 4px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}
.lab-item:hover {
  background: rgba(0, 0, 0, 0.04);
}
.lab-sub {
  font-size: 10px;
  line-height: 1.2;
}

/* Campos densos y comprimidos */
.lab-compacto :deep(.q-field--dense .q-field__control),
.lab-compacto :deep(.q-field--dense .q-field__marginal) {
  height: 28px;
  min-height: 28px;
}
.lab-compacto :deep(.q-field--dense .q-field__native),
.lab-compacto :deep(.q-field--dense .q-field__input),
.lab-compacto :deep(.q-field--dense .q-field__label),
.lab-compacto :deep(.q-field--dense .q-field__suffix) {
  font-size: 11px;
}
.lab-compacto :deep(.q-field--dense .q-field__append),
.lab-compacto :deep(.q-field--dense .q-field__prepend) {
  height: 28px;
}
.lab-compacto :deep(.q-field--dense.q-field--float .q-field__label) {
  transform: translateY(-38%) scale(0.75);
}
.lab-compacto :deep(.q-field__bottom) {
  min-height: 0;
  padding: 0;
}
</style>
