<template>
  <div class="h-full w-full relative overflow-hidden bg-white dark:bg-slate-950 flex flex-col text-slate-900 dark:text-slate-100">

    <!-- Decorative background elements -->
    <div class="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-blue-500/10 blur-[120px] pointer-events-none dark:block hidden"></div>
    <div class="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-indigo-500/10 blur-[120px] pointer-events-none dark:block hidden"></div>

    <!-- MAIN WRAPPER -->
    <div class="flex-1 flex flex-col h-full relative z-10 overflow-hidden">
      
      <!-- AUTHENTICATION VIEW (If not logged in) - Redirect to login page -->
      <div v-if="!isLoggedIn" class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <div class="w-full max-w-md bg-gray-50 dark:bg-slate-900/40 backdrop-blur-2xl border border-gray-200 dark:border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden text-center">
          <div class="w-16 h-16 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/20">
            <Icon icon="ph:chats-teardrop-double-bold" class="w-8 h-8 text-white" />
          </div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">Connexion requise</h2>
          <p class="text-gray-500 dark:text-slate-400 text-sm mb-6">Connectez-vous pour accéder à la messagerie du campus.</p>
          <router-link to="/login" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-500/25 flex justify-center items-center gap-2">
            Se connecter
          </router-link>
          <router-link to="/register" class="w-full text-blue-500 dark:text-blue-400 text-xs font-semibold mt-4 hover:underline text-center block">
            Pas encore de compte ? Créer un compte
          </router-link>
        </div>
      </div>

      <!-- MESSAGING VIEW (If logged in) -->
      <div v-else class="flex-1 flex flex-col h-full overflow-hidden">

        <!-- CASE A: CONVERSATION LIST (chat query is empty) -->
        <div v-if="!activeChat" class="flex-1 flex flex-col h-full overflow-hidden">

          <!-- Modern Top Bar -->
          <header class="pt-5 pb-4 px-4 sm:px-6 border-b border-gray-200 dark:border-white/10 backdrop-blur-md bg-white/80 dark:bg-slate-950/80 flex items-center justify-between">
            <div>
              <h1 class="text-2xl sm:text-3xl font-black bg-gradient-to-r from-gray-900 via-gray-700 to-gray-500 dark:from-white dark:via-slate-200 dark:to-slate-400 bg-clip-text text-transparent">Messagerie</h1>
              <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-0.5">Discussions éphémères de 7 jours</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="showNewChatModal = true" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-blue-500/10 hover:bg-blue-500/20 text-blue-500 dark:text-blue-400 flex items-center justify-center transition-all border border-blue-500/20" title="Nouvelle discussion">
                <Icon icon="ph:pencil-simple-line-bold" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
              <button @click="logout" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center transition-all" title="Se déconnecter">
                <Icon icon="ph:sign-out-bold" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
            </div>
          </header>

          <!-- List Content -->
          <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-6 space-y-6">
            
            <!-- Dynamic AI Assistant Card -->
            <div @click="selectAIChat" class="p-5 bg-gradient-to-br from-indigo-50 via-purple-50 to-blue-50 dark:from-indigo-900/40 dark:via-purple-900/30 dark:to-blue-900/20 backdrop-blur-xl border border-indigo-200 dark:border-indigo-500/20 rounded-2xl cursor-pointer hover:scale-[1.02] active:scale-98 transition-all flex items-center gap-4 relative overflow-hidden group shadow-lg shadow-indigo-500/5">
              <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:scale-150 transition-all duration-700"></div>
              <div class="w-12 h-12 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <Icon icon="ph:sparkle-fill" class="text-white w-6 h-6 animate-pulse" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h3 class="text-gray-900 dark:text-white font-bold tracking-tight">U-Map Copilot AI</h3>
                  <span class="text-[9px] bg-indigo-500 text-white font-extrabold px-1.5 py-0.5 rounded-full uppercase tracking-wider">OFFICIEL</span>
                </div>
                <p class="text-xs text-indigo-600/70 dark:text-indigo-200/70 mt-0.5 truncate">Votre guide intelligent de campus UAC disponible 24/7.</p>
              </div>
              <Icon icon="ph:caret-right-bold" class="text-indigo-500 dark:text-indigo-400 group-hover:translate-x-1 transition-transform" />
            </div>

            <!-- Study Status / Study Buddies Section -->
            <div class="p-5 bg-gradient-to-br from-blue-50 via-slate-50 to-indigo-50 dark:from-blue-950/40 dark:via-slate-900/40 dark:to-indigo-950/30 border border-blue-200 dark:border-blue-500/20 rounded-2xl shadow-lg space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center justify-between cursor-pointer" @click="studyStatusExpanded = !studyStatusExpanded">
                   <div class="flex items-center gap-2">
                      <Icon icon="ph:books-bold" class="w-4 h-4 text-blue-500 dark:text-blue-400" /> Mon Statut d'Étude
                   </div>
                   <Icon :icon="studyStatusExpanded ? 'ph:chevron-up-bold' : 'ph:caret-down-bold'" class="w-5 h-5 text-blue-500 dark:text-blue-400 ml-1" />
                </h3>
                <div v-show="studyStatusExpanded" class="space-y-3">
                   <div class="flex flex-col sm:flex-row gap-2">
                      <input v-model="myStudyStatus" type="text" placeholder="Ex: Révise les maths..."
                             class="flex-1 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl py-2 px-3 text-xs text-gray-900 dark:text-white outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-slate-500">
                      <input v-model="myStudyLocation" type="text" placeholder="Ex: BU..."
                             class="w-full sm:w-1/3 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl py-2 px-3 text-xs text-gray-900 dark:text-white outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-slate-500">
                      <button @click="saveMyStudyStatus"
                              class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 px-4 rounded-xl transition-all">
                         Enregistrer
                      </button>
                   </div>

                   <!-- Active Study Buddies list -->
                   <div v-if="studyBuddies.length > 0" class="pt-3 border-t border-gray-200 dark:border-white/5 space-y-2">
                      <h4 class="text-[10px] font-extrabold text-blue-500 dark:text-blue-400 uppercase tracking-wider">Étudiants actifs en ce moment :</h4>
                      <div class="max-h-24 overflow-y-auto space-y-1.5 custom-scrollbar pr-1">
                         <div v-for="buddy in studyBuddies" :key="buddy.id" @click="startNewConversation(buddy)"
                              class="flex items-center justify-between p-2.5 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-colors cursor-pointer border border-gray-200 dark:border-white/5">
                            <div class="min-w-0">
                               <div class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ buddy.name }}</div>
                               <div class="text-[10px] text-gray-500 dark:text-slate-400 truncate flex items-center gap-1"><Icon icon="ph:book-open" class="w-3 h-3 inline flex-shrink-0" /> {{ buddy.study_status }} — <Icon icon="ph:map-pin-fill" class="w-3 h-3 inline flex-shrink-0" /> {{ buddy.study_location }}</div>
                            </div>
                            <span class="text-[9px] bg-blue-500/20 text-blue-600 dark:text-blue-400 font-extrabold px-2 py-1 rounded-lg">Rejoindre</span>
                         </div>
                      </div>
                   </div>
                   <div v-else class="text-[10px] text-gray-500 dark:text-slate-500 italic pt-1">Aucun autre étudiant actif pour le moment.</div>
                </div>
            </div>

            <!-- Custom Separator -->
            <div class="flex items-center gap-3">
              <span class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Conversations actives</span>
              <div class="flex-1 h-[1px] bg-gray-200 dark:bg-white/10"></div>
            </div>

            <!-- List of Chats -->
            <div class="space-y-3">
              <!-- Empty State when no conversations exist -->
              <div v-if="conversations.length === 0" class="p-8 sm:p-10 text-center bg-gradient-to-b from-gray-50 to-blue-50/30 dark:from-slate-900/50 dark:to-blue-950/20 border border-gray-200 dark:border-white/10 rounded-3xl shadow-lg relative overflow-hidden my-2">
                <div class="w-14 h-14 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-xl shadow-blue-500/20 animate-bounce">
                  <Icon icon="ph:paper-plane-tilt-bold" class="w-7 h-7 text-white" />
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Aucune discussion en cours</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5 max-w-sm mx-auto leading-relaxed">
                  Vos conversations sont éphémères et s'auto-détruisent après 7 jours. Lancez votre premier échange avec la communauté de l'UAC !
                </p>
                <div class="flex flex-wrap justify-center gap-3 mt-5">
                  <button @click="showNewChatModal = true" class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-2 transition-all transform active:scale-95">
                    <Icon icon="ph:plus-circle-bold" class="w-4 h-4" />
                    Nouvelle discussion
                  </button>
                  <button @click="selectAIChat" class="px-4 py-2.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 text-xs font-bold rounded-xl border border-indigo-500/20 flex items-center gap-2 transition-all">
                    <Icon icon="ph:sparkle-bold" class="w-4 h-4" />
                    Parler à Copilot AI
                  </button>
                </div>
              </div>
              
              <!-- Active Conversation Threads -->
              <div v-else v-for="chat in conversations" :key="chat.id" @click="selectChat(chat)" class="p-3.5 sm:p-4 bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/5 rounded-2xl transition-all duration-200 cursor-pointer flex gap-3.5 items-center group shadow-sm hover:shadow-md">
                <div class="relative flex-shrink-0">
                  <img :src="chat.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(chat.name) + '&background=0284c7&color=fff'" class="w-12 h-12 rounded-full border-2 border-gray-200 dark:border-white/10 object-cover">
                  <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex justify-between items-baseline mb-0.5">
                    <h3 class="text-gray-900 dark:text-white font-bold text-sm truncate group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">{{ chat.name }}</h3>
                    <span v-if="chat.last_message || chat.last_message_at" class="text-[10px] text-gray-400 dark:text-slate-500 font-medium ml-2 flex-shrink-0">
                      {{ formatTimeAgo(chat.last_message ? chat.last_message.created_at : chat.last_message_at) }}
                    </span>
                  </div>

                  <!-- Snippet or Expired state badge -->
                  <div class="flex items-center gap-1.5">
                    <p v-if="chat.last_message" class="text-xs text-gray-600 dark:text-slate-400 truncate">
                      <span v-if="isMyMessage(chat.last_message)" class="font-semibold text-gray-500 dark:text-slate-400">Vous : </span>
                      {{ chat.last_message.content }}
                    </p>
                    <p v-else class="text-[11px] text-slate-400 dark:text-slate-500 italic flex items-center gap-1 truncate">
                      <Icon icon="ph:lock-key-bold" class="w-3 h-3 text-amber-500 flex-shrink-0" />
                      <span>Messages expirés (7j)</span>
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <span v-if="chat.unread_count > 0" class="w-5 h-5 bg-blue-600 text-white font-black text-[10px] rounded-full flex items-center justify-center shadow-md shadow-blue-500/30 animate-pulse">
                    {{ chat.unread_count }}
                  </span>
                  <Icon icon="ph:caret-right-bold" class="text-gray-400 dark:text-slate-500 group-hover:translate-x-1 transition-transform" />
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- CASE B: FULL SCREEN PREMIUM ACTIVE CHAT VIEW -->
        <div v-else class="flex-1 flex flex-col h-full bg-white dark:bg-slate-950 overflow-hidden relative">

          <!-- Premium Chat Header -->
          <header class="py-3 px-3 sm:px-6 border-b border-gray-200 dark:border-white/10 bg-gray-50/60 dark:bg-slate-900/60 backdrop-blur-2xl flex items-center justify-between z-20">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
              <button @click="closeChat" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white flex items-center justify-center transition-all" title="Retour">
                <Icon icon="ph:arrow-left-bold" class="w-5 h-5" />
              </button>

              <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <div class="relative">
                  <div v-if="activeChat?.isAI" class="w-9 h-9 sm:w-10 sm:h-10 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                    <Icon icon="ph:sparkle-fill" class="text-white w-4.5 h-4.5 sm:w-5 sm:h-5" />
                  </div>
                  <img v-else :src="activeChat?.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(activeChat?.name) + '&background=0284c7&color=fff'" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full border-2 border-gray-200 dark:border-white/10 object-cover">
                  <span v-if="!activeChat?.isAI" class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-950 rounded-full"></span>
                </div>
                <div class="min-w-0">
                  <h2 class="text-gray-900 dark:text-white font-bold truncate tracking-tight text-sm sm:text-base">{{ activeChat?.name }}</h2>
                  <p class="text-[9px] sm:text-[10px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ activeChat?.isAI ? 'Assistant intelligent' : 'En ligne' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions Header -->
            <div class="flex items-center gap-1.5">
              <button v-if="!activeChat?.isAI" @click="showReportModal = true" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-all border border-red-500/20" title="Signaler cet utilisateur">
                <Icon icon="ph:flag-bold" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
            </div>
          </header>

          <!-- Chat Messages Body -->
          <div class="flex-1 overflow-y-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 bg-gray-50 dark:bg-slate-950" id="chat-messages">

            <!-- Ephemeral Notification Notice -->
            <div v-if="!activeChat?.isAI" class="max-w-md mx-auto p-4 bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/20 rounded-2xl text-center mb-6">
              <Icon icon="ph:clock-countdown-bold" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-500 dark:text-blue-400 mx-auto mb-1.5 animate-pulse" />
              <h4 class="text-[10px] sm:text-xs font-bold text-blue-600 dark:text-blue-300 uppercase tracking-wider">Sécurité Éphémère Activée</h4>
              <p class="text-[10px] sm:text-[11px] text-gray-600 dark:text-slate-400 mt-1">Tous vos messages sur U-Map s'auto-détruisent après 7 jours pour préserver la vie privée sur le campus.</p>
            </div>

            <!-- Empty Conversation Starter (Snapchat / Messenger style) -->
            <div v-if="!activeChat?.isAI && chatMessages.length === 0" class="py-8 px-4 flex flex-col items-center justify-center text-center animate-in fade-in zoom-in duration-300">
              <div class="relative mb-4">
                <img :src="activeChat?.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(activeChat?.name) + '&background=0284c7&color=fff'" class="w-20 h-20 rounded-full border-4 border-white dark:border-slate-800 shadow-xl object-cover">
                <div class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
              </div>

              <h3 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight">Vous êtes connecté avec {{ activeChat?.name }} ! 👋</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5 max-w-xs leading-relaxed">
                Démarrez la discussion. Vos messages sont chiffrés et s'effacent automatiquement après 7 jours.
              </p>

              <!-- Quick starter suggestion pills -->
              <div class="flex flex-wrap justify-center gap-2 mt-6 max-w-md">
                <button @click="sendQuickStarter('Salut ! Tu es sur le campus aujourd\'hui ? 👋')" class="px-3.5 py-2 bg-white dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-full text-xs text-gray-700 dark:text-slate-300 font-medium shadow-sm transition-all active:scale-95">
                  "Salut ! Tu es sur le campus ? 👋"
                </button>
                <button @click="sendQuickStarter('Hello ! Tu révises à la BU ? 📚')" class="px-3.5 py-2 bg-white dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-full text-xs text-gray-700 dark:text-slate-300 font-medium shadow-sm transition-all active:scale-95">
                  "Hello ! Tu révises à la BU ? 📚"
                </button>
                <button @click="sendQuickStarter('Bonjour ! Quel est ton statut d\'étude ? 🎓')" class="px-3.5 py-2 bg-white dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-full text-xs text-gray-700 dark:text-slate-300 font-medium shadow-sm transition-all active:scale-95">
                  "Bonjour ! Quel est ton statut ? 🎓"
                </button>
              </div>
            </div>

            <!-- Messages Loop -->
            <div v-for="(msg, index) in chatMessages" :key="msg.id || index" :class="[isMyMessage(msg) ? 'flex justify-end' : 'flex justify-start']" class="w-full">
              <div class="max-w-[85%] sm:max-w-[75%] flex flex-col" :class="[isMyMessage(msg) ? 'items-end' : 'items-start']">

                <!-- Bubble Wrapper -->
                <div :class="[
                  isMyMessage(msg)
                    ? 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-blue-500/10'
                    : 'bg-white dark:bg-slate-800/80 text-gray-900 dark:text-slate-200 rounded-2xl rounded-tl-none border border-gray-200 dark:border-white/5 shadow-sm',
                  msg._optimistic ? 'opacity-70' : 'opacity-100',
                ]" class="px-4 py-3 shadow-lg transition-opacity duration-200">
                  <div class="text-sm leading-relaxed whitespace-pre-wrap select-text" v-html="parsePlaceLinks(msg.content)"></div>
                </div>

                <!-- Timestamp + status -->
                <div v-if="!activeChat?.isAI && msg.created_at" class="flex items-center gap-1 mt-1 px-1">
                  <span class="text-[10px] text-gray-400 dark:text-slate-500">
                    {{ formatMessageTime(msg.created_at) }}
                  </span>
                  <!-- Sending indicator for optimistic messages -->
                  <Icon v-if="msg._optimistic" icon="ph:clock" class="w-3 h-3 text-gray-400 dark:text-slate-600" />
                  <!-- Read indicator for sent messages -->
                  <Icon v-else-if="isMyMessage(msg)" :icon="msg.is_read ? 'ph:checks-bold' : 'ph:check-bold'" :class="msg.is_read ? 'text-blue-400' : 'text-gray-400 dark:text-slate-500'" class="w-3.5 h-3.5" />
                </div>
              </div>
            </div>

            <!-- AI Typing Indicator -->
            <div v-if="isTyping" class="flex justify-start">
              <div class="bg-white dark:bg-slate-800/80 border border-gray-200 dark:border-white/5 px-4 py-3 rounded-2xl rounded-tl-none flex items-center gap-1.5 shadow-sm">
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
              </div>
            </div>

          </div>

          <!-- Premium Input Bar -->
          <footer class="p-3 sm:p-4 border-t border-gray-200 dark:border-white/10 bg-gray-50/40 dark:bg-slate-900/40 backdrop-blur-2xl z-20" style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 0.75rem)">
            <div class="max-w-4xl mx-auto flex items-end gap-2 sm:gap-3">
              <div class="flex-1 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-2xl flex items-end px-3 sm:px-4 focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:border-blue-500 transition-all shadow-sm">
                <!-- Textarea: Enter sends, Shift+Enter adds a line break -->
                <textarea
                  v-model="messageInput"
                  @input="handleTyping"
                  @keydown.enter.exact.prevent="handleSendMessage"
                  :placeholder="activeChat?.isAI ? 'Demander quelque chose à l\'IA...' : 'Écrire un message... (Shift+Entrée pour saut de ligne)'"
                  rows="1"
                  class="w-full bg-transparent border-none py-2.5 sm:py-3.5 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 outline-none text-xs sm:text-sm resize-none overflow-hidden leading-relaxed"
                  style="max-height: 120px; overflow-y: auto;"
                  @input.native="$event.target.style.height = 'auto'; $event.target.style.height = Math.min($event.target.scrollHeight, 120) + 'px'"
                ></textarea>

                <!-- Quick Send Icon triggers & Emoji Picker -->
                <div class="relative flex items-center mb-2 sm:mb-3 gap-1">
                  <button type="button" @click="showEmojiPicker = !showEmojiPicker" class="p-1 hover:text-blue-500 text-gray-400 dark:text-slate-400 transition-colors flex-shrink-0" title="Ajouter un émoji">
                    <Icon icon="ph:smiley-bold" class="w-5 h-5" />
                  </button>
                  <div v-if="showEmojiPicker" class="absolute bottom-10 right-0 z-50 bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 p-2.5 rounded-2xl shadow-xl flex gap-2 animate-in fade-in zoom-in duration-150">
                    <button type="button" v-for="emoji in ['👍', '❤️', '😂', '🔥', '👏', '🙏', '🎓', '📚', '👋']" :key="emoji" @click="addEmoji(emoji)" class="text-lg hover:scale-125 transition-transform p-1">
                      {{ emoji }}
                    </button>
                  </div>
                </div>

                <button v-if="activeChat?.isAI" @click="messageInput = 'Où se trouve la BU ?'" class="p-1 mb-2.5 sm:mb-3.5 hover:text-indigo-400 text-slate-500 transition-colors flex-shrink-0" title="Idée rapide">
                  <Icon icon="ph:lightbulb" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
                </button>
              </div>
              
              <button @click="handleSendMessage" class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-tr from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20 hover:scale-105 active:scale-95 transition-all flex-shrink-0 mb-0.5">
                <Icon icon="ph:paper-plane-right-fill" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
            </div>
          </footer>

        </div>

      </div>

    </div>

    <!-- NEW DISCUSSION MODAL -->
    <div v-if="showNewChatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-6 shadow-2xl flex flex-col h-[70vh] relative overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="flex justify-between items-center mb-5">
           <div>
             <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Nouvelle discussion</h3>
             <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Recherchez un étudiant du campus</p>
           </div>
           <button @click="showNewChatModal = false" class="w-9 h-9 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-400 flex items-center justify-center transition-all">
             <Icon icon="ph:x-bold" class="w-5 h-5" />
           </button>
        </div>

        <div class="relative mb-4">
          <Icon icon="ph:magnifying-glass" class="absolute left-4 top-3.5 text-gray-400 dark:text-slate-500 w-5 h-5" />
          <input v-model="searchQuery" type="text" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-2xl py-3 pl-12 pr-4 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all text-sm" placeholder="Saisir le nom de l'étudiant...">
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
           <div v-for="user in filteredStudents" :key="user.id" @click="startNewConversation(user)" class="flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-white/5 rounded-2xl cursor-pointer transition-all border border-transparent hover:border-gray-200 dark:hover:border-white/5">
              <img :src="'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=0284c7&color=fff'" class="w-10 h-10 rounded-full object-cover">
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white truncate text-sm">{{ user.name }}</p>
                <p class="text-[11px] text-gray-500 dark:text-slate-400 truncate">Étudiant de l'UAC</p>
              </div>
              <Icon icon="ph:arrow-right-bold" class="w-4 h-4 text-gray-400 dark:text-slate-500" />
           </div>
           <div v-if="filteredStudents.length === 0" class="text-center py-8 text-gray-500 dark:text-slate-500 text-sm">
             Aucun étudiant trouvé pour "{{ searchQuery }}"
           </div>
        </div>
      </div>
    </div>

    <!-- REPORT USER MODAL -->
    <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="bg-white dark:bg-slate-900 border border-red-200 dark:border-red-500/20 rounded-3xl w-full max-w-md p-6 shadow-2xl relative overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="flex justify-between items-center mb-5">
           <div class="flex items-center gap-2">
             <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-500/10 text-red-500 dark:text-red-400 flex items-center justify-center">
               <Icon icon="ph:flag-bold" />
             </div>
             <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Signaler cet utilisateur</h3>
           </div>
           <button @click="showReportModal = false" class="w-9 h-9 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-400 flex items-center justify-center transition-all">
             <Icon icon="ph:x-bold" class="w-5 h-5" />
           </button>
        </div>

        <p class="text-xs text-gray-500 dark:text-slate-400 mb-4 leading-relaxed">
          U-Map s'engage à assurer la sécurité du campus. Décrivez le motif du signalement. L'équipe d'administration prendra des mesures de restriction immédiates si nécessaire.
        </p>

        <form @submit.prevent="submitReport" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Motif du signalement</label>
            <select v-model="reportReason" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-2xl py-3 px-4 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500/50 outline-none text-sm mb-3">
              <option value="Harcèlement ou intimidation">Harcèlement ou intimidation</option>
              <option value="Contenu inapproprié ou offensant">Contenu inapproprié ou offensant</option>
              <option value="Faux profil étudiant">Faux profil étudiant</option>
              <option value="Spam / comportement malveillant">Spam / comportement malveillant</option>
              <option value="Autre motif">Autre motif (Préciser ci-dessous)</option>
            </select>

            <textarea v-model="customReason" rows="3" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-2xl py-3 px-4 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-red-500/50 outline-none text-sm" placeholder="Fournissez plus de détails sur le comportement signalé..."></textarea>
          </div>

          <div class="flex gap-3 mt-6">
            <button type="button" @click="showReportModal = false" class="flex-1 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-slate-300 font-semibold py-3 rounded-2xl text-sm transition-all">
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
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'
import { authService } from '../services/authService'
import { aiService } from '../services/aiService'
import { messageService } from '../services/messageService'
import { studyService } from '../services/studyService'
import { useMeta } from '../composables/useMeta'
import echo from '../services/echo'
import errorHandler from '../services/errorHandler'

