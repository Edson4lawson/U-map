<template>
  <div class="register-root">
    <!-- Animated background -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Main container -->
    <div class="register-container">
      <!-- Brand section -->
      <div class="brand-section">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
        </div>
        <h1 class="brand-title">Créer un compte</h1>
        <p class="brand-subtitle">Rejoignez la communauté U-Map</p>
      </div>

      <!-- Register card -->
      <div class="register-card" :class="{ 'card-shake': shaking }">
        <!-- Error message -->
        <transition name="slide-error">
          <div v-if="error" class="error-banner">
            <Icon icon="ph:warning-circle-fill" class="error-icon" />
            <span class="error-text">{{ error }}</span>
            <button @click="error = ''" class="error-close">
              <Icon icon="ph:x-bold" />
            </button>
          </div>
        </transition>

        <!-- Register form -->
        <form @submit.prevent="handleRegister" class="register-form">
          <!-- Username field -->
          <div class="form-group">
            <label class="form-label">Nom d'utilisateur</label>
            <div class="input-wrapper" :class="{ 'input-success': usernameStatus === 'available', 'input-error': usernameStatus === 'taken' }">
              <Icon icon="ph:user-fill" class="input-icon" />
              <input
                v-model="username"
                type="text"
                required
                minlength="3"
                maxlength="30"
                autocomplete="username"
                placeholder="pseudo"
                class="form-input"
                @input="debounceUsernameCheck"
                @focus="fieldFocused = 'username'"
                @blur="fieldFocused = null"
              />
              <div class="input-status">
                <Icon v-if="usernameStatus === 'checking'" icon="ph:spinner-gap-bold" class="spin-icon" />
                <Icon v-else-if="usernameStatus === 'available'" icon="ph:check-circle-fill" class="success-icon" />
                <Icon v-else-if="usernameStatus === 'taken'" icon="ph:x-circle-fill" class="error-icon-small" />
              </div>
            </div>
            <transition name="fade">
              <span v-if="usernameStatus === 'taken'" class="field-error">{{ usernameInvalidError || "Ce nom d'utilisateur est déjà pris." }}</span>
              <span v-else-if="usernameStatus === 'available'" class="field-success">Ce nom d'utilisateur est disponible.</span>
            </transition>
          </div>

          <!-- Email field -->
          <div class="form-group">
            <label class="form-label">Adresse e-mail</label>
            <div class="input-wrapper" :class="{ 'input-success': emailStatus === 'available', 'input-error': emailStatus === 'taken' }">
              <Icon icon="ph:envelope-fill" class="input-icon" />
              <input
                v-model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="votre@email.com"
                class="form-input"
                @input="debounceEmailCheck"
                @focus="fieldFocused = 'email'"
                @blur="fieldFocused = null"
              />
              <div class="input-status">
                <Icon v-if="emailStatus === 'checking'" icon="ph:spinner-gap-bold" class="spin-icon" />
                <Icon v-else-if="emailStatus === 'available'" icon="ph:check-circle-fill" class="success-icon" />
                <Icon v-else-if="emailStatus === 'taken'" icon="ph:x-circle-fill" class="error-icon-small" />
              </div>
            </div>
            <transition name="fade">
              <span v-if="emailStatus === 'taken'" class="field-error">Cette adresse e-mail est déjà utilisée.</span>
              <span v-else-if="emailStatus === 'available'" class="field-success">Cette adresse e-mail est disponible.</span>
            </transition>
          </div>

          <!-- Password field -->
          <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <div class="input-wrapper">
              <Icon icon="ph:lock-key-fill" class="input-icon" />
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                minlength="8"
                autocomplete="new-password"
                placeholder="••••••••"
                class="form-input"
                @focus="fieldFocused = 'password'"
                @blur="fieldFocused = null"
                @keyup="checkCapsLock"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="password-toggle"
                :title="showPassword ? 'Masquer' : 'Afficher'"
              >
                <Icon :icon="showPassword ? 'ph:eye-slash-fill' : 'ph:eye-fill'" />
              </button>
            </div>
            
            <!-- Caps Lock warning -->
            <transition name="fade">
              <div v-if="capsLockActive" class="caps-lock-warning">
                <Icon icon="ph:keyboard-fill" />
                <Icon icon="ph:warning-bold" />
                <span>Verr. Maj. activée</span>
              </div>
            </transition>

            <!-- Password strength indicator -->
            <div v-if="password.length > 0" class="strength-section">
              <div class="strength-bars">
                <div
                  v-for="i in 4"
                  :key="i"
                  class="strength-bar"
                  :class="strengthBarClass(i)"
                ></div>
              </div>
              <span class="strength-label" :class="strengthLabelClass">{{ strengthLabel }}</span>
            </div>

            <!-- Password suggestions -->
            <transition name="fade">
              <div v-if="passwordSuggestions.length > 0" class="password-suggestions">
                <div class="suggestions-title">💡 Suggestions pour renforcer :</div>
                <ul class="suggestions-list">
                  <li v-for="suggestion in passwordSuggestions" :key="suggestion" class="suggestion-item">
                    {{ suggestion }}
                  </li>
                </ul>
              </div>
            </transition>
          </div>

          <!-- Confirm password field -->
          <div class="form-group">
            <label class="form-label">Confirmer le mot de passe</label>
            <div class="input-wrapper" :class="{ 'input-success': passwordMatch === 'match', 'input-error': passwordMatch === 'mismatch' }">
              <Icon icon="ph:lock-key-fill" class="input-icon" />
              <input
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="••••••••"
                class="form-input"
                @focus="fieldFocused = 'confirmPassword'"
                @blur="fieldFocused = null"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="password-toggle"
                :title="showConfirmPassword ? 'Masquer' : 'Afficher'"
              >
                <Icon :icon="showConfirmPassword ? 'ph:eye-slash-fill' : 'ph:eye-fill'" />
              </button>
            </div>
            <transition name="fade">
              <span v-if="passwordMatch === 'mismatch'" class="field-error">Les mots de passe ne correspondent pas.</span>
              <span v-else-if="passwordMatch === 'match'" class="field-success">Les mots de passe correspondent.</span>
            </transition>
          </div>

          <!-- Terms checkbox -->
          <div class="form-group">
            <label class="checkbox-wrapper">
              <input v-model="acceptTerms" type="checkbox" required class="checkbox-input" />
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">
                J'accepte les <router-link to="/terms-of-service" class="link">conditions d'utilisation</router-link> et la <router-link to="/privacy-policy" class="link">politique de confidentialité</router-link>
              </span>
            </label>
          </div>

          <!-- Submit button -->
          <button
            type="submit"
            :disabled="loading || !canSubmit"
            class="submit-btn"
          >
            <span v-if="loading" class="btn-content">
              <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
              Création du compte...
            </span>
            <span v-else class="btn-content">
              Créer mon compte
              <Icon icon="ph:arrow-right-bold" class="btn-arrow" />
            </span>
          </button>
        </form>

        <!-- Divider -->
        <div class="divider">
          <span>ou s'inscrire avec</span>
        </div>

        <!-- Social register -->
        <div class="social-register">
          <button type="button" class="social-btn google-btn" @click="handleSocialRegister('google')">
            <Icon icon="ph:google-logo-fill" />
            <span>Google</span>
          </button>
          <button type="button" class="social-btn github-btn" @click="handleSocialRegister('github')">
            <Icon icon="ph:github-logo-fill" />
            <span>GitHub</span>
          </button>
          <button type="button" class="social-btn apple-btn" @click="handleSocialRegister('apple')">
            <Icon icon="ph:apple-logo-fill" />
            <span>Apple</span>
          </button>
        </div>

        <!-- Login link -->
        <div class="login-prompt">
          <span>Vous avez déjà un compte ?</span>
          <router-link to="/login" class="login-link">
            Se connecter
          </router-link>
        </div>
      </div>

      <!-- Back to home -->
      <router-link to="/" class="back-home">
        <Icon icon="ph:house-fill" />
        Retour à l'accueil
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { useMeta } from '../composables/useMeta'
import errorHandler from '../services/errorHandler'

