<template>
  <q-page class="flex flex-center">
    <div class="column items-center">
      <img
        alt="Quasar logo"
        src="~@/assets/quasar-logo-vertical.svg"
        style="width: 200px; height: 200px"
      />

      <q-btn
        class="q-mt-md"
        color="primary"
        to="/second"
        label="Go to Second Page"
        no-caps
      />
      <pre>{{datos}}</pre>
    </div>
  </q-page>
</template>

<script setup>
const datos = {
  nombre: 'Juan',
  apellido: 'Pérez',
  edad: 30,
  direccion: {
    calle: 'Calle Falsa',
    numero: 123,
    ciudad: 'Ciudad Ejemplo',
    pais: 'País Ejemplo'
  },
}
</script>