useMeta('Messagerie', "Échangez avec la communauté étudiante de l'UAC. Messagerie instantanée et suggestions intelligentes sur U-map.", { canonicalPath: '/chat' })

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
const conversations = ref([])
const showEmojiPicker = ref(false)

const addEmoji = (emoji) => {
  messageInput.value += emoji
  showEmojiPicker.value = false
}

// Report Modal variables
const showReportModal = ref(false)
const reportReason = ref('Harcèlement ou intimidation')
const customReason = ref('')
const reporting = ref(false)

const authForm = ref({ name: '', email: '', password: '' })

// Study Buddies state
const studyStatusExpanded = ref(false)
const myStudyStatus = ref('')
const myStudyLocation = ref('')
const studyBuddies = ref([])
let currentEchoChannel = null

const loadStudyBuddies = async () => {
    try {
        studyBuddies.value = await studyService.getStudyBuddies()
    } catch (e) { console.error(e) }
}

let typingTimeout = null

// Subscribe to a private Echo channel for the current conversation
const subscribeToChatChannel = () => {
    if (!activeChat.value || activeChat.value?.isAI || !echo) return
    unsubscribeFromChatChannel()

    const me = authService.getCurrentUser()
    if (!me) return

    const minId = Math.min(me.id, activeChat.value.id)
    const maxId = Math.max(me.id, activeChat.value.id)
    const channelName = `chat.${minId}.${maxId}`
    currentEchoChannel = channelName

    echo.private(channelName)
        .listen('.message.sent', (data) => {
            // Only append if the message is from the other user (avoid duplicate with optimistic update)
            if (data.sender_id !== me.id) {
                chatMessages.value.push({
                    ...data,
                    // Normalize: WebSocket payload uses 'content' directly (decrypted by broadcastWith)
                    content: data.content,
                })
                scrollToBottom()

                // Update sidebar with the new incoming message
                _updateConversationSidebar(data)
            }
        })
        .listenForWhisper('typing', (e) => {
            isTyping.value = !!e.isTyping
            if (typingTimeout) clearTimeout(typingTimeout)
            if (e.isTyping) {
                typingTimeout = setTimeout(() => {
                    isTyping.value = false
                }, 3000)
            }
        })
}

