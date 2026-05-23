<template>
  <div class="h-screen w-full relative overflow-hidden bg-slate-950 flex flex-col text-slate-100">
    
    <!-- Decorative background elements -->
    <div class="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-blue-500/10 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-indigo-500/10 blur-[120px] pointer-events-none"></div>

    <!-- MAIN WRAPPER -->
    <div class="flex-1 flex flex-col h-full relative z-10 overflow-hidden">
      
      <!-- AUTHENTICATION VIEW (If not logged in) -->
      <div v-if="!isLoggedIn" class="flex-1 flex flex-col items-center justify-center p-6 overflow-y-auto">
        <div class="w-full max-w-md bg-white/5 dark:bg-slate-900/40 backdrop-blur-2xl border border-white/10 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-2xl"></div>
          
          <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/20">
              <Icon icon="ph:chats-teardrop-double-bold" class="w-8 h-8 text-white" />
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">{{ authMode === 'login' ? 'Connexion U-Map' : 'Rejoindre U-Map' }}</h2>
            <p class="text-slate-400 text-sm mt-1">Échangez de manière éphémère et sécurisée avec les étudiants du campus.</p>
          </div>

          <form @submit.prevent="handleAuth" class="space-y-4">
            <div v-if="authMode === 'register'">
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nom complet</label>
              <div class="relative">
                <Icon icon="ph:user" class="absolute left-4 top-3.5 text-slate-500 w-5 h-5" />
                <input v-model="authForm.name" type="text" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all" placeholder="John Doe">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Email universitaire</label>
              <div class="relative">
                <Icon icon="ph:envelope" class="absolute left-4 top-3.5 text-slate-500 w-5 h-5" />
                <input v-model="authForm.email" type="email" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all" placeholder="etudiant@uac.bj">
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Mot de passe</label>
              <div class="relative">
                <Icon icon="ph:lock-key" class="absolute left-4 top-3.5 text-slate-500 w-5 h-5" />
                <input v-model="authForm.password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all" placeholder="••••••••">
              </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-500/25 flex justify-center items-center gap-2 mt-6 transform active:scale-98">
              <Icon v-if="loading" icon="ph:spinner-gap-bold" class="animate-spin" />
              <span>{{ authMode === 'login' ? 'Se connecter' : "S'inscrire" }}</span>
            </button>
          </form>

          <button @click="toggleAuthMode" class="w-full text-blue-400 text-xs font-semibold mt-6 hover:underline text-center block">
            {{ authMode === 'login' ? "Pas encore de compte ? Créer un compte" : "Déjà inscrit ? Connectez-vous" }}
          </button>
        </div>
      </div>

      <!-- MESSAGING VIEW (If logged in) -->
      <div v-else class="flex-1 flex flex-col h-full overflow-hidden">
        
        <!-- CASE A: CONVERSATION LIST (chat query is empty) -->
        <div v-if="!activeChat" class="flex-1 flex flex-col h-full overflow-hidden">
          
          <!-- Modern Top Bar -->
          <header class="pt-6 pb-4 px-6 border-b border-white/10 backdrop-blur-md bg-slate-950/80 flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-black bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">Messagerie</h1>
              <p class="text-xs text-slate-400 mt-0.5">Discussions éphémères de 7 jours</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="showNewChatModal = true" class="w-10 h-10 rounded-full bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 flex items-center justify-center transition-all border border-blue-500/20" title="Nouvelle discussion">
                <Icon icon="ph:pencil-simple-line-bold" class="w-5 h-5" />
              </button>
              <button @click="logout" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition-all" title="Se déconnecter">
                <Icon icon="ph:sign-out-bold" class="w-5 h-5" />
              </button>
            </div>
          </header>

          <!-- List Content -->
          <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            
            <!-- Dynamic AI Assistant Card -->
            <div @click="selectAIChat" class="p-5 bg-gradient-to-br from-indigo-900/40 via-purple-900/30 to-blue-900/20 backdrop-blur-xl border border-indigo-500/20 rounded-2xl cursor-pointer hover:scale-[1.02] active:scale-98 transition-all flex items-center gap-4 relative overflow-hidden group shadow-lg shadow-indigo-500/5">
              <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
              <div class="w-12 h-12 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <Icon icon="ph:sparkle-fill" class="text-white w-6 h-6 animate-pulse" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h3 class="text-white font-bold tracking-tight">U-Map Copilot AI</h3>
                  <span class="text-[9px] bg-indigo-500 text-white font-extrabold px-1.5 py-0.5 rounded-full uppercase tracking-wider">OFFICIEL</span>
                </div>
                <p class="text-xs text-indigo-200/70 mt-0.5 truncate">Votre guide intelligent de campus UAC disponible 24/7.</p>
              </div>
              <Icon icon="ph:caret-right-bold" class="text-indigo-400 group-hover:translate-x-1 transition-transform" />
            </div>

            <!-- Custom Separator -->
            <div class="flex items-center gap-3">
              <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Conversations actives</span>
              <div class="flex-1 h-[1px] bg-white/10"></div>
            </div>

            <!-- List of Chats -->
            <div class="space-y-3">
              <div v-if="conversations.length === 0" class="p-12 text-center bg-white/5 border border-white/10 rounded-2xl">
                <Icon icon="ph:chat-teardrop-dots" class="w-12 h-12 text-slate-600 mx-auto mb-3" />
                <p class="text-sm text-slate-400">Aucune discussion en cours.</p>
                <button @click="showNewChatModal = true" class="mt-4 text-xs font-semibold text-blue-400 hover:underline">
                  Commencer un chat éphémère
                </button>
              </div>
              
              <div v-else v-for="chat in conversations" :key="chat.id" @click="selectChat(chat)" class="p-4 bg-white/5 hover:bg-white/10 border border-white/5 rounded-2xl transition-all duration-200 cursor-pointer flex gap-4 items-center group shadow-md hover:shadow-xl">
                <div class="relative">
                  <img :src="chat.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(chat.name) + '&background=0284c7&color=fff'" class="w-12 h-12 rounded-full border-2 border-white/10 object-cover">
                  <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-slate-900 rounded-full"></span>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-white font-semibold truncate group-hover:text-blue-400 transition-colors">{{ chat.name }}</h3>
                  <p class="text-xs text-slate-400 truncate mt-0.5">Discuter de façon éphémère (7 jours)</p>
                </div>
                <div class="flex flex-col items-end gap-1.5">
                  <span class="text-[10px] bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded-full font-bold border border-blue-500/20">7 jours</span>
                  <Icon icon="ph:caret-right-bold" class="text-slate-500 group-hover:translate-x-1 transition-transform" />
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- CASE B: FULL SCREEN PREMIUM ACTIVE CHAT VIEW -->
        <div v-else class="flex-1 flex flex-col h-full bg-slate-950 overflow-hidden relative">
          
          <!-- Premium Chat Header -->
          <header class="py-4 px-6 border-b border-white/10 bg-slate-900/60 backdrop-blur-2xl flex items-center justify-between z-20">
            <div class="flex items-center gap-3 min-w-0">
              <button @click="closeChat" class="w-10 h-10 rounded-full hover:bg-white/10 text-slate-300 hover:text-white flex items-center justify-center transition-all" title="Retour">
                <Icon icon="ph:arrow-left-bold" class="w-5 h-5" />
              </button>
              
              <div class="flex items-center gap-3 min-w-0">
                <div class="relative">
                  <div v-if="activeChat.isAI" class="w-10 h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                    <Icon icon="ph:sparkle-fill" class="text-white w-5 h-5" />
                  </div>
                  <img v-else :src="activeChat.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(activeChat.name) + '&background=0284c7&color=fff'" class="w-10 h-10 rounded-full border-2 border-white/10 object-cover">
                  <span v-if="!activeChat.isAI" class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-slate-950 rounded-full"></span>
                </div>
                <div class="min-w-0">
                  <h2 class="text-white font-bold truncate tracking-tight text-base">{{ activeChat.name }}</h2>
                  <p class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ activeChat.isAI ? 'Assistant intelligent U-Map' : 'En ligne' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions Header -->
            <div class="flex items-center gap-1.5">
              <button v-if="!activeChat.isAI" @click="showReportModal = true" class="w-10 h-10 rounded-full bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-all border border-red-500/20" title="Signaler cet utilisateur">
                <Icon icon="ph:flag-bold" class="w-5 h-5" />
              </button>
            </div>
          </header>

          <!-- Chat Messages Body -->
          <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4 bg-slate-950" id="chat-messages">
            
            <!-- Ephemeral Notification Notice -->
            <div v-if="!activeChat.isAI" class="max-w-md mx-auto p-4 bg-blue-500/5 border border-blue-500/20 rounded-2xl text-center mb-6">
              <Icon icon="ph:clock-countdown-bold" class="w-6 h-6 text-blue-400 mx-auto mb-1.5 animate-pulse" />
              <h4 class="text-xs font-bold text-blue-300 uppercase tracking-wider">Sécurité Éphémère Activée</h4>
              <p class="text-[11px] text-slate-400 mt-1">Tous vos messages sur U-Map s'auto-détruisent après 7 jours pour préserver la vie privée sur le campus.</p>
            </div>

            <!-- Messages Loop -->
            <div v-for="(msg, index) in chatMessages" :key="index" :class="[isMyMessage(msg) ? 'flex justify-end' : 'flex justify-start']" class="w-full">
              <div class="max-w-[75%] flex flex-col" :class="[isMyMessage(msg) ? 'items-end' : 'items-start']">
                
                <!-- Bubble Wrapper -->
                <div :class="[
                  isMyMessage(msg) 
                    ? 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-blue-500/10' 
                    : 'bg-white/10 dark:bg-slate-800/80 text-slate-200 rounded-2xl rounded-tl-none border border-white/5'
                ]" class="px-4 py-3 shadow-lg">
                  <p class="text-sm leading-relaxed whitespace-pre-wrap select-text">{{ msg.content }}</p>
                </div>

                <!-- Expire badge -->
                <span v-if="!activeChat.isAI && msg.created_at" class="text-[9px] text-slate-500 mt-1.5 flex items-center gap-1 px-1">
                  <Icon icon="ph:clock" class="w-3.5 h-3.5" />
                  {{ getExpirationText(msg.created_at) }}
                </span>
              </div>
            </div>

            <!-- AI Typing Indicator -->
            <div v-if="isTyping" class="flex justify-start">
              <div class="bg-white/10 dark:bg-slate-800/80 border border-white/5 px-4 py-3 rounded-2xl rounded-tl-none flex items-center gap-1.5">
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
              </div>
            </div>

          </div>

          <!-- Premium Input Bar -->
          <footer class="p-4 border-t border-white/10 bg-slate-900/40 backdrop-blur-2xl z-20">
            <div class="max-w-4xl mx-auto flex items-center gap-3">
              <div class="flex-1 bg-white/5 border border-white/10 rounded-2xl flex items-center px-4 focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:border-blue-500 transition-all">
                <input v-model="messageInput" @keyup.enter="handleSendMessage" type="text" :placeholder="activeChat.isAI ? 'Demander quelque chose à l\'IA...' : 'Écrire un message éphémère...'" class="w-full bg-transparent border-none py-3.5 text-white placeholder-slate-500 outline-none text-sm">
                
                <!-- Quick Send Icon triggers -->
                <button v-if="activeChat.isAI" @click="messageInput = 'Où se trouve la BU ?'" class="p-1 hover:text-indigo-400 text-slate-500 transition-colors" title="Idée rapide">
                  <Icon icon="ph:lightbulb" class="w-5 h-5" />
                </button>
              </div>
              
              <button @click="handleSendMessage" class="w-12 h-12 bg-gradient-to-tr from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20 hover:scale-105 active:scale-95 transition-all">
                <Icon icon="ph:paper-plane-right-fill" class="w-5 h-5" />
              </button>
            </div>
          </footer>

        </div>

      </div>

    </div>

    <!-- NEW DISCUSSION MODAL -->
    <div v-if="showNewChatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="bg-slate-900 border border-white/10 rounded-3xl w-full max-w-md p-6 shadow-2xl flex flex-col h-[70vh] relative overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <div class="flex justify-between items-center mb-5">
           <div>
             <h3 class="text-xl font-bold text-white tracking-tight">Nouvelle discussion</h3>
             <p class="text-xs text-slate-400 mt-0.5">Recherchez un étudiant du campus</p>
           </div>
           <button @click="showNewChatModal = false" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 text-slate-400 flex items-center justify-center transition-all">
             <Icon icon="ph:x-bold" class="w-5 h-5" />
           </button>
        </div>

        <div class="relative mb-4">
          <Icon icon="ph:magnifying-glass" class="absolute left-4 top-3.5 text-slate-500 w-5 h-5" />
          <input v-model="searchQuery" type="text" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 pl-12 pr-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all text-sm" placeholder="Saisir le nom de l'étudiant...">
        </div>
        
        <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
           <div v-for="user in filteredStudents" :key="user.id" @click="startNewConversation(user)" class="flex items-center gap-3 p-3 hover:bg-white/5 rounded-2xl cursor-pointer transition-all border border-transparent hover:border-white/5">
              <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=0284c7&color=fff'" class="w-10 h-10 rounded-full object-cover">
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-white truncate text-sm">{{ user.name }}</p>
                <p class="text-[11px] text-slate-400 truncate">Étudiant de l'UAC</p>
              </div>
              <Icon icon="ph:arrow-right-bold" class="w-4 h-4 text-slate-500" />
           </div>
           <div v-if="filteredStudents.length === 0" class="text-center py-8 text-slate-500 text-sm">
             Aucun étudiant trouvé pour "{{ searchQuery }}"
           </div>
        </div>
      </div>
    </div>

    <!-- REPORT USER MODAL -->
    <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="bg-slate-900 border border-red-500/20 rounded-3xl w-full max-w-md p-6 shadow-2xl relative overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <div class="flex justify-between items-center mb-5">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 flex items-center justify-center">
               <Icon icon="ph:flag-bold" />
             </div>
             <h3 class="text-lg font-bold text-white tracking-tight">Signaler cet utilisateur</h3>
           </div>
           <button @click="showReportModal = false" class="w-9 h-9 rounded-full bg-white/5 hover:bg-white/10 text-slate-400 flex items-center justify-center transition-all">
             <Icon icon="ph:x-bold" class="w-5 h-5" />
           </button>
        </div>

        <p class="text-xs text-slate-400 mb-4 leading-relaxed">
          U-Map s'engage à assurer la sécurité du campus. Décrivez le motif du signalement. L'équipe d'administration prendra des mesures de restriction immédiates si nécessaire.
        </p>

        <form @submit.prevent="submitReport" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Motif du signalement</label>
            <select v-model="reportReason" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-red-500/50 outline-none text-sm mb-3">
              <option value="Harcèlement ou intimidation">Harcèlement ou intimidation</option>
              <option value="Contenu inapproprié ou offensant">Contenu inapproprié ou offensant</option>
              <option value="Faux profil étudiant">Faux profil étudiant</option>
              <option value="Spam / comportement malveillant">Spam / comportement malveillant</option>
              <option value="Autre motif">Autre motif (Préciser ci-dessous)</option>
            </select>

            <textarea v-model="customReason" rows="3" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-red-500/50 outline-none text-sm" placeholder="Fournissez plus de détails sur le comportement signalé..."></textarea>
          </div>

          <div class="flex gap-3 mt-6">
            <button type="button" @click="showReportModal = false" class="flex-1 bg-white/5 hover:bg-white/10 text-slate-300 font-semibold py-3 rounded-2xl text-sm transition-all">
              Annuler
            </button>
            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-500 text-white font-semibold py-3 rounded-2xl text-sm shadow-lg shadow-red-600/20 transition-all flex items-center justify-center gap-2">
              <Icon v-if="reporting" icon="ph:spinner-gap-bold" class="animate-spin" />
              <span>Envoyer le signalement</span>
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { aiService } from '../services/aiService'
import { messageService } from '../services/messageService'

