<template>
  <div class="login-root">
    <!-- Animated background -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Main container -->
    <div class="login-container">
      <!-- Brand section -->
      <div class="brand-section">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h1 class="brand-title">U-Map</h1>
        <p class="brand-subtitle">Connectez-vous pour continuer</p>
      </div>

      <!-- Login card -->
      <div class="login-card" :class="{ 'card-shake': shaking }">
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

        <!-- Magic Link Form -->
        <form v-if="useMagicLink" @submit.prevent="handleSendMagicLink" class="login-form">
          <div v-if="magicSent" class="bg-emerald-950/20 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm mb-6 text-center">
            Un e-mail contenant votre lien de connexion magique a été envoyé.
          </div>

          <div v-else class="form-group">
            <label class="form-label">Votre adresse e-mail</label>
            <div class="input-wrapper">
              <Icon icon="ph:envelope-fill" class="input-icon" />
              <input
                v-model="magicEmail"
                type="email"
                required
                placeholder="votre@email.com"
                class="form-input"
              />
            </div>
          </div>

          <button v-if="!magicSent" type="submit" :disabled="loading" class="submit-btn">
            <span v-if="loading" class="btn-content">
              <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
              Envoi du lien...
            </span>
            <span v-else class="btn-content">
              Recevoir un lien magique
              <Icon icon="ph:paper-plane-tilt-bold" class="btn-arrow" />
            </span>
          </button>

          <div class="register-prompt mt-6 text-center">
            <button @click="useMagicLink = false; magicSent = false" type="button" class="text-blue-400 hover:underline">
              Se connecter avec mot de passe
            </button>
          </div>
        </form>

        <!-- Standard Login form -->
        <form v-else @submit.prevent="handleLogin" class="login-form">
          <!-- Identifier field (email or username) -->
          <div class="form-group">
            <label class="form-label">E-mail ou nom d'utilisateur</label>
            <div class="input-wrapper">
              <Icon icon="ph:user-fill" class="input-icon" />
              <input
                v-model="identifier"
                type="text"
                required
                autocomplete="username"
                placeholder="votre@email.com ou pseudo"
                class="form-input"
                @focus="fieldFocused = 'identifier'"
                @blur="fieldFocused = null"
              />
            </div>
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
                autocomplete="current-password"
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
          </div>

          <!-- CAPTCHA Field if needed -->
          <div v-if="captchaRequired" class="form-group bg-slate-900/60 p-4 rounded-xl border border-slate-800">
            <label class="form-label text-yellow-400">Défi de sécurité (CAPTCHA)</label>
            <div class="text-sm text-slate-300 mb-2 font-semibold">{{ captchaQuestion }}</div>
            <div class="input-wrapper">
              <Icon icon="ph:shield-warning-fill" class="input-icon" />
              <input
                v-model="captchaAnswer"
                type="number"
                required
                placeholder="Votre réponse"
                class="form-input"
              />
            </div>
            <button type="button" @click="fetchCaptcha" class="text-xs text-blue-400 mt-2 hover:underline flex items-center gap-1">
              <Icon icon="ph:arrows-clockwise-bold" /> Générer un autre calcul
            </button>
          </div>

          <!-- Remember me & Forgot password -->
          <div class="form-options">
            <label class="checkbox-wrapper">
              <input v-model="remember" type="checkbox" class="checkbox-input" />
              <span class="checkbox-custom"></span>
              <span class="checkbox-label">Se souvenir de moi</span>
            </label>
            <router-link to="/forgot-password" class="forgot-link">
              Mot de passe oublié ?
            </router-link>
          </div>

          <!-- Submit button -->
          <button type="submit" :disabled="loading" class="submit-btn">
            <span v-if="loading" class="btn-content">
              <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
              Connexion...
            </span>
            <span v-else class="btn-content">
              Se connecter
              <Icon icon="ph:arrow-right-bold" class="btn-arrow" />
            </span>
          </button>

          <div class="register-prompt mt-6 text-center">
            <button @click="useMagicLink = true" type="button" class="text-blue-400 hover:underline">
              Se connecter sans mot de passe (Magic Link)
            </button>
          </div>
        </form>

        <!-- Divider -->
        <div class="divider">
          <span>ou continuer avec</span>
        </div>

        <!-- Social login -->
        <div class="social-login">
          <button type="button" class="social-btn google-btn" @click="handleSocialLogin('google')">
            <Icon icon="ph:google-logo-fill" />
            <span>Continuer avec Google</span>
          </button>
          <button type="button" class="social-btn github-btn" @click="handleSocialLogin('github')">
            <Icon icon="ph:github-logo-fill" />
            <span>GitHub</span>
          </button>
          <button type="button" class="social-btn apple-btn" @click="handleSocialLogin('apple')">
            <Icon icon="ph:apple-logo-fill" />
            <span>Apple</span>
          </button>
        </div>

        <!-- Register link -->
        <div class="register-prompt">
          <span>Vous n'avez pas de compte ?</span>
          <router-link to="/register" class="register-link">
            Créer un compte
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { useMeta } from '../composables/useMeta'
import errorHandler from '../services/errorHandler'