const handleTyping = () => {
    if (!activeChat.value || activeChat.value?.isAI || !currentEchoChannel || !echo) return
    echo.private(currentEchoChannel).whisper('typing', { isTyping: true })
}

const unsubscribeFromChatChannel = () => {
    if (currentEchoChannel && echo) {
        echo.leaveChannel(`private-${currentEchoChannel}`)
        currentEchoChannel = null
    }
    if (typingTimeout) {
        clearTimeout(typingTimeout)
        typingTimeout = null
    }
}

const saveMyStudyStatus = async () => {
    try {
        await studyService.updateStudyStatus(myStudyStatus.value, myStudyLocation.value)
        errorHandler.success('Votre statut d\'étude a été enregistré avec succès !')
        await loadStudyBuddies()
    } catch (e) { errorHandler.error(e.message) }
}

onMounted(async () => {
    if (isLoggedIn.value) {
        await loadStudents()
        await loadConversations()
        await loadStudyBuddies()

        // Initialiser mon propre statut d'étude
        const me = authService.getCurrentUser()
        if (me) {
            myStudyStatus.value = me.study_status || ''
            myStudyLocation.value = me.study_location || ''
        }

        // Sync active chat with URL query ?chat=
        if (route.query.chat) {
            await syncChatFromQuery()
        }
    }
})

