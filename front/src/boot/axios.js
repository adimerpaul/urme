import {boot} from 'quasar/wrappers'
import axios from 'axios'
import {Alert} from "../addons/Alert";
import {useCounterStore} from "../stores/example-store";
import moment from "moment";
import VueApexCharts from "vue3-apexcharts";

import {computed} from "vue";
// Be careful when using SSR for cross-request state pollution
// due to creating a Singleton instance here;
// If any client changes this (global) instance, it might be a
// good idea to move this instance creation inside of the
// "export default () => {}" function below (which runs individually
// for each client)
const api = axios.create({ baseURL: 'https://api.example.com' })

export default boot(({ app, router }) => {
  app.use(VueApexCharts);
  // for use inside Vue files (Options API) through this.$axios and this.$api
  // app.config.globalProperties.$socket = io(import.meta.env.VITE_API_SOCKET)
  console.log('VITE_API_BACK', import.meta.env.VITE_API_BACK)

  app.config.globalProperties.$axios = axios.create({ baseURL: import.meta.env.VITE_API_BACK })
  // console.log(import.meta.env.VITE_API_BACK)
  app.config.globalProperties.$alert = Alert
  app.config.globalProperties.$store = useCounterStore()
  app.config.globalProperties.$url = import.meta.env.VITE_API_BACK
  app.config.globalProperties.$imgBase = (import.meta.env.VITE_API_BACK || '').replace(/\/api\/?$/, '')
  app.config.globalProperties.$version = import.meta.env.VITE_VERSION
  app.config.globalProperties.$filters = {
    dateDmYHis (value) {
      const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic']
      const mes = meses[moment(String(value)).format('MM') - 1]
      if (!value) return ''
      return moment(String(value)).format('DD') + ' ' + mes + ' ' + moment(String(value)).format('YYYY') + ' ' + moment(String(value)).format('hh:mm A')
    },
    date: (value) => {
      if (!value) return ''
      return new Date(value).toLocaleDateString()
    },
    time: (value) => {
      if (!value) return ''
      return new Date(value).toLocaleTimeString()
    },
    textCapitalize: (value) => {
      if (!value) return ''
      const lower = value.toLowerCase()
      return lower.charAt(0).toUpperCase() + lower.slice(1)
    },
    color(role) {
      if (role === 'Administrador') return 'red'
      if (role === 'Almacén' || role === 'Almacen') return 'teal'
      if (role === 'Docente') return 'green'
      if (role === 'Estudiante') return 'blue'
      return 'grey'
    },
    colorAgencia(agencia) {
      if (agencia === 'Oasis') return 'indigo'
      if (agencia === 'Clinica') return 'info'
      return 'red'
    }
  }
  const token = localStorage.getItem('tokenSil')
  if (token) {
    app.config.globalProperties.$axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    app.config.globalProperties.$axios.get('me').then(response => {
      useCounterStore().isLogged = true
      useCounterStore().user = response.data
      const perms = (response.data.permissions || []).map(p => p.name)
      useCounterStore().permissions = perms
      localStorage.setItem('user', JSON.stringify(response.data))
      localStorage.setItem('permissionsSil', JSON.stringify(perms))
      // useCounterStore().permissions = response.data.permissions
    }).catch(error => {
      console.log(error)
      router.push('/login')
      localStorage.removeItem('tokenSil')
      localStorage.removeItem('permissionsSil')
      useCounterStore().isLogged = false
      useCounterStore().permissions = []
      useCounterStore().user = {}
    })
  }
  app.config.globalProperties.$api = api
  // ^ ^ ^ this will allow you to use this.$api (for Vue Options API form)
  //       so you can easily perform requests against your app's API
})

export { api }
