<template>
  <q-card-section v-if="header" class="q-pa-sm bg-blue-1">
    <div class="row q-col-gutter-sm text-caption">
      <div class="col-12 col-md-2">
        <q-icon name="badge" class="q-mr-sm" color="blue" />
        <span class="text-bold">CÓDIGO:</span>
        <span class="line clip">{{ header.codigo || header.id }}</span>
      </div>

      <div class="col-12 col-md-2">
        <q-icon name="local_hospital" class="q-mr-sm" color="blue" />
        <span class="text-bold">ATENCIÓN:</span> <span class="line clip">{{ (header.tipo_atencion || '') === 'SI' ? 'SUS' : 'EXT' }}</span>
      </div>
      <div class="col-12 col-md-3">
        <q-icon name="assignment_ind" class="q-mr-sm" color="blue" />
        <span class="text-bold">NRO. REGISTRO:</span>
        <span class="line clip">{{ header.nro_registro || '-' }}</span>
      </div>
      <div class="col-12 col-md-5">
        <q-icon name="person" class="q-mr-sm" color="blue" />
        <span class="text-bold">PACIENTE:</span>
        <span class="line clip">{{ header.paciente_nombre || '-' }}</span>
      </div>
      <div class="col-12 col-md-2">
        <q-icon name="cake" class="q-mr-sm" color="blue" />
        <span class="text-bold">EDAD:</span>
        <span class="line clip">{{ header.paciente_edad || '-' }}</span>
      </div>
      <div class="col-12 col-md-2">
        <q-icon name="wc" class="q-mr-sm" color="blue" />
        <span class="text-bold">SEXO:</span>
        <span class="line clip">{{ header.paciente_genero || '-' }}</span>
      </div>
      <div class="col-12 col-md-4">
        <q-icon name="medical_services" class="q-mr-sm" color="blue" />
        <span class="text-bold">MÉDICO SOL.:</span>
        <span class="line clip">{{ header.doctor_nombre || '-' }}</span>
      </div>
      <div class="col-12 col-md-4">
        <q-icon name="healing" class="q-mr-sm" color="blue" />
        <span class="text-bold">DX:</span>
        <span class="line clip">
          {{ header.diagnostico_select ? header.diagnostico_select : (header.diagnostico_clinico || '-') }}
<!--          <pre>{{header.diagnostico_clinico}}</pre>-->
        </span>
      </div>
      <div class="col-12 col-md-6">
        <q-icon name="local_hospital" class="q-mr-sm" color="blue" />
        <span class="text-bold">EST. DE SALUD:</span>
        <span class="line clip">{{ header.establecimiento_salud || '-' }}</span>
      </div>
      <div class="col-12 col-md-6">
        <q-icon name="event" class="q-mr-sm" color="blue" />
        <span class="text-bold">CÓDIGO MUESTRA:    </span>
        <span class="line clip" >
              {{ `${header.codigo || '-'}-${header.nro_registro || '-'}` }}
            </span>
      </div>
      <div class="col-12 col-md-6">
        <q-icon name="schedule" class="q-mr-sm" color="blue" />
        <span class="text-bold">TIEMPO: </span>
        <q-chip class="line clip" color="blue" text-color="white" size="xs">
          {{ tiempoTranscurrido }}
        </q-chip>
        <span class="text-bold">FECHA PRE ANALITICA: </span>
        <span class="line clip">
          {{ header.fecha_envio_analitica}}
        </span>
<!--        <pre>{{header}}</pre>-->
<!--        {{fecha_fin}}-->
      </div>
    </div>
  </q-card-section>
  <q-card-section class="q-pa-xs">
    <div class="text-h6">Servicios solicitados:</div>
    <div class="q-mt-xs">
      <q-chip
        v-for="servicio in header?.servicios"
        :key="servicio.id"
        class="q-mr-sm q-mb-sm"
        color="blue"
        text-color="white"
        size="sm"
        outline
      >
        {{ servicio.nombre }}
        <span>
          ({{servicio.area?.name}})
        </span>
      </q-chip>
<!--      <pre>{{header?.servicios}}</pre>-->
    </div>
  </q-card-section>
</template>
<script>
import moment from 'moment';
export default {
  name: 'InfoServicio',
  props: {
    header: {
      type: Object,
      required: false,
      default: null,
    },
    fecha_fin: {
      type: String,
      required: false,
      default: null,
    },
  },
  mounted() {
    // console.log('fecha_fin', this.fecha_fin);
  },
  computed: {
    tiempoTranscurrido() {
      if (!this.header || !this.header.fecha_solicitud) {
        return '-';
      }
      const fechaSolicitud = moment(this.header.fecha_creacion);
      const ahora = moment(this.fecha_fin || new Date());
      const duracion = moment.duration(ahora.diff(fechaSolicitud));

      const dias = duracion.days();
      const horas = duracion.hours();
      const minutos = duracion.minutes();

      let resultado = '';
      if (dias > 0) {
        resultado += `${dias}d `;
      }
      if (horas > 0) {
        resultado += `${horas}h `;
      }
      if (minutos > 0) {
        resultado += `${minutos}m`;
      }

      return resultado.trim() || '0m';
    },
  },
};
</script>
