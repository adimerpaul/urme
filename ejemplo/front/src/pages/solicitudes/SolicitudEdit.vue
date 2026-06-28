<template>
  <q-page class="q-pa-sm">
    <SolicitudForm :solicitudProp="solicitud" v-if="solicitud.id"/>
  </q-page>
</template>

<script>
import moment from 'moment'
import SolicitudForm from "pages/solicitudes/SolicitudForm.vue";

export default {
  name: 'SolicitudEdit',
  components: {SolicitudForm},
  data() {
    return {
      solicitudId: null,
      solicitud: {},
    }
  },
  mounted() {
    this.solicitudId = this.$route.params.id
    console.log('Solicitud ID from route params:', this.solicitudId)
    this.solicitudGet()
  },
  methods: {
    async solicitudGet() {
      try {
        const response = await this.$axios.get(`/solicitudes/${this.solicitudId}`)
        this.solicitud = response.data
      } catch (error) {
        console.error('Error fetching solicitud:', error)
      }
    }
  },
}
</script>