useMeta('Connexion', 'Connectez-vous à U-map pour accéder à la messagerie, vos lieux visités et les fonctionnalités du campus UAC.', { canonicalPath: '/login' })

const router = useRouter()

// Theme handling
const isDark = ref(false)

const updateTheme = () => {
  isDark.value = document.documentElement.classList.contains('dark')
  const loginRoot = document.querySelector('.login-root')
  if (loginRoot) {
    if (isDark.value) {
      loginRoot.classList.remove('light-mode')
    } else {
      loginRoot.classList.add('light-mode')
    }
  }
}

const handleThemeChange = () => {
  updateTheme()
}

// Watch for theme changes from DarkModeToggle
const observer = new MutationObserver(() => {
  updateTheme()
})

onMounted(() => {
  updateTheme()
  window.addEventListener('storage', handleThemeChange)
  window.addEventListener('theme-changed', handleThemeChange)
  
  // Observe changes on documentElement for dark class
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })
})

onUnmounted(() => {
  window.removeEventListener('storage', handleThemeChange)
  window.removeEventListener('theme-changed', handleThemeChange)
  observer.disconnect()
})

const identifier = ref('')
const password = ref('')
const showPassword = ref(false)
const remember = ref(false)
const loading = ref(false)
const error = ref('')
const shaking = ref(false)
const fieldFocused = ref(null)
const capsLockActive = ref(false)

// CAPTCHA
const captchaRequired = ref(false)
const captchaQuestion = ref('')
const captchaToken = ref('')
const captchaAnswer = ref('')

// Magic Link
const useMagicLink = ref(false)
const magicEmail = ref('')
const magicSent = ref(false)

// Google Sign-In initialization flag
const googleScriptLoaded = ref(false)

const fetchCaptcha = async () => {
  try {
    const data = await authService.getCaptcha()
    captchaQuestion.value = data.question
    captchaToken.value = data.captcha_token
    captchaAnswer.value = ''
  } catch (e) {
    console.error("Error loading captcha:", e)
  }
}

const handleLogin = async () => {
  error.value = ''
  loading.value = true
  
  try {
    const res = await authService.login(
      identifier.value,
      password.value,
      remember.value,
      captchaRequired.value ? captchaToken.value : null,
      captchaRequired.value ? captchaAnswer.value : null
    )
    if (res && res.two_factor_required) {
      router.push(`/otp-verification?temp_token=${res.temp_token}`)
      return
    }
    router.push('/')
  } catch (e) {
    error.value = e.message
    if (e.captcha_required) {
      captchaRequired.value = true
      fetchCaptcha()
    }
    shaking.value = true
    setTimeout(() => { shaking.value = false }, 600)
  } finally {
    loading.value = false
  }
}

