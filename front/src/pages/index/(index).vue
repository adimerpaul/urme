<template>
  <q-page class="q-pa-md">
    <div class="text-h6 q-mb-md">Bienvenido, {{ $store.user.name }}</div>
    <div class="row q-col-gutter-md">
      <div class="col-12 col-sm-6 col-md-4">
        <q-card flat bordered>
          <q-card-section class="row items-center q-col-gutter-sm">
            <q-icon name="people" color="primary" size="40px" />
            <div>
              <div class="text-subtitle2 text-grey-7">Módulo activo</div>
              <div class="text-h6">Usuarios</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { getCurrentInstance } from 'vue'
const { proxy } = getCurrentInstance()
</script>
