<template>
  <div class="admin-login-root">
    <!-- Animated grid background -->
    <div class="bg-grid"></div>

    <!-- Floating orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <!-- Particles -->
    <div class="particle" v-for="n in 20" :key="n" :style="particleStyle(n)"></div>

    <!-- Center card -->
    <div class="login-wrap">

      <!-- Logo section -->
      <div class="login-brand">
        <div class="brand-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
        </div>
        <h1 class="brand-title">U-Map<span class="brand-dot">.</span></h1>
        <p class="brand-sub">Centre d'Administration Sécurisé</p>
        <div class="brand-badges">
          <span class="badge-secure">
            <Icon icon="ph:shield-check-fill" />
            Connexion chiffrée
          </span>
          <span class="badge-access">
            <Icon icon="ph:lock-fill" />
            Accès restreint
          </span>
        </div>
      </div>

      <!-- Card -->
      <div class="login-card" :class="{ 'card-shake': shaking }">
        <!-- Card header -->
        <div class="card-head">
          <div class="card-head-icon">
            <Icon icon="ph:terminal-window-bold" />
          </div>
          <div>
            <p class="card-head-label">Authentification Admin</p>
            <p class="card-head-desc">Entrez votre mot de passe maître</p>
          </div>
        </div>

        <!-- Error -->
        <transition name="slide-error">
          <div v-if="error" class="error-box">
            <div class="error-icon">
              <Icon icon="ph:warning-octagon-fill" />
            </div>
            <div>
              <p class="error-title">Accès refusé</p>
              <p class="error-msg">{{ error }}</p>
            </div>
            <button class="error-close" @click="error = ''">
              <Icon icon="ph:x-bold" />
            </button>
          </div>
        </transition>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="login-form">
          <div class="field-wrap" :class="{ 'field-focus': fieldFocused, 'field-filled': password.length > 0 }">
            <label class="field-label">Mot de passe administrateur</label>
            <div class="field-inner">
              <span class="field-icon-left">
                <Icon icon="ph:lock-key-fill" />
              </span>
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••••••"
                class="field-input"
                @focus="fieldFocused = true"
                @blur="fieldFocused = false"
              />
              <button type="button" @click="showPassword = !showPassword" class="field-toggle">
                <Icon :icon="showPassword ? 'ph:eye-slash-fill' : 'ph:eye-fill'" />
              </button>
            </div>
            <div class="field-underline"></div>
          </div>

          <!-- Strength indicator -->
          <div v-if="password.length > 0" class="strength-wrap">
            <div class="strength-bars">
              <div
                v-for="i in 4" :key="i"
                class="strength-bar"
                :class="strengthClass(i)"
              ></div>
            </div>
            <span class="strength-label" :class="strengthLabelClass">{{ strengthLabel }}</span>
          </div>

          <button type="submit" :disabled="loading" class="submit-btn">
            <div class="btn-glow"></div>
            <div class="btn-content">
              <transition name="fade" mode="out-in">
                <span v-if="loading" key="loading" class="btn-inner">
                  <Icon icon="ph:spinner-gap-bold" class="spin-icon" />
                  Vérification en cours...
                </span>
                <span v-else key="idle" class="btn-inner">
                  <Icon icon="ph:sign-in-bold" />
                  Accéder au Dashboard
                  <Icon icon="ph:arrow-right-bold" class="btn-arrow" />
                </span>
              </transition>
            </div>
            <div class="btn-shimmer"></div>
          </button>
        </form>

        <!-- Footer -->
        <div class="card-foot">
          <div class="foot-divider">
            <span></span>
            <p>Zone sécurisée</p>
            <span></span>
          </div>
          <router-link to="/" class="back-link">
            <Icon icon="ph:arrow-left-bold" />
            Retour à l'application
          </router-link>
        </div>
      </div>

      <!-- Version -->
      <p class="version-tag">U-Map Admin v2.0 · {{ new Date().getFullYear() }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { adminService } from '../services/adminService'

const router = useRouter()
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const fieldFocused = ref(false)
const shaking = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true
  try {
    await adminService.login(password.value)
    router.push('/admin/dashboard')
  } catch (e) {
    error.value = e.message
    shaking.value = true
    setTimeout(() => { shaking.value = false }, 600)
  } finally {
    loading.value = false
  }
}

// Password strength
const strengthLevel = computed(() => {
  const p = password.value
  if (p.length === 0) return 0
  if (p.length < 4) return 1
  if (p.length < 8) return 2
  if (p.length < 12) return 3
  return 4
})