onUnmounted(() => {
    unsubscribeFromChatChannel()
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
        let student = (Array.isArray(conversations.value) ? conversations.value.find(c => c.id == chatId) : null) ||
                      (Array.isArray(students.value) ? students.value.find(s => s.id == chatId) : null)
        if (!student) {
            // Fallback load
            await loadStudents()
            await loadConversations()
            student = (Array.isArray(conversations.value) ? conversations.value.find(c => c.id == chatId) : null) ||
                      (Array.isArray(students.value) ? students.value.find(s => s.id == chatId) : null)
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
        const res = await messageService.getConversations()
        // Handle both paginated response and direct array
        conversations.value = res.data || res || []
    } catch (e) {
        console.error('Error loading conversations:', e)
        conversations.value = []
    }
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
        errorHandler.error(err.message)
    } finally {
        loading.value = false
    }
}

const logout = () => {
    authService.logout()
    isLoggedIn.value = false
    activeChat.value = null
    router.replace({ query: {} })
    router.push('/login')
}

const selectAIChat = () => {
    activeChat.value = { id: 'ai', name: 'U-Map Copilot AI', isAI: true, avatar: null }
    chatMessages.value = [
        { role: 'assistant', content: "Bonjour ! Je suis l'intelligence artificielle officielle de l'UAC. Comment puis-je t'aider sur le campus aujourd'hui ?" }
    ]
    if (route.query.chat !== 'ai') {
        router.push({ query: { chat: 'ai' } })
    }
    scrollToBottom()
}

