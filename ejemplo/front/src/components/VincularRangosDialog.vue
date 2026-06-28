<template>
  <q-dialog
    :model-value="modelValue"
    maximized
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <q-card class="column no-wrap">
      <!-- HEADER -->
      <q-card-section class="row items-center q-pa-sm bg-deep-purple text-white" style="flex-shrink:0">
        <q-icon name="bar_chart" class="q-mr-sm" />
        <div class="text-subtitle1">Vincular rangos de referencia</div>
        <q-chip dense color="white" text-color="deep-purple" class="q-ml-sm" v-if="servicio">
          {{ servicio.nombre }}
        </q-chip>
        <q-space />
        <q-btn icon="close" flat round dense color="white" @click="$emit('update:modelValue', false)" />
      </q-card-section>

      <q-separator />

      <!-- BODY -->
      <div class="col" style="overflow-y: auto; padding: 8px 12px">

        <!-- ── RANGOS ── -->
        <div class="row items-center q-mb-xs">
          <q-icon name="linear_scale" color="deep-purple" size="16px" class="q-mr-xs" />
          <span class="text-caption text-weight-bold text-deep-purple">RANGOS VINCULADOS</span>
          <q-badge v-if="lista.length" color="deep-purple" :label="lista.length" class="q-ml-xs" />
        </div>

        <q-select
          v-model="rangoParaAgregar"
          :options="opcionesFiltradas"
          option-value="id"
          :option-label="opt => (opt.rango_nombre || opt.analito || '') + (opt.perfil ? ` · ${opt.perfil}` : '')"
          label="Buscar y agregar rango..."
          dense outlined use-input input-debounce="150" clearable
          class="q-mb-sm"
          style="max-width: 520px"
          @filter="onFiltrar"
          @update:model-value="onAgregar"
        >
          <template #option="scope">
            <q-item v-bind="scope.itemProps" dense>
              <q-item-section>
                <q-item-label class="text-caption text-weight-medium">
                  {{ scope.opt.rango_nombre || scope.opt.analito }}
                </q-item-label>
                <q-item-label caption>
                  <span v-if="scope.opt.perfil" class="text-purple">{{ scope.opt.perfil }}</span>
                  <span v-if="scope.opt.metodo"> · {{ scope.opt.metodo }}</span>
                  <span v-if="scope.opt.unidad" class="text-grey-6"> · {{ scope.opt.unidad }}</span>
                </q-item-label>
              </q-item-section>
            </q-item>
          </template>
          <template #no-option>
            <q-item dense>
              <q-item-section class="text-grey text-caption">Sin resultados o ya agregados</q-item-section>
            </q-item>
          </template>
        </q-select>

        <table v-if="lista.length" class="vr-table q-mb-md">
          <thead>
            <tr>
              <th style="width:24px"></th>
              <th style="width:24px">#</th>
              <th>Perfil</th>
              <th>Rango / Analito</th>
              <th>Método</th>
              <th style="max-width:160px">Referencia</th>
              <th style="min-width:160px">Variable</th>
              <th style="width:28px"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in lista"
              :key="item.id"
              draggable="true"
              :class="{ 'drag-highlight': dragOverIndex === idx }"
              @dragstart="onDragStart(idx)"
              @dragover.prevent="onDragOver(idx)"
              @dragleave="dragOverIndex = null"
              @drop.prevent="onDrop"
              @dragend="dragOverIndex = null"
            >
              <td class="tc"><q-icon name="drag_indicator" color="grey-5" size="14px" style="cursor:grab" /></td>
              <td class="tc text-grey-6">{{ idx + 1 }}</td>
              <td class="text-purple-7">{{ item.perfil || '—' }}</td>
              <td class="text-weight-medium">{{ item.rango_nombre }}</td>
              <td class="text-grey-7">{{ item.metodo || '—' }}</td>
              <td>
                <div class="ellipsis" style="max-width:156px" :title="item.interpretacion">
                  {{ item.interpretacion || '—' }}
                </div>
              </td>
              <td>
                <input v-model="item.nombre_variable" class="vr-input" placeholder="ej. glucosa_valor" @click.stop />
              </td>
              <td class="tc">
                <q-btn dense flat round icon="close" color="negative" size="10px" @click="quitarRango(idx)" />
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="vr-empty q-mb-md">Usa el selector para agregar rangos a esta prestación.</div>

        <!-- ── FÓRMULAS ── -->
        <q-separator class="q-mb-sm" />

        <div class="row items-center q-mb-sm">
          <q-icon name="functions" color="teal" size="16px" class="q-mr-xs" />
          <span class="text-caption text-weight-bold text-teal">FÓRMULAS DERIVADAS</span>
          <q-badge v-if="formulas.length" color="teal" :label="formulas.length" class="q-ml-xs" />
        </div>

        <!-- Tabla de fórmulas + fila de entrada inline -->
        <table class="vr-table q-mb-xs">
          <thead>
            <tr>
              <th style="min-width:170px">Variable resultado</th>
              <th style="width:16px" class="tc">=</th>
              <th>Fórmula</th>
              <th style="width:32px"></th>
            </tr>
          </thead>
          <tbody>
            <!-- Filas existentes -->
            <tr v-for="(f, idx) in formulas" :key="idx">
              <td>
                <template v-if="editandoIdx === idx">
                  <q-select
                    v-model="formulaForm.nombre_variable"
                    :options="variablesDisponibles"
                    dense outlined options-dense
                    style="min-width:160px"
                    use-input input-debounce="0"
                    @filter="(v,u) => u()"
                  />
                </template>
                <code v-else class="text-teal-8">{{ f.nombre_variable }}</code>
              </td>
              <td class="tc text-grey-5 text-weight-bold">=</td>
              <td>
                <template v-if="editandoIdx === idx">
                  <input
                    ref="formulaInputRef"
                    v-model="formulaForm.formula"
                    class="vr-input"
                    placeholder="ej. chagas_elisa_no_reactivo * 2"
                    @input="validarFormula"
                  />
                  <div class="row items-center q-gutter-xs q-mt-xs">
                    <q-chip
                      v-for="v in variablesDisponibles"
                      :key="v"
                      dense clickable size="xs"
                      color="deep-purple-1" text-color="deep-purple"
                      @click="insertarVariable(v)"
                    >{{ v }}</q-chip>
                    <q-btn v-for="op in ['+','-','*','/','(',')']" :key="op"
                      dense flat size="xs" color="grey-7" :label="op" class="vr-op-btn"
                      @click="insertarVariable(op)" />
                  </div>
                  <div v-if="formulaError" class="text-negative text-caption q-mt-xs">{{ formulaError }}</div>
                  <div v-else-if="formulaPreview !== null" class="text-caption text-grey-6 q-mt-xs">
                    Preview (vars=1): <strong>{{ formulaPreview }}</strong>
                  </div>
                </template>
                <code v-else class="text-grey-8">{{ f.formula }}</code>
              </td>
              <td class="tc">
                <template v-if="editandoIdx === idx">
                  <q-btn dense flat round icon="check" color="positive" size="10px"
                    :disable="!!formulaError || !formulaForm.formula || !formulaForm.nombre_variable"
                    @click="confirmarEdicion(idx)" />
                  <q-btn dense flat round icon="close" color="grey" size="10px" @click="editandoIdx = null" />
                </template>
                <template v-else>
                  <q-btn dense flat round icon="edit" color="grey-7" size="10px" @click="editarFormula(idx)" />
                  <q-btn dense flat round icon="delete" color="negative" size="10px" @click="formulas.splice(idx,1)" />
                </template>
              </td>
            </tr>

            <!-- Fila nueva -->
            <tr v-if="agregando">
              <td>
                <q-select
                  v-model="formulaForm.nombre_variable"
                  :options="variablesDisponibles"
                  dense outlined options-dense
                  style="min-width:160px"
                  use-input input-debounce="0"
                  label="Variable..."
                  @filter="(v,u) => u()"
                />
              </td>
              <td class="tc text-grey-5 text-weight-bold">=</td>
              <td>
                <input
                  ref="formulaInputRef"
                  v-model="formulaForm.formula"
                  class="vr-input"
                  placeholder="ej. chagas_elisa_no_reactivo * 2"
                  @input="validarFormula"
                />
                <div class="row items-center q-gutter-xs q-mt-xs">
                  <q-chip
                    v-for="v in variablesDisponibles"
                    :key="v"
                    dense clickable size="xs"
                    color="deep-purple-1" text-color="deep-purple"
                    @click="insertarVariable(v)"
                  >{{ v }}</q-chip>
                  <q-btn v-for="op in ['+','-','*','/','(',')']" :key="op"
                    dense flat size="xs" color="grey-7" :label="op" class="vr-op-btn"
                    @click="insertarVariable(op)" />
                </div>
                <div v-if="formulaError" class="text-negative text-caption q-mt-xs">{{ formulaError }}</div>
                <div v-else-if="formulaPreview !== null" class="text-caption text-grey-6 q-mt-xs">
                  Preview (vars=1): <strong>{{ formulaPreview }}</strong>
                </div>
              </td>
              <td class="tc">
                <q-btn dense flat round icon="check" color="positive" size="10px"
                  :disable="!!formulaError || !formulaForm.formula || !formulaForm.nombre_variable"
                  @click="confirmarNueva" />
                <q-btn dense flat round icon="close" color="grey" size="10px" @click="agregando = false" />
              </td>
            </tr>
          </tbody>
        </table>

        <q-btn
          v-if="!agregando && editandoIdx === null"
          flat dense no-caps size="sm"
          color="teal" icon="add" label="Agregar fórmula"
          :disable="!variablesDisponibles.length"
          @click="abrirNueva"
        />
        <div v-if="!variablesDisponibles.length" class="text-caption text-grey-5 q-ml-sm inline">
          (asigna nombres de variable a los rangos primero)
        </div>

      </div>

      <!-- FOOTER -->
      <q-separator />
      <q-card-section class="row justify-end q-pa-sm" style="flex-shrink:0">
        <q-btn flat no-caps label="Cancelar" :loading="loading" class="q-mr-sm"
          @click="$emit('update:modelValue', false)" />
        <q-btn color="deep-purple" icon="save" no-caps label="Guardar" :loading="loading" @click="guardar" />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script>
