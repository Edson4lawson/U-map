<template>
  <div class="min-h-screen relative overflow-hidden">
    <div class="pt-8 pb-24 px-4 max-w-xl md:max-w-full mx-auto relative z-10 flex flex-col h-screen">
      
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl md:text-4xl font-display font-bold text-white">Messagerie</h1>
        <div v-if="isLoggedIn && !activeChat" class="flex gap-2">
            <button @click="showNewChatModal = true" class="btn-primary text-sm px-4 py-2 flex items-center gap-2">
                <Icon icon="ph:pencil-simple-line-bold" />
                <span>Nouveau</span>
            </button>
             <button @click="logout" class="p-2 text-white/70 hover:text-white transition-colors">
                <Icon icon="ph:sign-out-bold" class="w-6 h-6" />
            </button>
        </div>
        <div v-else-if="activeChat" class="flex items-center gap-3">
            <button @click="activeChat = null" class="text-white">
                <Icon icon="ph:arrow-left-bold" class="w-6 h-6" />
            </button>
            <div class="flex items-center gap-2">
                <img :src="activeChat.avatar || 'https://ui-avatars.com/api/?name=' + activeChat.name" class="w-8 h-8 rounded-full">
                <h2 class="text-white font-bold">{{ activeChat.name }}</h2>
            </div>
        </div>
      </div>

      <!-- Authentication View -->
      <div v-if="!isLoggedIn" class="flex-1 flex flex-col items-center justify-center pt-10 px-4">
        <div class="w-full max-w-md bg-white/10 dark:bg-gray-900/60 backdrop-blur-xl border border-white/20 dark:border-gray-700 rounded-2xl p-8 shadow-2xl">
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
              <Icon icon="ph:chats-teardrop-double-bold" class="w-10 h-10" />
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">{{ authMode === 'login' ? 'Connexion' : 'Inscription' }}</h2>
            <p class="text-gray-300 text-sm">Échangez avec les autres étudiants de l'UAC.</p>
          </div>

          <form @submit.prevent="handleAuth" class="space-y-4">
            <div v-if="authMode === 'register'">
              <label class="block text-sm font-medium text-gray-300 mb-1">Nom complet</label>
              <input v-model="authForm.name" type="text" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-primary outline-none" placeholder="John Doe">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
              <input v-model="authForm.email" type="email" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-primary outline-none" placeholder="etudiant@uac.bj">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-300 mb-1">Mot de passe</label>
              <input v-model="authForm.password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-primary outline-none" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2 mt-6">
              <Icon v-if="loading" icon="ph:spinner-gap-bold" class="animate-spin" />
              <span>{{ authMode === 'login' ? 'Se connecter' : 'S\'inscrire' }}</span>
            </button>
          </form>

          <button @click="toggleAuthMode" class="w-full text-primary text-sm font-medium mt-6 hover:underline">
            {{ authMode === 'login' ? 'Pas encore de compte ? Créer un compte' : 'Déjà un compte ? Se connecter' }}
          </button>
        </div>
      </div>

      <!-- Main Messaging Interface -->
      <div v-else class="flex-1 flex flex-col min-h-0">
        
        <!-- Conversation Detail (Active Chat) -->
        <div v-if="activeChat" class="flex-1 flex flex-col min-h-0 bg-white/10 dark:bg-gray-900/60 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl mb-24">
           <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
              <div v-for="(msg, index) in chatMessages" :key="index" :class="isMyMessage(msg) ? 'flex justify-end' : 'flex justify-start'">
                 <div class="max-w-[80%] flex flex-col" :class="isMyMessage(msg) ? 'items-end' : 'items-start'">
                    <div :class="isMyMessage(msg) ? 'bg-primary text-white rounded-2xl rounded-tr-none' : 'bg-white/10 text-gray-200 rounded-2xl rounded-tl-none'" class="p-4 shadow-lg">
                       <p class="text-sm">{{ msg.content }}</p>
                    </div>
                    <span v-if="!activeChat.isAI" class="text-[9px] text-gray-500 mt-1 flex items-center gap-1">
                        <Icon icon="ph:clock" />
                        {{ getExpirationText(msg.created_at) }}
                    </span>
                 </div>
              </div>
              <div v-if="isTyping" class="flex justify-start">
                 <div class="bg-white/10 p-4 rounded-2xl rounded-tl-none flex gap-1">
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                 </div>
              </div>
           </div>

           <!-- Message Input -->
           <div class="p-4 border-t border-white/10 flex gap-2">
              <input v-model="messageInput" @keyup.enter="handleSendMessage" type="text" :placeholder="activeChat.isAI ? 'Demandez moi n\'importe quoi...' : 'Écrivez un message éphémère...'" class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-white focus:ring-primary focus:border-primary outline-none">
              <button @click="handleSendMessage" class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white shadow-lg hover:scale-105 transition-transform">
                 <Icon icon="ph:paper-plane-right-fill" />
              </button>
           </div>
        </div>

        <!-- Conversations List -->
        <div v-else class="space-y-4">
           
           <!-- Meta AI Permanent Contact -->
           <div @click="selectAIChat" class="p-4 bg-gradient-to-r from-indigo-600/20 to-primary/20 backdrop-blur-xl border border-indigo-500/30 rounded-2xl cursor-pointer hover:scale-[1.02] transition-all flex items-center gap-4">
              <div class="w-12 h-12 bg-gradient-to-tr from-indigo-600 to-primary rounded-full flex items-center justify-center shadow-lg">
                 <Icon icon="ph:sparkle-fill" class="text-white w-6 h-6" />
              </div>
              <div class="flex-1">
                 <h3 class="text-white font-bold flex items-center gap-2">
                    U-map AI 
                    <span class="text-[10px] bg-indigo-500 text-white px-2 py-0.5 rounded-full uppercase">Assistant</span>
                 </h3>
                 <p class="text-sm text-indigo-300">Votre compagnon de campus intelligent</p>
              </div>
              <Icon icon="ph:caret-right-bold" class="text-indigo-400" />
           </div>

           <!-- Student Chats List -->
           <div class="bg-white/10 dark:bg-gray-900/60 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden">
              <div v-if="conversations.length === 0" class="p-8 text-center text-gray-400">
                 <Icon icon="ph:chat-teardrop-dots" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                 <p>Commencez une discussion éphémère avec un étudiant.</p>
              </div>
              
              <div v-else class="divide-y divide-white/10">
                 <div v-for="chat in conversations" :key="chat.id" @click="selectChat(chat)" class="p-4 hover:bg-white/5 transition-colors cursor-pointer flex gap-4 items-center">
                    <img :src="chat.avatar || 'https://ui-avatars.com/api/?name=' + chat.name" class="w-12 h-12 rounded-full border-2 border-white/10">
                    <div class="flex-1 min-w-0">
                       <h3 class="text-white font-semibold truncate">{{ chat.name }}</h3>
                       <p class="text-sm text-gray-400 truncate">Cliquez pour discuter</p>
                    </div>
                    <span class="text-[10px] text-gray-500">7 jours</span>
                 </div>
              </div>
           </div>
        </div>
      </div>

      <!-- New Chat Modal -->
      <div v-if="showNewChatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-md p-6 shadow-xl border border-gray-200 dark:border-gray-800 h-[60vh] flex flex-col">
          <div class="flex justify-between items-center mb-4">
             <h3 class="text-xl font-bold dark:text-white">Chercher un étudiant</h3>
             <button @click="showNewChatModal = false" class="text-gray-500"><Icon icon="ph:x-bold" class="w-6 h-6" /></button>
          </div>
          <input v-model="searchQuery" type="text" class="w-full bg-gray-100 dark:bg-gray-800 border-none rounded-xl py-3 px-4 mb-4 dark:text-white" placeholder="Nom de l'étudiant...">
          
          <div class="flex-1 overflow-y-auto space-y-2">
             <div v-for="user in filteredStudents" :key="user.id" @click="startNewConversation(user)" class="flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl cursor-pointer">
                <img :src="'https://ui-avatars.com/api/?name=' + user.name" class="w-10 h-10 rounded-full">
                <p class="font-medium dark:text-white">{{ user.name }}</p>
             </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { aiService } from '../services/aiService'