const selectChat = async (student) => {
    activeChat.value = { ...student, isAI: false }
    if (route.query.chat != student.id) {
        router.push({ query: { chat: student.id } })
    }
    await loadMessages()
    subscribeToChatChannel()
}

const closeChat = () => {
    unsubscribeFromChatChannel()
    activeChat.value = null
    router.push({ query: {} })
}

const loadMessages = async () => {
    if (!activeChat.value || activeChat.value?.isAI) return
    try {
        const response = await messageService.getMessages(activeChat.value.id)
        // Backend always returns { data: [], meta: {} }
        chatMessages.value = Array.isArray(response.data) ? response.data : (Array.isArray(response) ? response : [])
        scrollToBottom()
    } catch (e) {
        console.error('Error loading messages:', e)
        chatMessages.value = []
    }
}

const parsePlaceLinks = (content) => {
    // Convert [LIEU:Nom|ID] format to clickable colored text (no background)
    if (!content || typeof content !== 'string') return content
    
    // Basic HTML sanitization - only allow specific tags and attributes
    const sanitizedContent = content
        .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
        .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
        .replace(/on\w+="[^"]*"/gi, '')
        .replace(/on\w+='[^']*'/gi, '')
    
    // Then parse place links
    return sanitizedContent.replace(/\[LIEU:([^\|]+)\|([^\]]+)\]/g, (match, name, id) => {
        return `<a href="/map?place=${id}&route=true" class="text-blue-600 dark:text-blue-400 font-bold hover:underline cursor-pointer no-underline decoration-none transition-colors" onclick="event.preventDefault(); window.location.href='/map?place=${id}&route=true'">
          ${name}
        </a>`
    })
}

