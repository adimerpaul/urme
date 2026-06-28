<template>
  <q-page class="q-pa-md flex flex-center bg-grey-2">
    <q-card style="width: min(96vw, 420px)">
      <q-card-section class="row items-center q-pb-none">
        <q-icon name="lock_reset" size="22px" class="text-primary q-mr-sm"/>
        <span class="text-h6 text-weight-bold">Cambiar contraseña</span>
      </q-card-section>

      <q-card-section>
        <q-form @submit.prevent="cambiar">
          <q-input
            v-model="form.password_actual"
            label="Contraseña actual"
            dense outlined
            :type="showActual ? 'text' : 'password'"
            :rules="[v => !!v || 'Campo requerido']"
            class="q-mb-sm"
          >
            <template #append>
              <q-icon :name="showActual ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                      @click="showActual = !showActual"/>
            </template>
          </q-input>

          <q-input
            v-model="form.password_nuevo"
            label="Nueva contraseña"
            dense outlined
            :type="showNuevo ? 'text' : 'password'"
            :rules="[v => !!v || 'Campo requerido', v => v.length >= 6 || 'Mínimo 6 caracteres']"
            class="q-mb-sm"
          >
            <template #append>
              <q-icon :name="showNuevo ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                      @click="showNuevo = !showNuevo"/>
            </template>
          </q-input>

          <q-input
            v-model="form.password_nuevo_confirmation"
            label="Confirmar nueva contraseña"
            dense outlined
            :type="showConfirm ? 'text' : 'password'"
            :rules="[
              v => !!v || 'Campo requerido',
              v => v === form.password_nuevo || 'Las contraseñas no coinciden'
            ]"
            class="q-mb-sm"
          >
            <template #append>
              <q-icon :name="showConfirm ? 'visibility_off' : 'visibility'" class="cursor-pointer"
                      @click="showConfirm = !showConfirm"/>
            </template>
          </q-input>

          <div class="text-right q-mt-md">
            <q-btn flat color="grey-7" label="Cancelar" no-caps @click="$router.back()" class="q-mr-sm"/>
            <q-btn color="primary" label="Cambiar contraseña" type="submit" no-caps :loading="loading" icon="lock_reset"/>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script>
export default {
  name: 'CambiarPasswordPage',
  data() {
    return {
      loading: false,
      showActual: false,
      showNuevo: false,
      showConfirm: false,
      form: {
        password_actual: '',
        password_nuevo: '',
        password_nuevo_confirmation: '',
      },
    }
  },
  methods: {
    async cambiar() {
      this.loading = true
      try {
        await this.$axios.put('cambiar-password', this.form)
        this.$alert.success('Contraseña actualizada correctamente')
        this.$router.push('/')
      } catch (e) {
        this.$alert.error(e.response?.data?.message || 'Error al cambiar contraseña')
      } finally {
        this.loading = false
      }
    },
  },
}
</script>