const handleSendMagicLink = async () => {
  error.value = ''
  loading.value = true
  try {
    const res = await authService.sendMagicLink(magicEmail.value)
    magicSent.value = true
    if (res.magic_link) {
      console.log("Magic link simulation:", res.magic_link)
      errorHandler.info(`Simulation : Cliquez sur ce lien pour vous connecter : ${res.magic_link}`)
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

const checkCapsLock = (e) => {
  capsLockActive.value = e.getModifierState && e.getModifierState('CapsLock')
}

const handleSocialLogin = async (provider) => {
  error.value = ''

  if (provider === 'google') {
    const googleClientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
    if (!googleClientId) {
      errorHandler.info("La connexion via Google n'est pas encore activée (Google Client ID non configuré). Veuillez utiliser votre e-mail ou le Magic Link.")
      return
    }

    loading.value = true
    try {
      // Initialize Google Identity Services only once
      if (!googleScriptLoaded.value && !window.google?.accounts?.id) {
        const googleScript = document.createElement('script')
        googleScript.src = 'https://accounts.google.com/gsi/client'
        googleScript.async = true
        googleScript.defer = true
        document.head.appendChild(googleScript)

        googleScript.onload = () => {
          googleScriptLoaded.value = true
          initializeGoogleSignIn(googleClientId)
        }

        googleScript.onerror = () => {
          error.value = 'Erreur lors du chargement de Google Sign-In'
          loading.value = false
        }
      } else if (window.google?.accounts?.id) {
        // Script already loaded, just prompt
        initializeGoogleSignIn(googleClientId)
      }
    } catch (e) {
      error.value = e.message
      loading.value = false
    }
  } else if (provider === 'github') {
    errorHandler.info("La connexion via GitHub n'est pas encore disponible sur cette version. Veuillez utiliser votre adresse e-mail ou le Magic Link.")
  } else if (provider === 'apple') {
    errorHandler.info("La connexion via Apple n'est pas encore disponible sur cette version. Veuillez utiliser votre adresse e-mail ou le Magic Link.")
  }
}

const initializeGoogleSignIn = (clientId) => {
  window.google.accounts.id.initialize({
    client_id: clientId,
    callback: handleGoogleCredentialResponse,
    auto_select: false,
    cancel_on_tap_outside: true,
  })

  window.google.accounts.id.prompt((notification) => {
    if (notification.isNotDisplayed()) {
      error.value = 'Impossible d\'afficher Google Sign-In'
      loading.value = false
    } else if (notification.isSkipped()) {
      loading.value = false
    }
  })
}

const handleGoogleCredentialResponse = async (response) => {
  try {
    // Decode the JWT token to get user info
    const base64Url = response.credential.split('.')[1]
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
    const jsonPayload = decodeURIComponent(atob(base64).split('').map((c) => {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
    }).join(''))

    const userInfo = JSON.parse(jsonPayload)

    // Send to backend
    await authService.socialLogin('google', response.credential, userInfo.email, userInfo.name)
    router.push('/')
  } catch (e) {
    error.value = e.message
    loading.value = false
  }
}
</script>

<style scoped>
/* Root */
.login-root {
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
  .login-root {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  }
}

.login-root.light-mode {
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
.login-container {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 420px;
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

.login-root.light-mode .brand-title {
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

.login-root.light-mode .brand-subtitle {
  color: rgba(71,85,105,0.7);
}

/* Card */
.login-card {
  width: 100%;
  background: rgba(15,23,42,0.8);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.3);
}

.login-root.light-mode .login-card {
  background: rgba(255,255,255,0.9);
  border-color: rgba(99,102,241,0.3);
  box-shadow: 0 25px 50px rgba(0,0,0,0.1);
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
.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(148,163,184,0.8);
}

.login-root.light-mode .form-label {
  color: rgba(71,85,105,0.8);
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
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

.login-root.light-mode .form-input {
  background: rgba(248,250,252,0.8);
  border-color: rgba(99,102,241,0.3);
  color: #1e293b;
}

.login-root.light-mode .form-input::placeholder {
  color: rgba(71,85,105,0.4);
}

.form-input:focus {
  border-color: rgba(99,102,241,0.5);
  background: rgba(99,102,241,0.05);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.form-input:focus + .input-icon {
  color: #818cf8;
}

.password-toggle {
  position: absolute;
  right: 12px;
  color: rgba(148,163,184,0.6);
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  transition: color 0.2s;
  z-index: 10;
  font-size: 18px;
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

/* Options */
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
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

.checkbox-label {
  font-size: 13px;
  color: rgba(148,163,184,0.7);
}

.login-root.light-mode .checkbox-label {
  color: rgba(71,85,105,0.7);
}

.forgot-link {
  font-size: 13px;
  color: #818cf8;
  text-decoration: none;
  transition: color 0.2s;
}

.forgot-link:hover { color: #a5b4fc; }

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

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
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

/* Social login */
.social-login {
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

.google-btn {
  border-color: rgba(16,185,129,0.3);
  background: rgba(16,185,129,0.08);
  color: #10b981;
}

.google-btn:hover {
  border-color: rgba(16,185,129,0.6);
  background: rgba(16,185,129,0.18);
  color: #34d399;
  box-shadow: 0 4px 15px rgba(16,185,129,0.25);
}

.social-btn span {
  font-size: 11px;
}

/* Register prompt */
.register-prompt {
  text-align: center;
  font-size: 13px;
  color: rgba(148,163,184,0.6);
  margin-top: 8px;
}

.login-root.light-mode .register-prompt {
  color: rgba(71,85,105,0.6);
}

.register-link {
  color: #818cf8;
  text-decoration: none;
  font-weight: 600;
  margin-left: 4px;
  transition: color 0.2s;
}

.register-link:hover { color: #a5b4fc; }

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

.login-root.light-mode .back-home {
  color: rgba(71,85,105,0.5);
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
  .login-container {
    padding: 16px;
  }

  .login-card {
    padding: 24px;
  }

  .social-login {
    gap: 8px;
  }

  .social-btn {
    padding: 10px 6px;
  }
}
</style>