useMeta('Inscription', "Créez votre compte U-map et rejoignez la communauté étudiante du campus de l'UAC à Abomey-Calavi.", { canonicalPath: '/register' })

const router = useRouter()

const username = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const acceptTerms = ref(false)
const loading = ref(false)
const error = ref('')
const shaking = ref(false)
const fieldFocused = ref(null)
const capsLockActive = ref(false)

const usernameStatus = ref('') // '', 'checking', 'available', 'taken'
const emailStatus = ref('') // '', 'checking', 'available', 'taken'

let usernameCheckTimeout = null
let emailCheckTimeout = null

// Password strength
const strengthLevel = computed(() => {
  const p = password.value
  let score = 0
  
  if (p.length >= 8) score++
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  
  return Math.min(score, 4)
})

const strengthLabel = computed(() => {
  const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Fort']
  return labels[strengthLevel.value]
})

const strengthLabelClass = computed(() => {
  const classes = ['', 'text-red-400', 'text-orange-400', 'text-yellow-400', 'text-emerald-400']
  return classes[strengthLevel.value]
})

const strengthBarClass = (i) => {
  if (i > strengthLevel.value) return 'bar-empty'
  const classes = ['', 'bar-red', 'bar-orange', 'bar-yellow', 'bar-green']
  return classes[strengthLevel.value]
}

