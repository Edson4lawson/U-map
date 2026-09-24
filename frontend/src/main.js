import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import './style.css'
import 'leaflet/dist/leaflet.css'
import 'aos/dist/aos.css'
import AOS from 'aos'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)



app.mount('#app')

// Initialize AOS
AOS.init({
  duration: 800,
  easing: 'ease-out-cubic',
  once: true,
  offset: 50,
})