const handleSendMessage = async () => {
    // BUG FIX: isTyping tracks the OTHER person's typing indicator — it must NOT block our send
    if (!messageInput.value.trim()) return
    const content = messageInput.value.trim()
    messageInput.value = ''

    if (activeChat.value?.isAI) {
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
        const me = authService.getCurrentUser()
        // Optimistic update: add message immediately in UI for instant feedback
        const optimisticMsg = {
            id: `temp-${Date.now()}`,
            content,
            sender_id: me?.id,
            receiver_id: activeChat.value.id,
            created_at: new Date().toISOString(),
            is_read: false,
            _optimistic: true,
        }
        chatMessages.value.push(optimisticMsg)
        scrollToBottom()

        try {
            const newMsg = await messageService.sendMessage(activeChat.value.id, content)

            // Replace optimistic message with real one from server
            const idx = chatMessages.value.findIndex(m => m._optimistic && m.id === optimisticMsg.id)
            if (idx !== -1) {
                chatMessages.value.splice(idx, 1, newMsg)
            }

            // Update conversation sidebar locally (no extra API call needed)
            _updateConversationSidebar(newMsg)
        } catch (e) {
            // On error: remove optimistic message and show error
            chatMessages.value = chatMessages.value.filter(m => m.id !== optimisticMsg.id)
            errorHandler.error(e.message || 'Erreur lors de l\'envoi du message.')
        }
    }
}

