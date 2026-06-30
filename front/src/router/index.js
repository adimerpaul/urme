import { defineRouter } from '#q-app'
import { routes, handleHotUpdate } from 'vue-router/auto-routes'
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router'

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory)

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE),
  })

  Router.beforeEach((to) => {
    if (to.path === '/login') return true
    const token = localStorage.getItem('tokenUrme')
    if (!token) return '/login'
    return true
  })

  if (import.meta.hot) {
    handleHotUpdate(Router)
  }

  return Router
})