const strengthLabel = computed(() => ['', 'Très faible', 'Faible', 'Moyen', 'Fort'][strengthLevel.value])
const strengthLabelClass = computed(() => ['', 'text-red-400', 'text-orange-400', 'text-yellow-400', 'text-emerald-400'][strengthLevel.value])

const strengthClass = (i) => {
  if (i > strengthLevel.value) return 'bar-empty'
  return ['', 'bar-red', 'bar-orange', 'bar-yellow', 'bar-green'][strengthLevel.value]
}

const particleStyle = (n) => ({
  left: `${(n * 17 + 5) % 100}%`,
  top: `${(n * 23 + 10) % 100}%`,
  animationDelay: `${(n * 0.3) % 5}s`,
  animationDuration: `${3 + (n % 4)}s`,
  width: `${2 + (n % 3)}px`,
  height: `${2 + (n % 3)}px`,
  opacity: 0.2 + (n % 5) * 0.07
})
</script>

<style scoped>
/* ---- ROOT ---- */
.admin-login-root {
  min-height: 100vh;
  background: #050a14;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* ---- BACKGROUND GRID ---- */
.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
  background-size: 60px 60px;
  animation: gridMove 20s linear infinite;
}
@keyframes gridMove {
  0% { transform: translateY(0); }
  100% { transform: translateY(60px); }
}

/* ---- ORBS ---- */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: orbFloat 8s ease-in-out infinite;
  pointer-events: none;
}
.orb-1 {
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(99,102,241,0.15), transparent 70%);
  top: -200px; left: -200px;
}
.orb-2 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(6,182,212,0.1), transparent 70%);
  bottom: -150px; right: -150px;
  animation-delay: -3s;
}
.orb-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(139,92,246,0.08), transparent 70%);
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: -6s;
}
@keyframes orbFloat {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-30px) scale(1.05); }
}

/* ---- PARTICLES ---- */
.particle {
  position: absolute;
  border-radius: 50%;
  background: rgba(99,102,241,0.8);
  animation: particleFly linear infinite;
}
@keyframes particleFly {
  0% { transform: translateY(0) rotate(0deg); opacity: 0; }
  20% { opacity: 1; }
  80% { opacity: 1; }
  100% { transform: translateY(-80px) rotate(360deg); opacity: 0; }
}

/* ---- LAYOUT ---- */
.login-wrap {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 440px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 28px;
}

/* ---- BRAND ---- */
.login-brand {
  text-align: center;
}
.brand-icon {
  width: 80px; height: 80px;
  border-radius: 24px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 20px 60px rgba(99,102,241,0.4);
  animation: iconPulse 3s ease-in-out infinite;
}
.brand-icon svg {
  width: 40px; height: 40px;
  color: white;
}
@keyframes iconPulse {
  0%, 100% { box-shadow: 0 20px 60px rgba(99,102,241,0.4); }
  50% { box-shadow: 0 20px 80px rgba(99,102,241,0.7); }
}
.brand-title {
  font-size: 32px;
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin: 0 0 4px;
}
.brand-dot { color: #6366f1; }
.brand-sub {
  font-size: 13px;
  color: rgba(148,163,184,0.7);
  margin: 0 0 16px;
  letter-spacing: 0.5px;
}
.brand-badges {
  display: flex;
  justify-content: center;
  gap: 8px;
}
.badge-secure, .badge-access {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 100px;
  border: 1px solid;
}
.badge-secure {
  color: #34d399;
  background: rgba(52,211,153,0.08);
  border-color: rgba(52,211,153,0.2);
}
.badge-access {
  color: #a78bfa;
  background: rgba(167,139,250,0.08);
  border-color: rgba(167,139,250,0.2);
}

/* ---- CARD ---- */
.login-card {
  width: 100%;
  background: rgba(15,23,42,0.7);
  backdrop-filter: blur(40px);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 28px;
  padding: 32px;
  box-shadow: 0 40px 80px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.05);
  transition: all 0.3s ease;
}
.login-card:hover {
  border-color: rgba(99,102,241,0.35);
  box-shadow: 0 40px 100px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.07);
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