const startNewConversation = (user) => {
    if (!Array.isArray(conversations.value) || !conversations.value.find(c => c.id === user.id)) {
        if (Array.isArray(conversations.value)) {
            conversations.value.unshift(user)
        }
    }
    showNewChatModal.value = false
    selectChat(user)
}

const isMyMessage = (msg) => {
    if (activeChat.value?.isAI) return msg.role === 'user'
    const me = authService.getCurrentUser()
    return msg.sender_id === me.id
}

const sendQuickStarter = (text) => {
    messageInput.value = text
    handleSendMessage()
}

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return ''

    const now = new Date()
    const isToday = d.toDateString() === now.toDateString()
    
    const yesterday = new Date(now)
    yesterday.setDate(now.getDate() - 1)
    const isYesterday = d.toDateString() === yesterday.toDateString()

    if (isToday) {
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    } else if (isYesterday) {
        return 'Hier'
    } else {
        return d.toLocaleDateString([], { day: '2-digit', month: '2-digit' })
    }
}

/**
 * Format a date as HH:MM for message bubbles.
 */
const formatMessageTime = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return ''
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getExpirationText = (createdAt) => {
    const created = new Date(createdAt)
    const now = new Date()
    const diff = 7 - Math.floor((now - created) / (1000 * 60 * 60 * 24))
    if (diff <= 0) return "S'autodétruit sous peu"
    return `Expire dans ${diff}j`
}

