<template>
  <div class="settings-root min-h-screen text-slate-100 p-6 md:p-12">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-800">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
            <Icon icon="ph:shield-check-fill" class="text-blue-500 text-4xl" />
            Profil & Sécurité
          </h1>
          <p class="text-slate-400 mt-1">Gérez votre profil, vos options de sécurité et vos sessions actives.</p>
        </div>
        <router-link to="/" class="back-btn flex items-center gap-2 text-sm bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-lg transition">
          <Icon icon="ph:arrow-left-bold" />
          Retour à l'accueil
        </router-link>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 gap-8">
        <!-- Profile Box -->
        <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl p-6 md:p-8">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <Icon icon="ph:user-circle-bold" class="text-blue-400" />
                Informations du Profil
              </h2>
              <p class="text-sm text-slate-400 mt-1">Gérez vos informations personnelles et académiques.</p>
            </div>
          </div>

          <form @submit.prevent="handleUpdateProfile" class="space-y-4">
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="w-full">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Photo de profil</label>
                <input type="file" @change="handleAvatarChange" accept="image/*" class="w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 bg-slate-900 border border-slate-800 rounded-lg focus:outline-none" />
              </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="w-full">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Nom</label>
                <input v-model="profileForm.name" type="text" class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
              </div>
              <div class="w-full">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Email (Lecture seule)</label>
                <input v-model="user.email" type="email" disabled class="w-full bg-slate-900/50 border border-slate-800 text-slate-500 rounded-lg px-4 py-2.5 cursor-not-allowed" />
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
              <div class="w-full">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Faculté / Filière (Optionnel)</label>
                <input v-model="profileForm.faculty" type="text" placeholder="Ex: Informatique, Droit..." class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
              </div>
              <div class="w-full">
                <label class="block text-xs font-semibold text-slate-400 mb-1">Niveau d'étude (Optionnel)</label>
                <select v-model="profileForm.study_level" class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none">
                  <option value="">Non spécifié</option>
                  <option value="L1">Licence 1</option>
                  <option value="L2">Licence 2</option>
                  <option value="L3">Licence 3</option>
                  <option value="M1">Master 1</option>
                  <option value="M2">Master 2</option>
                  <option value="D">Doctorat</option>
                </select>
              </div>
            </div>

            <div class="w-full sm:w-1/2 pr-2">
              <label class="block text-xs font-semibold text-slate-400 mb-1">Matricule étudiant (Optionnel)</label>
              <input v-model="profileForm.student_id" type="text" placeholder="Ex: 20260001" class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
            </div>

            <div class="pt-2">
              <button type="submit" :disabled="loadingProfile" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition">
                {{ loadingProfile ? 'Enregistrement...' : 'Enregistrer le profil' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Password Change Box -->
        <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl p-6 md:p-8">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <Icon icon="ph:key-bold" class="text-blue-400" />
                Mot de passe
              </h2>
            </div>
          </div>
          
          <form @submit.prevent="handleUpdatePassword" class="space-y-4 max-w-md">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Mot de passe actuel</label>
              <input v-model="passwordForm.current_password" type="password" required class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nouveau mot de passe</label>
              <input v-model="passwordForm.password" type="password" required minlength="8" class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Confirmer le nouveau mot de passe</label>
              <input v-model="passwordForm.password_confirmation" type="password" required minlength="8" class="w-full bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 focus:border-blue-500 focus:outline-none" />
            </div>
            <div class="pt-2">
              <button type="submit" :disabled="loadingPassword" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition">
                {{ loadingPassword ? 'Modification...' : 'Modifier le mot de passe' }}
              </button>
            </div>
          </form>
        </div>

        <!-- 2FA Box -->
        <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl p-6 md:p-8">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <Icon icon="ph:lock-keyhole-fill" class="text-blue-400" />
                Authentification Double Facteur (2FA)
              </h2>
              <p class="text-sm text-slate-400 mt-1">Protégez votre compte avec une étape de vérification supplémentaire lors de la connexion.</p>
            </div>
            <span 
              :class="user.two_factor_confirmed_at ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700'"
              class="text-xs px-3 py-1 rounded-full font-semibold uppercase tracking-wider"
            >
              {{ user.two_factor_confirmed_at ? 'Activé' : 'Désactivé' }}
            </span>
          </div>

          <!-- If 2FA is active -->
          <div v-if="user.two_factor_confirmed_at" class="space-y-6">
            <div class="bg-emerald-950/20 border border-emerald-500/20 rounded-xl p-4 flex gap-3 text-emerald-400">
              <Icon icon="ph:check-circle-bold" class="text-2xl shrink-0" />
              <div class="text-sm">
                Votre compte est protégé par la double authentification. Chaque tentative de connexion nécessitera la saisie d'un code OTP généré par votre application.
              </div>
            </div>

            <!-- Recovery Codes Section -->
            <div v-if="recoveryCodes.length > 0" class="border border-slate-800 rounded-xl p-6 bg-slate-950/40">
              <h3 class="font-bold text-white mb-2 flex items-center gap-2">
                <Icon icon="ph:file-text-bold" class="text-yellow-500" />
                Codes de récupération de secours
              </h3>
              <p class="text-xs text-slate-400 mb-4">Stockez ces codes en lieu sûr. Ils vous permettront de récupérer l'accès à votre compte en cas de perte de votre appareil.</p>
              
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm font-mono text-center mb-4">
                <div v-for="c in recoveryCodes" :key="c" class="bg-slate-900 border border-slate-800 py-2 px-3 rounded-lg text-yellow-200">
                  {{ c }}
                </div>
              </div>

              <button @click="copyRecoveryCodes" class="text-xs bg-slate-800 hover:bg-slate-700 text-white px-3 py-1.5 rounded transition flex items-center gap-1">
                <Icon icon="ph:copy-bold" /> Copier les codes
              </button>
            </div>

            <button 
              @click="handleDisable2fa" 
              :disabled="loading"
              class="bg-red-600/20 border border-red-500/30 text-red-400 hover:bg-red-600 hover:text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition"
            >
              Désactiver la double authentification
            </button>
          </div>

          <!-- If 2FA is inactive -->
          <div v-else class="space-y-6">
            <!-- Setup steps -->
            <div v-if="setupData" class="border border-slate-800 rounded-xl p-6 bg-slate-950/40 space-y-6">
              <div class="flex flex-col md:flex-row gap-6 items-center justify-between">
                <div class="space-y-4 max-w-md">
                  <h3 class="font-bold text-white">Étape 1 : Configurez votre application TOTP</h3>
                  <p class="text-sm text-slate-400">Scannez le code QR ci-contre avec votre application d'authentification (Google Authenticator, Authy, Microsoft Authenticator...).</p>
                  <p class="text-sm text-slate-400">Si vous ne pouvez pas scanner le QR code, entrez la clé secrète suivante manuellement :</p>
                  <div class="bg-slate-900 border border-slate-800 font-mono text-sm py-2 px-4 rounded-lg select-all text-blue-300 break-all">
                    {{ setupData.secret }}
                  </div>
                </div>

                <div class="bg-white p-3 rounded-xl shrink-0">
                  <!-- Generate a simple QR code from standard API, or display using simple canvas. We will render an image from qr-server. -->
                  <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(setupData.qr_code_url)}`" alt="2FA QR Code" width="150" height="150" />
                </div>
              </div>

              <div class="border-t border-slate-800 pt-6">
                <h3 class="font-bold text-white mb-2">Étape 2 : Confirmez le code</h3>
                <p class="text-sm text-slate-400 mb-4">Saisissez le code de vérification à 6 chiffres affiché sur votre application pour confirmer l'activation.</p>
                
                <form @submit.prevent="handleConfirm2fa" class="flex flex-col sm:flex-row gap-3">
                  <input 
                    v-model="verificationCode"
                    type="text" 
                    placeholder="Ex: 123456" 
                    maxlength="6"
                    class="bg-slate-900 border border-slate-800 text-white rounded-lg px-4 py-2.5 text-center font-bold tracking-widest text-lg w-full sm:w-48 focus:border-blue-500 focus:outline-none"
                    required
                  />
                  <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition">
                    Activer
                  </button>
                </form>
                <p v-if="error" class="text-red-400 text-xs mt-2 font-semibold">{{ error }}</p>
              </div>
            </div>

            <button 
              v-else
              @click="handleStartSetup" 
              :disabled="loading"
              class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg text-sm font-semibold transition flex items-center gap-2"
            >
              <Icon icon="ph:plus-circle-bold" />
              Configurer la double authentification
            </button>
          </div>
        </div>

        <!-- Devices Session History Box -->
        <div class="bg-slate-900/60 backdrop-blur border border-slate-800 rounded-2xl p-6 md:p-8">
          <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
            <Icon icon="ph:devices-fill" class="text-blue-400" />
            Sessions & Appareils Connectés
          </h2>
          <p class="text-sm text-slate-400 mb-6">Liste des appareils qui se sont connectés à votre compte U-Map récemment.</p>

          <div v-if="devices.length === 0" class="text-center py-6 text-slate-500">
            Aucune session enregistrée.
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="d in devices" 
              :key="d.id"
              class="flex flex-col sm:flex-row items-start sm:items-center justify-between border border-slate-800 bg-slate-950/20 rounded-xl p-4 gap-4"
            >
              <div class="flex items-center gap-3">
                <div class="bg-slate-800 text-slate-300 p-2.5 rounded-lg text-xl">
                  <Icon :icon="getDeviceIcon(d.device_name)" />
                </div>
                <div>
                  <div class="font-bold text-white flex items-center gap-2">
                    {{ d.device_name }}
                    <span v-if="isCurrentDevice(d)" class="bg-blue-500/20 text-blue-400 text-[10px] px-2 py-0.5 rounded border border-blue-500/30 uppercase font-semibold">Cet appareil</span>
                  </div>
                  <div class="text-xs text-slate-400 mt-0.5 font-mono">IP : {{ d.ip_address }}</div>
                  <div class="text-xs text-slate-500 mt-1">Actif le : {{ new Date(d.last_active_at).toLocaleString('fr-FR') }}</div>
                </div>
              </div>

              <button 
                @click="handleRevokeDevice(d.id)"
                :disabled="loading || isCurrentDevice(d)"
                class="text-xs bg-red-600/10 border border-red-500/20 text-red-400 hover:bg-red-600 hover:text-white px-3 py-2 rounded-lg transition disabled:opacity-40 disabled:hover:bg-red-600/10 disabled:hover:text-red-400"
              >
                Déconnecter
              </button>
            </div>
          </div>
        </div>
        <!-- Logout Box -->
        <div class="bg-red-900/20 border border-red-900/50 rounded-2xl p-6 md:p-8 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-red-400">Déconnexion</h2>
            <p class="text-sm text-red-400/70 mt-1">Se déconnecter de votre compte sur cet appareil.</p>
          </div>
          <button @click="handleLogout" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold transition">
            Se déconnecter
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { useMeta } from '../composables/useMeta'
import errorHandler from '../services/errorHandler'

useMeta('Paramètres de Sécurité', 'Gérez la sécurité de votre compte U-map : authentification 2FA, appareils connectés et codes de récupération.', { canonicalPath: '/settings/security' })

const { t } = useI18n()
const router = useRouter()

const user = ref(authService.getCurrentUser() || {})

const profileForm = ref({
  name: user.value.name || '',
  faculty: user.value.faculty || '',
  study_level: user.value.study_level || '',
  student_id: user.value.student_id || '',
  avatar: null
})

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const loadingProfile = ref(false)
const loadingPassword = ref(false)

const devices = ref([])
const setupData = ref(null)
const verificationCode = ref('')
const recoveryCodes = ref([])
const loading = ref(false)
const error = ref('')

onMounted(async () => {
  await fetchDevices()
})

const fetchDevices = async () => {
  try {
    devices.value = await authService.getDevices()
  } catch (e) {
    console.error("Error fetching devices:", e)
  }
}

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    profileForm.value.avatar = file
  }
}

const handleUpdateProfile = async () => {
  loadingProfile.value = true
  try {
    const formData = new FormData()
    formData.append('name', profileForm.value.name)
    if (profileForm.value.faculty) formData.append('faculty', profileForm.value.faculty)
    if (profileForm.value.study_level) formData.append('study_level', profileForm.value.study_level)
    if (profileForm.value.student_id) formData.append('student_id', profileForm.value.student_id)
    if (profileForm.value.avatar) formData.append('avatar', profileForm.value.avatar)

    await authService.updateProfile(formData)
    errorHandler.success('Profil mis à jour avec succès')
    user.value = authService.getCurrentUser()
  } catch (e) {
    errorHandler.handle(e, 'Erreur lors de la mise à jour du profil')
  } finally {
    loadingProfile.value = false
  }
}

const handleUpdatePassword = async () => {
  loadingPassword.value = true
  try {
    await authService.changePassword(
      passwordForm.value.current_password,
      passwordForm.value.password,
      passwordForm.value.password_confirmation
    )
    errorHandler.success('Mot de passe modifié avec succès')
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (e) {
    errorHandler.handle(e, 'Erreur lors de la modification du mot de passe')
  } finally {
    loadingPassword.value = false
  }
}

const handleLogout = () => {
  if (confirm(t('profile.logout_confirm'))) {
    authService.logout()
    router.push('/')
  }
}

const handleStartSetup = async () => {
  loading.value = true
  error.value = ''
  try {
    setupData.value = await authService.enable2fa()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

const handleConfirm2fa = async () => {
  loading.value = true
  error.value = ''
  try {
    const res = await authService.confirm2fa(verificationCode.value)
    recoveryCodes.value = res.recovery_codes
    
    // Update local user state
    user.value.two_factor_confirmed_at = new Date().toISOString()
    localStorage.setItem('u_map_user', JSON.stringify(user.value))

    setupData.value = null
    verificationCode.value = ''
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

const handleDisable2fa = async () => {
  if (!confirm(t('settings.two_fa.disable_confirm'))) return
  
  loading.value = true
  try {
    await authService.disable2fa()
    
    user.value.two_factor_confirmed_at = null
    localStorage.setItem('u_map_user', JSON.stringify(user.value))
    
    recoveryCodes.value = []
  } catch (e) {
    console.error("Error disabling 2fa:", e)
  } finally {
    loading.value = false
  }
}

const handleRevokeDevice = async (id) => {
  if (!confirm(t('settings.devices.disconnect_confirm'))) return
  
  loading.value = true
  try {
    await authService.revokeDevice(id)
    await fetchDevices()
  } catch (e) {
    console.error("Error revoking device:", e)
  } finally {
    loading.value = false
  }
}

const copyRecoveryCodes = () => {
  navigator.clipboard.writeText(recoveryCodes.value.join('\n'))
  errorHandler.success(t('settings.two_fa.codes_copied'))
}

const getDeviceIcon = (deviceName) => {
  const name = deviceName.toLowerCase()
  if (name.includes('iphone') || name.includes('android')) return 'ph:phone-fill'
  if (name.includes('ipad')) return 'ph:tablet-fill'
  return 'ph:desktop-fill'
}

const isCurrentDevice = (device) => {
  // A simple heuristic (can match IP if same IP and current browser agent, but for demo we just look if last_active matches current timeframe closely)
  // Let's assume the first element or the most active is current, or we can check the IP.
  // For safety, let's keep it simple: we can disable revoking for all if we want, but since they can re-login, we let them revoke others.
  // We'll mark the first one as current for UI demonstration purposes.
  return devices.value[0] && devices.value[0].id === device.id
}
</script>

<style scoped>
.settings-root {
  background-color: #0b0f19;
  font-family: Inter, system-ui, -apple-system, sans-serif;
}
</style>