const router = useRouter()
const route = useRoute()

const isLoggedIn = ref(authService.isAuthenticated())
const activeChat = ref(null)
const chatMessages = ref([])
const messageInput = ref('')
const isTyping = ref(false)
const authMode = ref('login')
const loading = ref(false)
const showNewChatModal = ref(false)
const searchQuery = ref('')
const students = ref([])
const conversations = ref([]) // list of students we have chatted with

// Report Modal variables
const showReportModal = ref(false)
const reportReason = ref('Harcèlement ou intimidation')
const customReason = ref('')
const reporting = ref(false)

const authForm = ref({ name: '', email: '', password: '' })

onMounted(async () => {
    if (isLoggedIn.value) {
        await loadStudents()
        await loadConversations()
        
        // Sync active chat with URL query ?chat=
        if (route.query.chat) {
            await syncChatFromQuery()
        }
    }
})

// Watch route query to switch active chats dynamically
watch(() => route.query.chat, async (newChatId) => {
    if (newChatId) {
        await syncChatFromQuery()
    } else {
        activeChat.value = null
        chatMessages.value = []
    }
})

const syncChatFromQuery = async () => {
    const chatId = route.query.chat
    if (chatId === 'ai') {
        selectAIChat()
    } else {
        // Try finding student in loaded list or fetch
        let student = conversations.value.find(c => c.id == chatId) || students.value.find(s => s.id == chatId)
        if (!student) {
            // Fallback load
            await loadStudents()
            await loadConversations()
            student = conversations.value.find(c => c.id == chatId) || students.value.find(s => s.id == chatId)
        }
        if (student) {
            activeChat.value = { ...student, isAI: false }
            await loadMessages()
        } else {
            // clear query if invalid
            closeChat()
        }
    }
}