const scrollToBottom = () => {
    // Double nextTick ensures DOM is fully painted before measuring scrollHeight
    nextTick(() => {
        nextTick(() => {
            const container = document.getElementById('chat-messages')
            if (container) {
                container.scrollTop = container.scrollHeight
            }
        })
    })
}

/**
 * Update the conversation sidebar locally without an API call.
 * @param {Object} msg - Message object with sender_id, receiver_id, content, created_at
 */
const _updateConversationSidebar = (msg) => {
    const me = authService.getCurrentUser()
    if (!me) return

    // Determine the other party's id
    const otherId = msg.sender_id === me.id ? msg.receiver_id : msg.sender_id
    const lastMsgPayload = {
        id: msg.id,
        content: msg.content,
        created_at: msg.created_at,
        sender_id: msg.sender_id,
        is_read: msg.sender_id === me.id, // our own messages are read; incoming may not be
    }

    const existingIdx = Array.isArray(conversations.value)
        ? conversations.value.findIndex(c => c.id === otherId)
        : -1

    if (existingIdx !== -1) {
        const updated = {
            ...conversations.value[existingIdx],
            last_message: lastMsgPayload,
            last_message_at: msg.created_at,
        }
        // Move to top
        const rest = conversations.value.filter((_, i) => i !== existingIdx)
        conversations.value = [updated, ...rest]
    } else {
        // New conversation — add to top
        conversations.value = [{
            ...activeChat.value,
            id: otherId,
            last_message: lastMsgPayload,
            last_message_at: msg.created_at,
            unread_count: msg.sender_id !== me.id ? 1 : 0,
        }, ...(conversations.value || [])]
    }
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
            errorHandler.success('L\'utilisateur a bien été signalé aux administrateurs. Merci de veiller à la sécurité de l\'UAC.')
            showReportModal.value = false
            customReason.value = ''
        } else {
            throw new Error('Erreur réseau lors de l\'envoi.')
        }
    } catch (e) {
        errorHandler.error(e.message)
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
