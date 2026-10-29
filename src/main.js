import { createApp } from 'vue'
import './assets/style.css'
import App from './App.vue'
import router from './router'
import { initAuth } from './utils/api'

initAuth().then(() => {
  createApp(App)
    .use(router)
    .mount('#app')
})
