<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-gray-100 font-sans flex">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-slate-800 border-r border-gray-200 dark:border-slate-700 flex flex-col transition-all duration-300 z-20 hidden md:flex">
      <div class="h-20 flex items-center px-8 border-b border-gray-200 dark:border-slate-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/20 flex items-center justify-center">
            <Icon icon="ph:map-trifold-bold" class="text-white w-6 h-6" />
          </div>
          <span class="text-xl font-bold font-display tracking-tight dark:text-white">U-Map<span class="text-blue-500">.</span></span>
        </div>
      </div>
      
      <div class="flex-1 py-6 px-4 space-y-1 overflow-y-auto">
        <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Général</p>
        <button @click="currentTab = 'dashboard'" :class="['w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200', currentTab === 'dashboard' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700/50']">
          <Icon icon="ph:squares-four" class="w-5 h-5" :class="currentTab === 'dashboard' ? 'text-blue-600 dark:text-blue-400' : ''" />
          Vue d'ensemble
        </button>
        <button @click="currentTab = 'users'" :class="['w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200', currentTab === 'users' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700/50']">
          <Icon icon="ph:users" class="w-5 h-5" :class="currentTab === 'users' ? 'text-blue-600 dark:text-blue-400' : ''" />
          Utilisateurs
        </button>
        <button @click="currentTab = 'places'" :class="['w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200', currentTab === 'places' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700/50']">
          <div class="flex items-center gap-3">
            <Icon icon="ph:map-pin" class="w-5 h-5" :class="currentTab === 'places' ? 'text-blue-600 dark:text-blue-400' : ''" />
            Lieux
          </div>
          <span v-if="stats?.pendingPlaces > 0" class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ stats.pendingPlaces }}</span>
        </button>
        <button @click="currentTab = 'messages'" :class="['w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200', currentTab === 'messages' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700/50']">
          <Icon icon="ph:chats" class="w-5 h-5" :class="currentTab === 'messages' ? 'text-blue-600 dark:text-blue-400' : ''" />
          Messages
        </button>
      </div>

      <div class="p-4 border-t border-gray-200 dark:border-slate-700">
        <button @click="handleLogout" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors font-medium text-sm">
          <Icon icon="ph:sign-out" class="w-5 h-5" />
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden relative">
      <!-- Mobile Header -->
      <header class="md:hidden h-16 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between px-4 z-20">
         <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
              <Icon icon="ph:map-trifold-bold" class="text-white w-5 h-5" />
            </div>
            <span class="font-bold font-display dark:text-white">U-Map Admin</span>
         </div>
         <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 p-2">
           <Icon icon="ph:list" class="w-6 h-6" />
         </button>
      </header>

      <!-- Topbar -->
      <header class="h-20 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md border-b border-gray-200 dark:border-slate-700 flex items-center justify-between px-8 z-10 hidden md:flex sticky top-0">
        <div>
          <h2 class="text-2xl font-bold dark:text-white tracking-tight">{{ tabTitles[currentTab] }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Gérez l'application U-Map en temps réel.</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-3 px-4 py-2 bg-gray-100 dark:bg-slate-700 rounded-full">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Système en ligne</span>
          </div>
        </div>
      </header>

      <!-- Scrollable Content Area -->
      <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
        
        <!-- Loading State -->
        <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-gray-50/80 dark:bg-slate-900/80 backdrop-blur-sm z-50">
          <div class="flex flex-col items-center gap-4">
            <Icon icon="ph:spinner-gap-bold" class="w-10 h-10 text-blue-500 animate-spin" />
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Chargement des données...</p>
          </div>
        </div>

        <div v-else class="max-w-7xl mx-auto space-y-8">
          
          <!-- TAB: DASHBOARD -->
          <template v-if="currentTab === 'dashboard'">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <!-- Card 1 -->
              <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex justify-between items-start mb-4">
                  <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <Icon icon="ph:users-fill" class="w-6 h-6" />
                  </div>
                  <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-full flex items-center gap-1">
                    <Icon icon="ph:trend-up" /> +{{ stats?.recentUsers }}
                  </span>
                </div>
                <div>
                  <h3 class="text-3xl font-display font-bold text-gray-900 dark:text-white mb-1">{{ stats?.totalUsers || 0 }}</h3>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Utilisateurs totaux</p>
                </div>
              </div>

              <!-- Card 2 -->
              <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex justify-between items-start mb-4">
                  <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <Icon icon="ph:map-pin-fill" class="w-6 h-6" />
                  </div>
                </div>
                <div>
                  <h3 class="text-3xl font-display font-bold text-gray-900 dark:text-white mb-1">{{ stats?.approvedPlaces || 0 }}</h3>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lieux approuvés</p>
                </div>
              </div>

              <!-- Card 3 -->
              <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group cursor-pointer" @click="currentTab = 'places'">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-orange-500/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex justify-between items-start mb-4">
                  <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                    <Icon icon="ph:warning-circle-fill" class="w-6 h-6" />
                  </div>
                </div>
                <div>
                  <h3 class="text-3xl font-display font-bold text-gray-900 dark:text-white mb-1">{{ stats?.pendingPlaces || 0 }}</h3>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lieux en attente</p>
                </div>
              </div>

              <!-- Card 4 -->
              <div class="bg-white dark:bg-slate-800 rounded-3xl p-6 border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex justify-between items-start mb-4">
                  <div class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <Icon icon="ph:chats-fill" class="w-6 h-6" />
                  </div>
                  <span class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-full flex items-center gap-1">
                    <Icon icon="ph:trend-up" /> +{{ stats?.recentMessages }}
                  </span>
                </div>
                <div>
                  <h3 class="text-3xl font-display font-bold text-gray-900 dark:text-white mb-1">{{ stats?.totalMessages || 0 }}</h3>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Messages échangés</p>
                </div>
              </div>
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Categorey Stats -->
              <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl p-6 shadow-sm">
                <h3 class="text-lg font-bold dark:text-white mb-6">Répartition des Lieux</h3>
                <div class="space-y-4">
                  <div v-for="(item, index) in stats?.placesByCategory" :key="index">
                    <div class="flex justify-between items-center mb-2">
                      <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ item.category }}</span>
                      <span class="text-sm font-bold dark:text-white">{{ item.count }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                      <div class="bg-blue-500 h-2 rounded-full" :style="{ width: `${(item.count / stats.totalPlaces) * 100}%` }"></div>
                    </div>
                  </div>
                </div>
              </div>

               <!-- Quick Actions -->
               <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-8 shadow-xl text-white relative overflow-hidden">
                  <div class="absolute right-0 top-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
                  <h3 class="text-2xl font-bold font-display mb-2 relative z-10">Actions Rapides</h3>
                  <p class="text-blue-100 mb-8 relative z-10 text-sm">Gérez les éléments critiques en attente de modération.</p>
                  
                  <div class="space-y-3 relative z-10">
                    <button @click="currentTab = 'places'" class="w-full bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/20 rounded-xl p-4 flex items-center justify-between transition-colors">
                      <div class="flex items-center gap-3">
                        <Icon icon="ph:map-pin-bold" class="w-5 h-5" />
                        <span class="font-semibold">Modérer les lieux ({{ stats?.pendingPlaces }})</span>
                      </div>
                      <Icon icon="ph:arrow-right-bold" />
                    </button>
                  </div>
               </div>
            </div>
          </template>

          <!-- TAB: USERS -->
          <template v-if="currentTab === 'users'">
            <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl shadow-sm overflow-hidden">
              <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center">
                <h3 class="font-bold text-lg dark:text-white">Liste des Utilisateurs</h3>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="bg-gray-50/50 dark:bg-slate-800/50">
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                      <td class="py-4 px-6">
                        <div class="flex items-center gap-3">
                          <img :src="'https://ui-avatars.com/api/?name=' + user.name + '&background=EBF4FF&color=3B82F6'" class="w-8 h-8 rounded-full">
                          <span class="font-medium dark:text-white">{{ user.name }}</span>
                        </div>
                      </td>
                      <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-300">{{ user.email }}</td>
                      <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                      <td class="py-4 px-6 text-right">
                        <button @click="deleteUser(user.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="Supprimer">
                          <Icon icon="ph:trash" class="w-5 h-5" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="users.length === 0">
                      <td colspan="4" class="py-8 text-center text-gray-500">Aucun utilisateur trouvé.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>

          <!-- TAB: PLACES -->
          <template v-if="currentTab === 'places'">
            <div class="space-y-6">
              <!-- Pending Places -->
              <div v-if="pendingPlacesList.length > 0" class="bg-orange-50/50 dark:bg-orange-500/5 border border-orange-200 dark:border-orange-500/20 rounded-3xl p-6">
                <h3 class="text-orange-600 dark:text-orange-400 font-bold mb-4 flex items-center gap-2">
                  <Icon icon="ph:warning-circle-fill" />
                  Lieux en attente de validation ({{ pendingPlacesList.length }})
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div v-for="place in pendingPlacesList" :key="place.id" class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-gray-100 dark:border-slate-700 shadow-sm relative">
                    <span class="absolute top-4 right-4 bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-1 rounded-full">EN ATTENTE</span>
                    <h4 class="font-bold dark:text-white text-lg pr-16 truncate mb-1">{{ place.name }}</h4>
                    <p class="text-xs text-gray-500 mb-2">{{ place.type }} • {{ place.category }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-2 min-h-[40px]">{{ place.description || 'Aucune description fournie.' }}</p>
                    <div class="text-xs text-gray-500 mb-4 bg-gray-50 dark:bg-slate-900 p-2 rounded-lg">
                      📍 Lat: {{ place.latitude.toFixed(4) }}<br>
                      📍 Lng: {{ place.longitude.toFixed(4) }}<br>
                      👤 Par: <span class="font-semibold">{{ place.added_by }}</span>
                    </div>
                    <div class="flex gap-2">
                      <button @click="approvePlace(place.id)" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 rounded-xl text-sm transition-colors flex items-center justify-center gap-1">
                        <Icon icon="ph:check-bold" /> Approuver
                      </button>
                      <button @click="deletePlace(place.id)" class="flex-1 bg-red-100 hover:bg-red-200 text-red-600 font-bold py-2 rounded-xl text-sm transition-colors flex items-center justify-center gap-1">
                        <Icon icon="ph:x-bold" /> Rejeter
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Approved Places -->
              <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center">
                  <h3 class="font-bold text-lg dark:text-white">Lieux Approuvés</h3>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full text-left border-collapse">
                    <thead>
                      <tr class="bg-gray-50/50 dark:bg-slate-800/50">
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Catégorie</th>
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ajouté par</th>
                        <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                      <tr v-for="place in approvedPlacesList" :key="place.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="py-4 px-6 font-medium dark:text-white">{{ place.name }}</td>
                        <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-300">
                          <span class="bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-md text-xs">{{ place.category }}</span>
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-500 dark:text-gray-400">{{ place.added_by }}</td>
                        <td class="py-4 px-6 text-right">
                          <button @click="deletePlace(place.id)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="Supprimer">
                            <Icon icon="ph:trash" class="w-5 h-5" />
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </template>

          <!-- TAB: MESSAGES -->
          <template v-if="currentTab === 'messages'">
            <div class="bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 rounded-3xl shadow-sm overflow-hidden">
              <div class="p-6 border-b border-gray-100 dark:border-slate-700">
                <h3 class="font-bold text-lg dark:text-white">Logs de Messagerie (50 derniers)</h3>
                <p class="text-sm text-gray-500">Pour des raisons de confidentialité, ce log est principalement à but de débogage.</p>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="bg-gray-50/50 dark:bg-slate-800/50">
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">De</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">À</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Contenu</th>
                      <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    <tr v-for="msg in messages" :key="msg.id" class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                      <td class="py-4 px-6 text-sm text-gray-500 whitespace-nowrap">{{ new Date(msg.created_at).toLocaleString() }}</td>
                      <td class="py-4 px-6 font-medium dark:text-white text-sm">{{ msg.sender?.name || 'Inconnu' }}</td>
                      <td class="py-4 px-6 font-medium dark:text-white text-sm">{{ msg.receiver?.name || 'Inconnu' }}</td>
                      <td class="py-4 px-6 text-sm text-gray-600 dark:text-gray-300 max-w-xs truncate" :title="msg.content">{{ msg.content }}</td>
                      <td class="py-4 px-6">
                        <span v-if="msg.is_read" class="text-xs font-bold text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-full">Lu</span>
                        <span v-else class="text-xs font-bold text-gray-500 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">Non lu</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>

        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import { adminService } from '../services/adminService'

const router = useRouter()
const currentTab = ref('dashboard')
const loading = ref(true)
const mobileMenuOpen = ref(false)

const stats = ref(null)
const users = ref([])
const places = ref([])
const messages = ref([])

const tabTitles = {
  dashboard: "Vue d'ensemble",
  users: "Gestion des Utilisateurs",
  places: "Base de données des Lieux",
  messages: "Activité Messagerie"
}

const pendingPlacesList = computed(() => places.value.filter(p => p.status === 'pending'))
const approvedPlacesList = computed(() => places.value.filter(p => p.status === 'approved'))

onMounted(async () => {
  if (!adminService.isAuthenticated()) {
    router.push('/admin/login')
    return
  }
  
  try {
    const isValid = await adminService.verify()
    if (!isValid) {
      adminService.logout()
      router.push('/admin/login')
      return
    }
    await loadData()
  } catch (e) {
    console.error("Erreur d'authentification admin", e)
    router.push('/admin/login')
  }
})

const loadData = async () => {
  loading.value = true
  try {
    const [s, u, p, m] = await Promise.all([
      adminService.getStats(),
      adminService.getUsers(),
      adminService.getPlaces(),
      adminService.getMessages()
    ])
    stats.value = s
    users.value = u
    places.value = p
    messages.value = m
  } catch (e) {
    console.error(e)
    alert("Erreur lors du chargement des données.")
  } finally {
    loading.value = false
  }
}

const deleteUser = async (id) => {
  if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur définitivement ?")) {
    try {
      await adminService.deleteUser(id)
      users.value = users.value.filter(u => u.id !== id)
      stats.value.totalUsers--
    } catch (e) { alert(e.message) }
  }
}

const approvePlace = async (id) => {
  try {
    await adminService.approvePlace(id)
    const p = places.value.find(p => p.id === id)
    if (p) p.status = 'approved'
    stats.value.pendingPlaces--
    stats.value.approvedPlaces++
  } catch (e) { alert(e.message) }
}

const deletePlace = async (id) => {
  if (confirm("Êtes-vous sûr de vouloir supprimer/rejeter ce lieu ?")) {
    try {
      await adminService.deletePlace(id)
      const p = places.value.find(p => p.id === id)
      if (p) {
        if (p.status === 'pending') stats.value.pendingPlaces--
        else stats.value.approvedPlaces--
      }
      places.value = places.value.filter(p => p.id !== id)
    } catch (e) { alert(e.message) }
  }
}

const handleLogout = () => {
  adminService.logout()
  router.push('/admin/login')
}
</script>
