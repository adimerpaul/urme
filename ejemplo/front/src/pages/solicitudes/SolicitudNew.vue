<template>
  <q-page class="q-pa-sm">
    <SolicitudForm/>
  </q-page>
</template>

<script>
import moment from 'moment'
import SolicitudForm from "pages/solicitudes/SolicitudForm.vue";

export default {
  name: 'SolicitudNew',
  components: {SolicitudForm},
}
</script>