import { messageService } from '../services/messageService'

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
const conversations = ref([]) // List of students we have chatted with

const authForm = ref({ name: '', email: '', password: '' })

onMounted(async () => {
    if (isLoggedIn.value) {
        await loadStudents()
        await loadConversations()
    }
})

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
}

const selectAIChat = () => {
    activeChat.value = { id: 'ai', name: 'U-map AI', isAI: true, avatar: null }
    chatMessages.value = [
        { role: 'assistant', content: 'Bonjour ! Comment puis-je t\'aider sur le campus aujourd\'hui ?' }
    ]
}

const selectChat = async (student) => {
    activeChat.value = { ...student, isAI: false }
    await loadMessages()
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
        try {
            const response = await aiService.askCampusAI(content)
            chatMessages.value.push({ role: 'assistant', content: response })
        } catch (e) {
            chatMessages.value.push({ role: 'assistant', content: "Erreur de connexion avec l'IA." })
        } finally {
            isTyping.value = false
            scrollToBottom()
        }
    } else {
        try {
            const newMsg = await messageService.sendMessage(activeChat.value.id, content)
            chatMessages.value.push(newMsg)
            scrollToBottom()
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
    return `Expire dans ${diff}j`
}

const scrollToBottom = () => {
    nextTick(() => {
        const container = document.getElementById('chat-messages')
        if (container) container.scrollTop = container.scrollHeight
    })
}

const logoutUser = () => {
    authService.logout()
    isLoggedIn.value = false
}
</script>