const passwordSuggestions = computed(() => {
  const suggestions = []
  const p = password.value
  
  if (p.length < 8) suggestions.push('Au moins 8 caractères')
  if (p.length < 12) suggestions.push('12 caractères ou plus pour plus de sécurité')
  if (!/[A-Z]/.test(p)) suggestions.push('Ajoutez des majuscules')
  if (!/[0-9]/.test(p)) suggestions.push('Ajoutez des chiffres')
  if (!/[^A-Za-z0-9]/.test(p)) suggestions.push('Ajoutez des caractères spéciaux (!@#$%)')
  
  return suggestions
})

// Password match
const passwordMatch = computed(() => {
  if (!confirmPassword.value) return ''
  return password.value === confirmPassword.value ? 'match' : 'mismatch'
})

// Can submit
const canSubmit = computed(() => {
  return (
    username.value.length >= 3 &&
    email.value.includes('@') &&
    password.value.length >= 8 &&
    password.value === confirmPassword.value &&
    usernameStatus.value !== 'taken' &&
    emailStatus.value !== 'taken' &&
    acceptTerms.value
  )
})

// Debounced checks
const usernameInvalidError = ref('')

const debounceUsernameCheck = () => {
  clearTimeout(usernameCheckTimeout)
  usernameInvalidError.value = ''
  
  // Sanitize / validate special characters: only alphanumeric, underscore, hyphen
  const validRegex = /^[a-zA-Z0-9_-]*$/
  if (!validRegex.test(username.value)) {
    usernameStatus.value = 'taken'
    usernameInvalidError.value = 'Seuls les lettres, chiffres, tirets et underscores sont autorisés.'
    return
  }

  if (username.value.length < 3) {
    usernameStatus.value = ''
    return
  }
  usernameStatus.value = 'checking'
  usernameCheckTimeout = setTimeout(checkUsernameAvailability, 500)
}

const debounceEmailCheck = () => {
  clearTimeout(emailCheckTimeout)
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.value)) {
    emailStatus.value = ''
    return
  }
  emailStatus.value = 'checking'
  emailCheckTimeout = setTimeout(checkEmailAvailability, 500)
}

const checkUsernameAvailability = async () => {
  try {
    const response = await fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/check-username`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: username.value })
    })
    const data = await response.json()
    usernameStatus.value = data.available ? 'available' : 'taken'
  } catch (e) {
    usernameStatus.value = ''
  }
}

const checkEmailAvailability = async () => {
  try {
    const response = await fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/check-email`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value })
    })
    const data = await response.json()
    emailStatus.value = data.available ? 'available' : 'taken'
  } catch (e) {
    emailStatus.value = ''
  }
}

const checkCapsLock = (e) => {
  capsLockActive.value = e.getModifierState && e.getModifierState('CapsLock')
}

const handleRegister = async () => {
  error.value = ''
  loading.value = true
  
  try {
    await authService.register({
      name: username.value,
      email: email.value,
      password: password.value
    })
    router.push('/')
  } catch (e) {
    error.value = e.message
    shaking.value = true
    setTimeout(() => { shaking.value = false }, 600)
  } finally {
    loading.value = false
  }
}