const loadConversations = async () => {
    try {
        conversations.value = await messageService.getConversations()
    } catch (e) { console.error(e) }
}

const loadStudents = async () => {
    try {
        students.value = await messageService.getStudents()
    } catch (e) { console.error(e) }
}

const filteredStudents = computed(() => {
    if (!searchQuery.value) return students.value
    return students.value.filter(s => s.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

const toggleAuthMode = () => authMode.value = authMode.value === 'login' ? 'register' : 'login'

const handleAuth = async () => {
    loading.value = true
    try {
        if (authMode.value === 'login') {
            await authService.login(authForm.value.email, authForm.value.password)
        } else {
            await authService.register(authForm.value)
        }
        isLoggedIn.value = true
        await loadStudents()
        await loadConversations()
    } catch (err) {
        alert(err.message)
    } finally {
        loading.value = false
    }
}

const logout = () => {
    authService.logout()
    isLoggedIn.value = false
    activeChat.value = null
    router.replace({ query: {} })
}

const selectAIChat = () => {
    activeChat.value = { id: 'ai', name: 'U-Map Copilot AI', isAI: true, avatar: null }
    chatMessages.value = [
        { role: 'assistant', content: "Bonjour ! Je suis l'intelligence artificielle officielle de l'UAC. Comment puis-je t'aider sur le campus aujourd'hui ?" }
    ]
    if (route.query.chat !== 'ai') {
        router.replace({ query: { chat: 'ai' } })
    }
    scrollToBottom()
}

const selectChat = async (student) => {
    activeChat.value = { ...student, isAI: false }
    if (route.query.chat != student.id) {
        router.replace({ query: { chat: student.id } })
    }
    await loadMessages()
}

const closeChat = () => {
    activeChat.value = null
    router.replace({ query: {} })
}

const loadMessages = async () => {
    if (!activeChat.value || activeChat.value.isAI) return
    try {
        chatMessages.value = await messageService.getMessages(activeChat.value.id)
        scrollToBottom()
    } catch (e) { console.error(e) }
}

const handleSendMessage = async () => {
    if (!messageInput.value.trim() || isTyping.value) return
    const content = messageInput.value
    messageInput.value = ''

    if (activeChat.value.isAI) {
        chatMessages.value.push({ role: 'user', content })
        isTyping.value = true
        scrollToBottom()
        try {
            const response = await aiService.askCampusAI(content)
            chatMessages.value.push({ role: 'assistant', content: response })
        } catch (e) {
            chatMessages.value.push({ role: 'assistant', content: "Désolé, je rencontre des difficultés temporaires. Réessayez." })
        } finally {
            isTyping.value = false
            scrollToBottom()
        }
    } else {
        try {
            const newMsg = await messageService.sendMessage(activeChat.value.id, content)
            chatMessages.value.push(newMsg)
            scrollToBottom()
            await loadConversations() // update sidebar if needed
        } catch (e) { console.error(e) }
    }
}

const startNewConversation = (user) => {
    if (!conversations.value.find(c => c.id === user.id)) {
        conversations.value.unshift(user)
    }
    showNewChatModal.value = false
    selectChat(user)
}

const isMyMessage = (msg) => {
    if (activeChat.value.isAI) return msg.role === 'user'
    const me = authService.getCurrentUser()
    return msg.sender_id === me.id
}

const getExpirationText = (createdAt) => {
    const created = new Date(createdAt)
    const now = new Date()
    const diff = 7 - Math.floor((now - created) / (1000 * 60 * 60 * 24))
    if (diff <= 0) return "S'autodétruit sous peu"
    return `Expire dans ${diff}j`
}

const scrollToBottom = () => {
    nextTick(() => {
        const container = document.getElementById('chat-messages')
        if (container) container.scrollTop = container.scrollHeight
    })
}

const submitReport = async () => {
    if (reporting.value) return
    reporting.value = true
    const finalReason = reportReason.value === 'Autre motif' 
        ? `Autre: ${customReason.value}` 
        : `${reportReason.value}. Détails: ${customReason.value}`
        
    try {
        const token = authService.getToken()
        const response = await fetch(`${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/users/${activeChat.value.id}/report`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason: finalReason })
        })
        
        if (response.ok) {
            alert('L\'utilisateur a bien été signalé aux administrateurs. Merci de veiller à la sécurité de l\'UAC.')
            showReportModal.value = false
            customReason.value = ''
        } else {
            throw new Error('Erreur réseau lors de l\'envoi.')
        }
    } catch (e) {
        alert(e.message)
    } finally {
        reporting.value = false
    }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