/* ---- CARD HEAD ---- */
.card-head {
  display: flex;
  align-items: center;
  gap: 14px;
  padding-bottom: 24px;
  margin-bottom: 24px;
  border-bottom: 1px solid rgba(99,102,241,0.15);
}
.card-head-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.25);
  display: flex; align-items: center; justify-content: center;
  color: #818cf8;
  font-size: 20px;
  flex-shrink: 0;
}
.card-head-label {
  font-size: 14px;
  font-weight: 700;
  color: #e2e8f0;
  margin: 0 0 2px;
}
.card-head-desc {
  font-size: 12px;
  color: rgba(148,163,184,0.6);
  margin: 0;
}

/* ---- ERROR BOX ---- */
.error-box {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.2);
  border-radius: 14px;
  margin-bottom: 20px;
}
.error-icon {
  font-size: 20px;
  color: #f87171;
  flex-shrink: 0;
  line-height: 1;
}
.error-title {
  font-size: 13px;
  font-weight: 700;
  color: #fca5a5;
  margin: 0 0 2px;
}
.error-msg {
  font-size: 12px;
  color: rgba(252,165,165,0.7);
  margin: 0;
}
.error-close {
  margin-left: auto;
  color: rgba(239,68,68,0.5);
  font-size: 14px;
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  transition: color 0.2s;
}
.error-close:hover { color: #f87171; }

/* ---- FIELD ---- */
.login-form { display: flex; flex-direction: column; gap: 20px; }
.field-wrap { position: relative; }
.field-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(148,163,184,0.5);
  margin-bottom: 10px;
  transition: color 0.3s;
}
.field-focus .field-label { color: #818cf8; }
.field-inner {
  position: relative;
  display: flex;
  align-items: center;
}
.field-icon-left {
  position: absolute;
  left: 16px;
  color: rgba(99,102,241,0.4);
  font-size: 18px;
  transition: color 0.3s;
  pointer-events: none;
}
.field-focus .field-icon-left { color: #818cf8; }
.field-input {
  width: 100%;
  background: rgba(15,23,42,0.8);
  border: 1px solid rgba(99,102,241,0.15);
  border-radius: 14px;
  padding: 14px 48px;
  font-size: 14px;
  color: white;
  outline: none;
  transition: all 0.3s ease;
  font-family: inherit;
}
.field-input::placeholder { color: rgba(148,163,184,0.25); }
.field-input:focus {
  border-color: rgba(99,102,241,0.5);
  background: rgba(99,102,241,0.05);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.field-toggle {
  position: absolute;
  right: 14px;
  color: rgba(148,163,184,0.3);
  font-size: 18px;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 4px;
}
.field-toggle:hover { color: #818cf8; }

/* ---- STRENGTH ---- */
.strength-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: -8px;
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
.strength-label { font-size: 11px; font-weight: 600; white-space: nowrap; }
.text-red-400 { color: #f87171; }
.text-orange-400 { color: #fb923c; }
.text-yellow-400 { color: #facc15; }
.text-emerald-400 { color: #34d399; }

/* ---- SUBMIT BUTTON ---- */
.submit-btn {
  position: relative;
  width: 100%;
  padding: 16px;
  border: none;
  border-radius: 16px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  cursor: pointer;
  overflow: hidden;
  transition: all 0.3s ease;
}
.submit-btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(99,102,241,0.4);
}
.submit-btn:not(:disabled):active { transform: translateY(0); }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at center, rgba(255,255,255,0.1), transparent 70%);
}
.btn-content { position: relative; z-index: 1; }
.btn-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: white;
  letter-spacing: 0.3px;
}
.spin-icon { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.btn-arrow { transition: transform 0.3s; }
.submit-btn:hover .btn-arrow { transform: translateX(4px); }
.btn-shimmer {
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
  animation: shimmer 3s ease-in-out infinite;
}
@keyframes shimmer {
  0% { left: -100%; }
  50%, 100% { left: 100%; }
}

/* ---- CARD FOOT ---- */
.card-foot { margin-top: 24px; }
.foot-divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.foot-divider span {
  flex: 1;
  height: 1px;
  background: rgba(99,102,241,0.1);
}
.foot-divider p {
  font-size: 11px;
  color: rgba(148,163,184,0.3);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  white-space: nowrap;
}
.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(148,163,184,0.4);
  text-decoration: none;
  transition: color 0.3s;
}
.back-link:hover { color: #818cf8; }

/* ---- VERSION ---- */
.version-tag {
  font-size: 11px;
  color: rgba(148,163,184,0.25);
  margin: 0;
}

/* ---- TRANSITIONS ---- */
.slide-error-enter-active, .slide-error-leave-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.slide-error-enter-from, .slide-error-leave-to {
  opacity: 0;
  transform: translateY(-10px) scale(0.95);
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