const handleSocialRegister = async (provider) => {
  if (provider === 'google') {
    const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
    if (!googleClientId) {
      errorHandler.info("L'inscription via Google nécessite la configuration du Google Client ID. Veuillez utiliser le formulaire ci-dessus.")
      return
    }
    
    loading.value = true
    try {
      if (!window.google?.accounts?.id) {
        await new Promise((resolve, reject) => {
          const script = document.createElement('script')
          script.src = 'https://accounts.google.com/gsi/client'
          script.onload = resolve
          script.onerror = reject
          document.head.appendChild(script)
        })
      }

      window.google.accounts.id.initialize({
        client_id: googleClientId,
        callback: async (response) => {
          try {
            const payload = JSON.parse(atob(response.credential.split('.')[1]))
            await authService.socialLogin({
              provider: 'google',
              token: response.credential,
              email: payload.email,
              name: payload.name || payload.email.split('@')[0],
            })
            router.push('/')
          } catch (err) {
            error.value = err.message || "Erreur lors de l'inscription via Google"
          } finally {
            loading.value = false
          }
        }
      })

      window.google.accounts.id.prompt()
    } catch (e) {
      error.value = "Impossible de charger l'authentification Google"
      loading.value = false
    }
  } else {
    const name = provider === 'github' ? 'GitHub' : 'Apple'
    errorHandler.info(`L'inscription via ${name} n'est pas encore disponible. Veuillez utiliser le formulaire e-mail ci-dessus.`)
  }
}
</script>

<style scoped>
/* Root */
.register-root {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', system-ui, sans-serif;
  padding: 20px;
}

@media (prefers-color-scheme: light) {
  .register-root {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  }
}

.register-root.light-mode {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Background */
.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
  background-size: 40px 40px;
  animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
  0% { transform: translateY(0); }
  100% { transform: translateY(40px); }
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  animation: orbFloat 8s ease-in-out infinite;
  pointer-events: none;
}

.orb-1 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(99,102,241,0.2), transparent 70%);
  top: -100px;
  left: -100px;
}

.orb-2 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(139,92,246,0.15), transparent 70%);
  bottom: -80px;
  right: -80px;
  animation-delay: -4s;
}

@keyframes orbFloat {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

/* Container */
.register-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 460px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

/* Brand */
.brand-section {
  text-align: center;
}

.brand-logo {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 20px 40px rgba(99,102,241,0.3);
}

.brand-logo svg {
  width: 32px;
  height: 32px;
  color: white;
}

.brand-title {
  font-size: 28px;
  font-weight: 800;
  color: white;
  margin: 0 0 8px;
  letter-spacing: -0.5px;
}

@media (prefers-color-scheme: light) {
  .brand-title {
    color: #1e293b;
  }
}

.register-root.light-mode .brand-title {
  color: #1e293b;
}

.brand-subtitle {
  font-size: 14px;
  color: rgba(148,163,184,0.7);
  margin: 0;
}

@media (prefers-color-scheme: light) {
  .brand-subtitle {
    color: rgba(71,85,105,0.7);
  }
}

.register-root.light-mode .brand-subtitle {
  color: rgba(71,85,105,0.7);
}

/* Card */
.register-card {
  width: 100%;
  background: rgba(15,23,42,0.8);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.3);
}

.card-shake {
  animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  15% { transform: translateX(-8px); }
  30% { transform: translateX(8px); }
  45% { transform: translateX(-6px); }
  60% { transform: translateX(6px); }
  75% { transform: translateX(-3px); }
  90% { transform: translateX(3px); }
}

/* Error banner */
.error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 12px;
  margin-bottom: 20px;
}

.error-icon {
  font-size: 18px;
  color: #f87171;
  flex-shrink: 0;
}

.error-text {
  flex: 1;
  font-size: 13px;
  color: #fca5a5;
}

.error-close {
  color: rgba(239,68,68,0.5);
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s;
}

.error-close:hover { color: #f87171; }

/* Form */
.register-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(148,163,184,0.8);
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  transition: border-color 0.3s;
}

.input-wrapper.input-success {
  border-color: rgba(34,197,94,0.5);
}

.input-wrapper.input-error {
  border-color: rgba(239,68,68,0.5);
}

.input-icon {
  position: absolute;
  left: 14px;
  color: rgba(99,102,241,0.5);
  font-size: 18px;
  pointer-events: none;
  transition: color 0.3s;
}

.form-input {
  width: 100%;
  background: rgba(15,23,42,0.6);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 12px;
  padding: 14px 14px 14px 48px;
  font-size: 14px;
  color: white;
  outline: none;
  transition: all 0.3s ease;
}

.form-input::placeholder {
  color: rgba(148,163,184,0.3);
}

