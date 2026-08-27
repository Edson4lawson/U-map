<template>
  <div class="forgot-root">
    <!-- Animated background -->
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Main container -->
    <div class="forgot-container">
      <!-- Brand section -->
      <div class="brand-section">
        <div class="brand-logo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
          </svg>
        </div>
        <h1 class="brand-title">Mot de passe oublié ?</h1>
        <p class="brand-subtitle">Entrez votre e-mail pour recevoir un lien de réinitialisation</p>
      </div>

      <!-- Forgot password card -->
      <div class="forgot-card" :class="{ 'card-shake': shaking }">
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

        <!-- Success message -->
        <transition name="slide-success">
          <div v-if="success" class="success-banner">
            <Icon icon="ph:check-circle-fill" class="success-icon" />
            <span class="success-text">{{ success }}</span>
          </div>
        </transition>

        <!-- Form -->
        <form @submit.prevent="handleForgotPassword" class="forgot-form">
          <div class="form-group">
            <label class="form-label">Adresse e-mail</label>
            <div class="input-wrapper">
              <Icon icon="ph:envelope-fill" class="input-icon" />
              <input
                v-model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="votre@email.com"
                class="form-input"
                :disabled="loading || emailSent"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading || emailSent"
            class="submit-btn"
          >
            <span v-if="loading" class="btn-content">
              <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
              Envoi en cours...
            </span>
            <span v-else-if="emailSent" class="btn-content">
              <Icon icon="ph:check-bold" />
              E-mail envoyé
            </span>
            <span v-else class="btn-content">
              Envoyer le lien
              <Icon icon="ph:paper-plane-right-bold" class="btn-arrow" />
            </span>
          </button>
        </form>

        <!-- Back to login -->
        <div class="back-section">
          <router-link to="/login" class="back-link">
            <Icon icon="ph:arrow-left-bold" />
            Retour à la connexion
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useMeta } from '../composables/useMeta'

useMeta('Mot de passe oublié', 'Réinitialisez votre mot de passe U-map pour retrouver l\'accès à votre compte.', { canonicalPath: '/forgot-password' })

const email = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')
const shaking = ref(false)
const emailSent = ref(false)

const handleForgotPassword = async () => {
  error.value = ''
  success.value = ''
  loading.value = true
  
  try {
    const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
    const response = await fetch(`${API_URL}/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email.value })
    })
    
    const data = await response.json()
    
    if (!response.ok) {
      throw new Error(data.message || 'Une erreur est survenue')
    }
    
    success.value = data.message
    emailSent.value = true
  } catch (e) {
    error.value = e.message
    shaking.value = true
    setTimeout(() => { shaking.value = false }, 600)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* Root */
.forgot-root {
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
.forgot-container {
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

.brand-subtitle {
  font-size: 14px;
  color: rgba(148,163,184,0.7);
  margin: 0;
}

/* Card */
.forgot-card {
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

/* Banners */
.error-banner, .success-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 12px;
  margin-bottom: 20px;
}

.error-banner {
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
}

.success-banner {
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.2);
}

.error-icon {
  font-size: 18px;
  color: #f87171;
  flex-shrink: 0;
}

.success-icon {
  font-size: 18px;
  color: #34d399;
  flex-shrink: 0;
}

.error-text, .success-text {
  flex: 1;
  font-size: 13px;
}

.error-text { color: #fca5a5; }
.success-text { color: #86efac; }

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
.forgot-form {
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

.form-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

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

/* Back section */
.back-section {
  margin-top: 20px;
  text-align: center;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(148,163,184,0.5);
  text-decoration: none;
  transition: color 0.3s;
}

.back-link:hover { color: #818cf8; }

/* Transitions */
.slide-error-enter-active,
.slide-error-leave-active,
.slide-success-enter-active,
.slide-success-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.slide-error-enter-from,
.slide-error-leave-to,
.slide-success-enter-from,
.slide-success-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive */
@media (max-width: 480px) {
  .forgot-container {
    padding: 16px;
  }

  .forgot-card {
    padding: 24px;
  }
}
</style>
