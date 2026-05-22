import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import App from './App.vue'
import router from './router'
import fr from './locales/fr.json'
import en from './locales/en.json'
import './style.css'
import 'leaflet/dist/leaflet.css'
import 'aos/dist/aos.css'
import AOS from 'aos'

const i18n = createI18n({
  legacy: false,
  locale: 'fr',
  fallbackLocale: 'en',
  messages: { fr, en }
})

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