.form-input:focus {
  border-color: rgba(99,102,241,0.5);
  background: rgba(99,102,241,0.05);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.input-status {
  position: absolute;
  right: 12px;
  display: flex;
  align-items: center;
}

.spin-icon {
  animation: spin 1s linear infinite;
  color: rgba(99,102,241,0.5);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.success-icon {
  color: #22c55e;
}

.error-icon-small {
  color: #ef4444;
}

.field-error {
  font-size: 11px;
  color: #f87171;
}

.field-success {
  font-size: 11px;
  color: #34d399;
}

.password-toggle {
  position: absolute;
  right: 12px;
  color: rgba(148,163,184,0.9);
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  transition: color 0.2s;
  z-index: 20;
  font-size: 20px;
  opacity: 1;
}

.password-toggle:hover { color: #818cf8; }

.caps-lock-warning {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #fbbf24;
  margin-top: 4px;
}

/* Strength indicator */
.strength-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 4px;
}

.strength-bars {
  display: flex;
  gap: 4px;
  flex: 1;
}

.strength-bar {
  flex: 1;
  height: 3px;
  border-radius: 100px;
  transition: all 0.4s ease;
}

.bar-empty { background: rgba(255,255,255,0.08); }
.bar-red { background: #ef4444; }
.bar-orange { background: #f97316; }
.bar-yellow { background: #eab308; }
.bar-green { background: #22c55e; }

.strength-label {
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.text-red-400 { color: #f87171; }
.text-orange-400 { color: #fb923c; }
.text-yellow-400 { color: #facc15; }
.text-emerald-400 { color: #34d399; }

/* Password suggestions */
.password-suggestions {
  margin-top: 8px;
  padding: 12px;
  background: rgba(99,102,241,0.05);
  border-radius: 8px;
}

.suggestions-title {
  font-size: 11px;
  font-weight: 600;
  color: rgba(148,163,184,0.7);
  margin-bottom: 6px;
}

.suggestions-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.suggestion-item {
  font-size: 11px;
  color: rgba(148,163,184,0.6);
  padding: 2px 0;
  padding-left: 12px;
  position: relative;
}

.suggestion-item::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #818cf8;
}

/* Checkbox */
.checkbox-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  cursor: pointer;
  font-size: 12px;
  color: rgba(148,163,184,0.7);
}

.checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(99,102,241,0.3);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-top: 1px;
}

.checkbox-input:checked + .checkbox-custom {
  background: #6366f1;
  border-color: #6366f1;
}

.checkbox-input:checked + .checkbox-custom::after {
  content: '✓';
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.link {
  color: #818cf8;
  text-decoration: none;
  transition: color 0.2s;
}

.link:hover { color: #a5b4fc; }

/* Submit button */
.submit-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 8px;
}

.submit-btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(99,102,241,0.3);
}

.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-arrow {
  transition: transform 0.3s;
}

.submit-btn:hover .btn-arrow {
  transform: translateX(4px);
}

/* Divider */
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 8px 0;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(99,102,241,0.15);
}

.divider span {
  font-size: 12px;
  color: rgba(148,163,184,0.5);
  font-weight: 500;
}

/* Social register */
.social-register {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.social-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 10px;
  background: rgba(15,23,42,0.4);
  color: rgba(148,163,184,0.7);
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.social-btn:hover {
  border-color: rgba(99,102,241,0.4);
  background: rgba(99,102,241,0.05);
  color: white;
}

.social-btn span {
  font-size: 11px;
}

/* Login prompt */
.login-prompt {
  text-align: center;
  font-size: 13px;
  color: rgba(148,163,184,0.6);
  margin-top: 8px;
}

.login-link {
  color: #818cf8;
  text-decoration: none;
  font-weight: 600;
  margin-left: 4px;
  transition: color 0.2s;
}

.login-link:hover { color: #a5b4fc; }

/* Back home */
.back-home {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(148,163,184,0.5);
  text-decoration: none;
  transition: color 0.3s;
}

.back-home:hover { color: #818cf8; }

/* Transitions */
.slide-error-enter-active,
.slide-error-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-error-enter-from,
.slide-error-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 480px) {
  .register-container {
    padding: 16px;
  }

  .register-card {
    padding: 24px;
  }

  .social-register {
    gap: 8px;
  }

  .social-btn {
    padding: 10px 6px;
  }
}
</style>
