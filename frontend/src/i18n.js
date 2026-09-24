import { createI18n } from 'vue-i18n'
import fr from './locales/fr.json'
import en from './locales/en.json'

const savedLocale = localStorage.getItem('u_map_lang') || 'fr'

const i18n = createI18n({
  legacy: false, // You must use Composition API for Vue 3
  locale: savedLocale,
  fallbackLocale: 'fr',
  messages: {
    fr,
    en
  }
})

export default i18n