export default {
  name: 'VincularRangosDialog',

  props: {
    modelValue: Boolean,
    servicio: { type: Object, default: null },
    rangos: { type: Array, default: () => [] },
    vinculosIniciales: { type: Array, default: () => [] },
    formulasIniciales: { type: Array, default: () => [] },
    loading: Boolean
  },

  emits: ['update:modelValue', 'save'],

  data () {
    return {
      lista: [],
      rangoParaAgregar: null,
      opcionesFiltradas: [],
      dragFromIndex: null,
      dragOverIndex: null,

      formulas: [],
      agregando: false,
      editandoIdx: null,
      formulaForm: { nombre_variable: '', formula: '' },
      formulaError: '',
      formulaPreview: null
    }
  },

  computed: {
    variablesDisponibles () {
      return this.lista
        .map(r => r.nombre_variable)
        .filter(v => v && v.trim())
    }
  },

  watch: {
    modelValue (v) {
      if (v) this.inicializar()
    }
  },

  methods: {
    inicializar () {
      this.lista = [...this.vinculosIniciales]
        .sort((a, b) => (a.pivot?.orden ?? 0) - (b.pivot?.orden ?? 0))
        .map((r, idx) => ({
          id: r.id,
          rango_nombre: r.rango_nombre || r.analito || '',
          perfil: r.perfil || '',
          metodo: r.metodo || '',
          interpretacion: r.interpretacion || '',
          unidad: r.unidad || '',
          nombre_variable: r.pivot?.nombre_variable || '',
          orden: r.pivot?.orden ?? idx + 1
        }))
      this.formulas = this.formulasIniciales.map(f => ({
        nombre_variable: f.nombre_variable,
        formula: f.formula
      }))
      this.rangoParaAgregar = null
      this.dragFromIndex = null
      this.dragOverIndex = null
      this.agregando = false
      this.editandoIdx = null
      this.opcionesFiltradas = this.disponibles()
    },

    // ── RANGOS ──
    disponibles () {
      const usados = new Set(this.lista.map(r => r.id))
      return this.rangos.filter(r => !usados.has(r.id))
    },

    onFiltrar (val, update) {
      const term = (val || '').toLowerCase()
      update(() => {
        const disp = this.disponibles()
        this.opcionesFiltradas = term
          ? disp.filter(r =>
              (r.rango_nombre || '').toLowerCase().includes(term) ||
              (r.analito || '').toLowerCase().includes(term) ||
              (r.perfil || '').toLowerCase().includes(term) ||
              (r.metodo || '').toLowerCase().includes(term)
            )
          : disp
      })
    },

    onAgregar (rango) {
      if (!rango) return
      const nombre = rango.rango_nombre || rango.analito || ''
      this.lista.push({
        id: rango.id,
        rango_nombre: nombre,
        perfil: rango.perfil || '',
        metodo: rango.metodo || '',
        interpretacion: rango.interpretacion || '',
        unidad: rango.unidad || '',
        nombre_variable: this.toVariable(nombre),
        orden: this.lista.length + 1
      })
      this.$nextTick(() => { this.rangoParaAgregar = null })
      this.opcionesFiltradas = this.disponibles()
    },

    quitarRango (idx) {
      this.lista.splice(idx, 1)
      this.opcionesFiltradas = this.disponibles()
    },

    onDragStart (idx) { this.dragFromIndex = idx },
    onDragOver (idx) { this.dragOverIndex = idx },
    onDrop () {
      const from = this.dragFromIndex
      const to = this.dragOverIndex
      if (from === null || to === null || from === to) {
        this.dragFromIndex = null; this.dragOverIndex = null; return
      }
      const item = this.lista.splice(from, 1)[0]
      this.lista.splice(to, 0, item)
      this.dragFromIndex = null; this.dragOverIndex = null
    },

    toVariable (nombre) {
      return (nombre || '')
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
    },

    // ── FÓRMULAS ──
    resetFormula () {
      this.formulaForm = { nombre_variable: '', formula: '' }
      this.formulaError = ''
      this.formulaPreview = null
    },

    abrirNueva () {
      this.resetFormula()
      this.editandoIdx = null
      this.agregando = true
    },

    editarFormula (idx) {
      this.resetFormula()
      this.formulaForm = { ...this.formulas[idx] }
      this.editandoIdx = idx
      this.agregando = false
    },

    insertarVariable (token) {
      const inputEl = Array.isArray(this.$refs.formulaInputRef)
        ? this.$refs.formulaInputRef[0]
        : this.$refs.formulaInputRef
      if (inputEl) {
        const pos = inputEl.selectionStart ?? this.formulaForm.formula.length
        const f = this.formulaForm.formula
        const sp = token.length > 1 ? ' ' : ''
        this.formulaForm.formula = f.slice(0, pos) + sp + token + sp + f.slice(pos)
        this.$nextTick(() => {
          const newPos = pos + sp.length + token.length + sp.length
          inputEl.focus()
          inputEl.setSelectionRange(newPos, newPos)
        })
      } else {
        this.formulaForm.formula += ' ' + token
      }
      this.validarFormula()
    },

    validarFormula () {
      const expr = (this.formulaForm.formula || '').trim()
      this.formulaError = ''
      this.formulaPreview = null
      if (!expr) return

      const tokens = expr
        .replace(/[()]/g, ' ')
        .split(/[\s+\-*/]+/)
        .map(t => t.trim())
        .filter(t => t && !/^\d+(\.\d+)?$/.test(t))

      const desconocidos = tokens.filter(t => !this.variablesDisponibles.includes(t))
      if (desconocidos.length) {
        this.formulaError = `Variable(s) desconocida(s): ${desconocidos.join(', ')}`
        return
      }

      try {
        let preview = expr
        this.variablesDisponibles.forEach(v => {
          preview = preview.replace(new RegExp('\\b' + v + '\\b', 'g'), '1')
        })
        // eslint-disable-next-line no-new-func
        this.formulaPreview = new Function('return (' + preview + ')')()
      } catch {
        this.formulaError = 'Expresión inválida'
      }
    },

    confirmarNueva () {
      if (this.formulaError || !this.formulaForm.formula || !this.formulaForm.nombre_variable) return
      this.formulas.push({ ...this.formulaForm })
      this.agregando = false
      this.resetFormula()
    },

    confirmarEdicion (idx) {
      if (this.formulaError || !this.formulaForm.formula || !this.formulaForm.nombre_variable) return
      this.formulas.splice(idx, 1, { ...this.formulaForm })
      this.editandoIdx = null
      this.resetFormula()
    },

    // ── GUARDAR ──
    guardar () {
      const rangos = this.lista.map((item, idx) => ({
        area_rango_id: item.id,
        nombre_variable: item.nombre_variable || null,
        orden: idx + 1
      }))
      const formulas = this.formulas.map((f, idx) => ({ ...f, label: '', unidad: '', orden: idx + 1 }))
      this.$emit('save', { rangos, formulas })
    }
  }
}
</script>

<style scoped>
.vr-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.vr-table th {
  background: #f5f5f5;
  padding: 4px 6px;
  text-align: left;
  font-weight: 600;
  color: #555;
  border: 1px solid #e0e0e0;
  white-space: nowrap;
}
.vr-table td {
  padding: 3px 6px;
  border: 1px solid #e0e0e0;
  vertical-align: middle;
}
.vr-table tr:hover td { background: #f3f0ff; }
.drag-highlight td { background: #e3f2fd !important; }
.tc { text-align: center; }
.vr-input {
  width: 100%;
  border: 1px solid #bdbdbd;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 12px;
  outline: none;
  background: #fff;
  box-sizing: border-box;
}
.vr-input:focus { border-color: #7c3aed; box-shadow: 0 0 0 2px #ede9fe; }
.vr-empty {
  padding: 10px;
  font-size: 12px;
  color: #9e9e9e;
  background: #fafafa;
  border: 1px dashed #e0e0e0;
  border-radius: 4px;
  text-align: center;
}
.vr-op-btn { min-width: 24px; font-family: monospace; font-weight: 700; }
code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-size: 11px; }
</style>
