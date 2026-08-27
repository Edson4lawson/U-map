<template>
  <div class="login-root">
    <!-- Animated background -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="login-container">
      <div class="brand-section">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h1 class="brand-title">Double Facteur</h1>
        <p class="brand-subtitle">Entrez le code de vérification pour continuer</p>
      </div>

      <div class="login-card">
        <!-- Error message -->
        <transition name="slide-error">
          <div v-if="error" class="error-banner">
            <Icon icon="ph:warning-circle-fill" class="error-icon" />
            <span class="error-text">{{ error }}</span>
          </div>
        </transition>

        <form @submit.prevent="handleSubmit" class="login-form">
          <div v-if="!useRecoveryCode" class="form-group">
            <label class="form-label text-center block mb-4">Code de sécurité à 6 chiffres</label>
            <div class="otp-inputs-wrapper flex justify-center gap-2">
              <input
                v-for="(digit, idx) in otpDigits"
                :key="idx"
                ref="inputs"
                v-model="otpDigits[idx]"
                type="text"
                maxlength="1"
                class="otp-digit-input text-center w-12 h-14 border rounded-lg text-xl font-bold bg-white/10 text-white focus:border-blue-500 focus:outline-none"
                @input="handleInput(idx, $event)"
                @keydown.backspace="handleBackspace(idx, $event)"
                @paste="handlePaste"
                required
                autocomplete="one-time-code"
                inputmode="numeric"
                pattern="[0-9]*"
              />
            </div>
          </div>

          <div v-else class="form-group">
            <label class="form-label">Code de récupération de secours</label>
            <div class="input-wrapper">
              <Icon icon="ph:key-fill" class="input-icon" />
              <input
                v-model="recoveryCode"
                type="text"
                placeholder="Ex: aBcD123XyZ"
                class="form-input"
                required
              />
            </div>
          </div>

          <button type="submit" :disabled="loading" class="submit-btn mt-6">
            <span v-if="loading" class="btn-content">
              <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
              Vérification...
            </span>
            <span v-else class="btn-content">
              Vérifier le code
              <Icon icon="ph:arrow-right-bold" class="btn-arrow" />
            </span>
          </button>
        </form>

        <div class="register-prompt mt-6 text-center">
          <button @click="toggleMode" type="button" class="text-blue-400 hover:underline">
            {{ useRecoveryCode ? "Utiliser un code OTP classique" : "Utiliser un code de récupération de secours" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { useMeta } from '../composables/useMeta'

useMeta('Vérification OTP', 'Vérification à deux facteurs pour sécuriser votre compte U-map.', { canonicalPath: '/otp-verification' })

const router = useRouter()
const route = useRoute()

const tempToken = ref(route.query.temp_token || '')
const otpDigits = ref(['', '', '', '', '', ''])
const recoveryCode = ref('')
const useRecoveryCode = ref(false)
const loading = ref(false)
const error = ref('')
const inputs = ref([])

onMounted(() => {
  if (!tempToken.value) {
    router.push('/login')
    return
  }
  // Focus the first input box
  nextTick(() => {
    if (inputs.value[0]) {
      inputs.value[0].focus()
    }
  })
})

const toggleMode = () => {
  useRecoveryCode.value = !useRecoveryCode.value
  error.value = ''
  if (!useRecoveryCode.value) {
    otpDigits.value = ['', '', '', '', '', '']
    nextTick(() => {
      if (inputs.value[0]) inputs.value[0].focus()
    })
  }
}

const handleInput = (idx, event) => {
  const val = event.target.value
  // Keep only the last character if multiple are entered
  otpDigits.value[idx] = val.slice(-1).replace(/[^0-9]/g, '')
  
  if (otpDigits.value[idx] && idx < 5) {
    inputs.value[idx + 1].focus()
  }
}

const handleBackspace = (idx, event) => {
  if (!otpDigits.value[idx] && idx > 0) {
    otpDigits.value[idx - 1] = ''
    inputs.value[idx - 1].focus()
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').trim()
  if (pastedData.length === 6 && /^\d+$/.test(pastedData)) {
    for (let i = 0; i < 6; i++) {
      otpDigits.value[i] = pastedData[i]
    }
    inputs.value[5].focus()
  }
}

const handleSubmit = async () => {
  error.value = ''
  loading.value = true
  
  const code = useRecoveryCode.value ? recoveryCode.value : otpDigits.value.join('')
  
  try {
    await authService.verify2fa(tempToken.value, code)
    router.push('/')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@import '../styles/design-system.css';
@import '../styles/animations.css';

.login-root {
  min-height: 100vh;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  overflow: hidden;
  background-color: #0b0f19;
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
  background-size: 40px 40px;
  mask-image: radial-gradient(circle at 50% 50%, black, transparent 80%);
  pointer-events: none;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
  pointer-events: none;
}

.orb-1 {
  top: 10%;
  left: 20%;
  width: 300px;
  height: 300px;
  background: var(--primary, #3b82f6);
}

.orb-2 {
  bottom: 10%;
  right: 20%;
  width: 400px;
  height: 400px;
  background: var(--accent, #a855f7);
}

.login-container {
  width: 100%;
  max-width: 460px;
  position: relative;
  z-index: 10;
}

.brand-section {
  text-align: center;
  margin-bottom: 2rem;
}

.brand-logo {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  margin: 0 auto 1rem;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.brand-title {
  font-size: 2rem;
  font-weight: 800;
  color: white;
  letter-spacing: -0.025em;
  margin-bottom: 0.25rem;
}

.brand-subtitle {
  color: #9ca3af;
  font-size: 0.95rem;
}

.login-card {
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
}

.otp-digit-input {
  transition: all 0.2s ease;
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.otp-digit-input:focus {
  background: rgba(255, 255, 255, 0.1);
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 1rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  color: #ef4444;
  font-size: 0.9rem;
}
</style>
