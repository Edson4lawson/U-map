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
              <p class="text-[10px] sm:text-xs text-gray-500 dark:text-slate-400 mt-0.5">Discussions instantanées & partage de positions</p>
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
                      <Icon icon="ph:books-bold" class="w-4 h-4 text-blue-500 dark:text-blue-400" /> Mon Statut d'Étude & Campus
                   </div>
                   <Icon :icon="studyStatusExpanded ? 'ph:chevron-up-bold' : 'ph:caret-down-bold'" class="w-5 h-5 text-blue-500 dark:text-blue-400 ml-1" />
                </h3>
                <div v-show="studyStatusExpanded" class="space-y-3">
                   <div class="flex flex-col sm:flex-row gap-2">
                      <input v-model="myStudyStatus" type="text" placeholder="Ex: Révise les maths..."
                             class="flex-1 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl py-2 px-3 text-xs text-gray-900 dark:text-white outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-400 dark:placeholder-slate-500">
                      <input v-model="myStudyLocation" type="text" placeholder="Ex: BU Centrale..."
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
                            <span class="text-[9px] bg-blue-500/20 text-blue-600 dark:text-blue-400 font-extrabold px-2 py-1 rounded-lg">Écrire</span>
                         </div>
                      </div>
                   </div>
                   <div v-else class="text-[10px] text-gray-500 dark:text-slate-500 italic pt-1">Aucun autre étudiant actif pour le moment.</div>
                </div>
            </div>

            <!-- Custom Separator -->
            <div class="flex items-center gap-3">
              <span class="text-[10px] font-bold text-gray-500 dark:text-slate-500 uppercase tracking-widest">Discussions récentes</span>
              <div class="flex-1 h-[1px] bg-gray-200 dark:border-white/10"></div>
            </div>

            <!-- List of Chats -->
            <div class="space-y-3">
              <!-- Loading skeleton while fetching conversations -->
              <div v-if="conversationsLoading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="p-3.5 sm:p-4 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/5 rounded-2xl flex gap-3.5 items-center animate-pulse">
                  <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-slate-700 flex-shrink-0"></div>
                  <div class="flex-1 space-y-2">
                    <div class="h-3.5 bg-gray-200 dark:bg-slate-700 rounded-full w-1/3"></div>
                    <div class="h-2.5 bg-gray-200 dark:bg-slate-700 rounded-full w-2/3"></div>
                  </div>
                </div>
              </div>

              <!-- Empty State when no conversations exist -->
              <div v-else-if="conversations.length === 0" class="p-8 sm:p-10 text-center bg-gradient-to-b from-gray-50 to-blue-50/30 dark:from-slate-900/50 dark:to-blue-950/20 border border-gray-200 dark:border-white/10 rounded-3xl shadow-lg relative overflow-hidden my-2">
                <div class="w-14 h-14 bg-gradient-to-tr from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-xl shadow-blue-500/20 animate-bounce">
                  <Icon icon="ph:paper-plane-tilt-bold" class="w-7 h-7 text-white" />
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Aucune discussion en cours</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5 max-w-sm mx-auto leading-relaxed">
                  Discutez, partagez vos positions GPS et retrouvez facilement vos amis sur le campus de l'UAC !
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

                  <!-- Snippet -->
                  <div class="flex items-center gap-1.5">
                    <p v-if="chat.last_message" class="text-xs text-gray-600 dark:text-slate-400 truncate">
                      <span v-if="isMyMessage(chat.last_message)" class="font-semibold text-gray-500 dark:text-slate-400">Vous : </span>
                      {{ formatSnippet(chat.last_message.content) }}
                    </p>
                    <p v-else class="text-[11px] text-slate-400 dark:text-slate-500 italic flex items-center gap-1 truncate">
                      <span>Démarrer la discussion 👋</span>
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

        <!-- CASE B: FULL SCREEN ACTIVE CHAT VIEW (MESSENGER STYLE) -->
        <div v-else class="flex-1 flex flex-col h-full bg-white dark:bg-slate-950 overflow-hidden relative">

          <!-- Premium Chat Header -->
          <header class="py-3 px-3 sm:px-6 border-b border-gray-200 dark:border-white/10 bg-gray-50/60 dark:bg-slate-900/60 backdrop-blur-2xl flex items-center justify-between z-20">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
              <button @click="closeChat" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-300 hover:text-gray-700 dark:hover:text-white flex items-center justify-center transition-all" title="Retour aux discussions">
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
                  <p class="text-[9px] sm:text-[10px] font-semibold flex items-center gap-1 mt-0.5" :class="activeChat?.isAI ? 'text-indigo-500' : 'text-emerald-400'">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ activeChat?.isAI ? 'Assistant intelligent UAC' : (activeChat?.study_location ? `À la ${activeChat.study_location}` : 'En ligne') }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions Header -->
            <div class="flex items-center gap-1.5">
              <button v-if="!activeChat?.isAI" @click="openLocationModal" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-blue-500/10 hover:bg-blue-500/20 text-blue-500 dark:text-blue-400 flex items-center justify-center transition-all border border-blue-500/20" title="Partager un lieu ou ma position">
                <Icon icon="ph:map-pin-bold" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
              <button v-if="!activeChat?.isAI" @click="showReportModal = true" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-red-500/10 hover:bg-red-500/20 text-red-400 flex items-center justify-center transition-all border border-red-500/20" title="Signaler cet utilisateur">
                <Icon icon="ph:flag-bold" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
            </div>
          </header>

          <!-- Chat Messages Body -->
          <div class="flex-1 overflow-y-auto px-3 sm:px-6 py-4 sm:py-6 space-y-4 bg-gray-50 dark:bg-slate-950" id="chat-messages">

            <!-- Loading Skeleton -->
            <div v-if="messagesLoading" class="flex flex-col items-center justify-center py-12 space-y-3">
              <Icon icon="ph:spinner-gap-bold" class="w-8 h-8 text-blue-500 animate-spin" />
              <p class="text-xs text-gray-400 dark:text-slate-500">Chargement des messages chiffrés...</p>
            </div>

            <!-- Empty Conversation Starter -->
            <div v-else-if="!activeChat?.isAI && chatMessages.length === 0" class="py-8 px-4 flex flex-col items-center justify-center text-center animate-in fade-in zoom-in duration-300">
              <div class="relative mb-4">
                <img :src="activeChat?.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(activeChat?.name) + '&background=0284c7&color=fff'" class="w-20 h-20 rounded-full border-4 border-white dark:border-slate-800 shadow-xl object-cover">
                <div class="absolute bottom-1 right-1 w-4 h-4 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
              </div>

              <h3 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight">Vous êtes connecté avec {{ activeChat?.name }} ! 👋</h3>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-1.5 max-w-xs leading-relaxed">
                Envoyez un message, partagez votre position GPS en temps réel ou donnez-vous rendez-vous sur le campus !
              </p>

              <!-- Quick starter suggestion pills -->
              <div class="flex flex-wrap justify-center gap-2 mt-6 max-w-md">
                <button @click="sendQuickStarter('Salut ! Tu es sur le campus aujourd\'hui ? 👋')" class="px-3.5 py-2 bg-white dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-full text-xs text-gray-700 dark:text-slate-300 font-medium shadow-sm transition-all active:scale-95">
                  "Salut ! Tu es sur le campus ? 👋"
                </button>
                <button @click="sendQuickStarter('Hello ! Tu révises à la BU ? 📚')" class="px-3.5 py-2 bg-white dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 border border-gray-200 dark:border-white/10 rounded-full text-xs text-gray-700 dark:text-slate-300 font-medium shadow-sm transition-all active:scale-95">
                  "Hello ! Tu révises à la BU ? 📚"
                </button>
                <button @click="openLocationModal" class="px-3.5 py-2 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-500/30 rounded-full text-xs text-blue-600 dark:text-blue-400 font-bold shadow-sm transition-all active:scale-95 flex items-center gap-1.5">
                  <Icon icon="ph:map-pin-fill" class="w-3.5 h-3.5" />
                  "Partager ma position 📍"
                </button>
              </div>
            </div>

            <!-- Messages Loop -->
            <div v-for="(msg, index) in chatMessages" :key="msg.id || index" :class="[isMyMessage(msg) ? 'flex justify-end' : 'flex justify-start']" class="w-full">
              <div class="max-w-[88%] sm:max-w-[75%] flex flex-col" :class="[isMyMessage(msg) ? 'items-end' : 'items-start']">

                <!-- Bubble Wrapper -->
                <div :class="[
                  isMyMessage(msg)
                    ? 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-2xl rounded-tr-none shadow-md shadow-blue-500/15'
                    : 'bg-white dark:bg-slate-800/90 text-gray-900 dark:text-slate-200 rounded-2xl rounded-tl-none border border-gray-200 dark:border-white/10 shadow-sm',
                  msg._optimistic ? 'opacity-70' : 'opacity-100',
                ]" class="px-4 py-3 transition-opacity duration-200">

                  <!-- Render Location Card if message has location tag, else regular text -->
                  <div class="text-sm leading-relaxed whitespace-pre-wrap select-text" v-html="renderMessageBody(msg.content, isMyMessage(msg))"></div>
                </div>

                <!-- Timestamp + status -->
                <div v-if="!activeChat?.isAI && msg.created_at" class="flex items-center gap-1 mt-1 px-1">
                  <span class="text-[10px] text-gray-400 dark:text-slate-500">
                    {{ formatMessageTime(msg.created_at) }}
                  </span>
                  <!-- Sending indicator for optimistic messages -->
                  <Icon v-if="msg._optimistic" icon="ph:clock" class="w-3 h-3 text-gray-400 dark:text-slate-600 animate-spin" />
                  <!-- Read indicator for sent messages -->
                  <Icon v-else-if="isMyMessage(msg)" :icon="msg.is_read ? 'ph:checks-bold' : 'ph:check-bold'" :class="msg.is_read ? 'text-blue-400' : 'text-gray-400 dark:text-slate-500'" class="w-3.5 h-3.5" />
                </div>
              </div>
            </div>

            <!-- AI / User Typing Indicator -->
            <div v-if="isTyping" class="flex justify-start">
              <div class="bg-white dark:bg-slate-800/80 border border-gray-200 dark:border-white/5 px-4 py-3 rounded-2xl rounded-tl-none flex items-center gap-1.5 shadow-sm">
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce"></span>
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
              </div>
            </div>

          </div>

          <!-- Premium Input Bar -->
          <footer class="p-3 sm:p-4 border-t border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-slate-900/70 backdrop-blur-2xl z-20" style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 0.75rem)">
            <div class="max-w-4xl mx-auto flex items-end gap-2 sm:gap-3">
              <div class="flex-1 bg-white dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-2xl flex items-end px-3 sm:px-4 focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:border-blue-500 transition-all shadow-sm">
                
                <!-- Quick Location Share Button inside input bar -->
                <button v-if="!activeChat?.isAI" type="button" @click="openLocationModal" class="p-1 mb-2.5 sm:mb-3 hover:text-blue-500 text-gray-400 dark:text-slate-400 transition-colors flex-shrink-0 mr-1" title="Partager un lieu ou ma position GPS">
                  <Icon icon="ph:map-pin-bold" class="w-5 h-5" />
                </button>

                <!-- Textarea: Enter sends, Shift+Enter adds a line break -->
                <textarea
                  v-model="messageInput"
                  @input="handleTyping"
                  @keydown.enter.exact.prevent="handleSendMessage"
                  :placeholder="activeChat?.isAI ? 'Demander un lieu, amphi, resto à l\'IA...' : 'Écrire un message... (Shift+Entrée pour saut de ligne)'"
                  rows="1"
                  class="flex-1 bg-transparent border-none py-2.5 sm:py-3.5 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 outline-none text-xs sm:text-sm resize-none overflow-hidden leading-relaxed"
                  style="max-height: 120px; overflow-y: auto;"
                  @input.native="$event.target.style.height = 'auto'; $event.target.style.height = Math.min($event.target.scrollHeight, 120) + 'px'"
                ></textarea>

                <!-- Emoji Picker -->
                <div class="relative flex items-center mb-2 sm:mb-3 gap-1">
                  <button type="button" @click="showEmojiPicker = !showEmojiPicker" class="p-1 hover:text-blue-500 text-gray-400 dark:text-slate-400 transition-colors flex-shrink-0" title="Ajouter un émoji">
                    <Icon icon="ph:smiley-bold" class="w-5 h-5" />
                  </button>
                  <div v-if="showEmojiPicker" class="absolute bottom-10 right-0 z-50 bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 p-2.5 rounded-2xl shadow-xl flex gap-2 animate-in fade-in zoom-in duration-150">
                    <button type="button" v-for="emoji in ['👍', '❤️', '😂', '🔥', '👏', '🙏', '📍', '🎓', '📚', '👋']" :key="emoji" @click="addEmoji(emoji)" class="text-lg hover:scale-125 transition-transform p-1">
                      {{ emoji }}
                    </button>
                  </div>
                </div>

                <button v-if="activeChat?.isAI" @click="messageInput = 'Où se trouve la BU ?'" class="p-1 mb-2.5 sm:mb-3.5 hover:text-indigo-400 text-slate-500 transition-colors flex-shrink-0" title="Idée rapide">
                  <Icon icon="ph:lightbulb" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
                </button>
              </div>
              
              <button @click="handleSendMessage" :disabled="!messageInput.trim()" class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-tr from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-500/20 hover:scale-105 active:scale-95 transition-all flex-shrink-0 mb-0.5">
                <Icon icon="ph:paper-plane-right-fill" class="w-4.5 h-4.5 sm:w-5 sm:h-5" />
              </button>
            </div>
          </footer>

        </div>

      </div>

    </div>

    <!-- LOCATION & PLACE SHARING MODAL (MESSENGER STYLE) -->
    <div v-if="showLocationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md">
      <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-lg p-6 shadow-2xl flex flex-col max-h-[85vh] relative overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <div class="flex justify-between items-center mb-4">
           <div class="flex items-center gap-2.5">
             <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
               <Icon icon="ph:map-pin-fill" class="w-5 h-5" />
             </div>
             <div>
               <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Partager un lieu ou ma position</h3>
               <p class="text-xs text-gray-500 dark:text-slate-400">Envoyez vos coordonnées à {{ activeChat?.name }}</p>
             </div>
           </div>
           <button @click="showLocationModal = false" class="w-9 h-9 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-500 dark:text-slate-400 flex items-center justify-center transition-all">
             <Icon icon="ph:x-bold" class="w-5 h-5" />
           </button>
        </div>

        <!-- Tab Selector: Live GPS vs Campus Places -->
        <div class="flex gap-2 mb-4 p-1 bg-gray-100 dark:bg-white/5 rounded-2xl">
          <button @click="locationTab = 'gps'" :class="locationTab === 'gps' ? 'bg-white dark:bg-slate-800 shadow-sm text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-slate-400 font-medium'" class="flex-1 py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
            <Icon icon="ph:crosshair-bold" class="w-4 h-4" />
            Ma position GPS en direct
          </button>
          <button @click="locationTab = 'places'" :class="locationTab === 'places' ? 'bg-white dark:bg-slate-800 shadow-sm text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-slate-400 font-medium'" class="flex-1 py-2.5 text-xs rounded-xl transition-all flex items-center justify-center gap-1.5">
            <Icon icon="ph:buildings-bold" class="w-4 h-4" />
            Lieux du campus ({{ allCampusPlaces.length }})
          </button>
        </div>

        <!-- TAB 1: LIVE GPS SHARE -->
        <div v-if="locationTab === 'gps'" class="space-y-4 py-2">
          <div class="p-5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/20 border border-blue-200 dark:border-blue-500/20 rounded-2xl text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center mx-auto shadow-lg shadow-blue-500/25">
              <Icon icon="ph:navigation-arrow-fill" class="w-6 h-6 animate-pulse" />
            </div>
            <div>
              <h4 class="text-sm font-bold text-gray-900 dark:text-white">Partage instantané de position</h4>
              <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 max-w-xs mx-auto">
                Votre ami recevra une carte interactive avec un bouton d'itinéraire piéton direct vers votre position actuelle.
              </p>
            </div>

            <button @click="shareCurrentGPSLocation" :disabled="gpsLoading" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-3 rounded-xl transition-all shadow-md shadow-blue-500/20 flex items-center justify-center gap-2 text-xs disabled:opacity-60">
              <Icon :icon="gpsLoading ? 'ph:spinner-gap-bold' : 'ph:paper-plane-tilt-bold'" :class="gpsLoading ? 'animate-spin' : ''" class="w-4 h-4" />
              <span>{{ gpsLoading ? 'Acquisition du signal GPS...' : '📍 Envoyer ma position actuelle' }}</span>
            </button>
          </div>
        </div>

        <!-- TAB 2: SEARCH CAMPUS PLACES -->
        <div v-else class="flex-1 flex flex-col min-h-0 space-y-3">
          <div class="relative">
            <Icon icon="ph:magnifying-glass" class="absolute left-3.5 top-3 text-gray-400 dark:text-slate-500 w-4.5 h-4.5" />
            <input v-model="placeSearchQuery" type="text" placeholder="Rechercher un amphi, la BU, un resto..." class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-xs text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 outline-none focus:ring-2 focus:ring-blue-500/40">
          </div>

          <div class="flex-1 overflow-y-auto space-y-2 pr-1 custom-scrollbar max-h-64">
            <div v-for="place in filteredCampusPlaces" :key="place.id" @click="shareCampusPlace(place)" class="p-3 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl cursor-pointer transition-all border border-gray-200/60 dark:border-white/5 flex items-center justify-between group">
              <div class="min-w-0 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center flex-shrink-0">
                  <Icon :icon="getPlaceIcon(place.category || place.type)" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                  <h5 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-blue-500 transition-colors">{{ place.name }}</h5>
                  <p class="text-[10px] text-gray-500 dark:text-slate-400 truncate">{{ place.category || place.type || 'Lieu du campus' }}</p>
                </div>
              </div>
              <span class="text-[10px] bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold px-2 py-1 rounded-lg group-hover:bg-blue-500 group-hover:text-white transition-all flex items-center gap-1 flex-shrink-0">
                <span>Envoyer</span>
                <Icon icon="ph:paper-plane-right-fill" class="w-3 h-3" />
              </span>
            </div>
            <div v-if="filteredCampusPlaces.length === 0" class="text-center py-6 text-xs text-gray-400 dark:text-slate-500">
              Aucun lieu correspondant à "{{ placeSearchQuery }}"
            </div>
          </div>
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
          U-Map s'engage à assurer la sécurité du campus. Décrivez le motif du signalement.
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
import { campusService } from '../services/campusService'
import { useMeta } from '../composables/useMeta'
import echo from '../services/echo'
import errorHandler from '../services/errorHandler'

defineOptions({
  name: 'Chat'
})

useMeta('Messagerie', "Échangez avec la communauté étudiante de l'UAC. Messagerie instantanée et partage de positions sur U-map.", { canonicalPath: '/chat' })


const router = useRouter()
const route = useRoute()

const isLoggedIn = ref(authService.isAuthenticated())
const activeChat = ref(null)
const chatMessages = ref([])
const messageInput = ref('')
const isTyping = ref(false)
const authMode = ref('login')
const loading = ref(false)
const messagesLoading = ref(false)
const conversationsLoading = ref(false)
const showNewChatModal = ref(false)
const searchQuery = ref('')
const students = ref([])
const conversations = ref([])
const showEmojiPicker = ref(false)

// Location & Place Sharing state
const showLocationModal = ref(false)
const locationTab = ref('gps') // 'gps' | 'places'
const placeSearchQuery = ref('')
const allCampusPlaces = ref([])
const gpsLoading = ref(false)

// Polling interval for robust local dev syncing
let activePollInterval = null
let currentEchoChannel = null
let typingTimeout = null

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

const addEmoji = (emoji) => {
  messageInput.value += emoji
  showEmojiPicker.value = false
}

const loadStudyBuddies = async () => {
    try {
        studyBuddies.value = await studyService.getStudyBuddies()
    } catch (e) { console.error(e) }
}

const loadCampusPlaces = async () => {
    try {
        const places = await campusService.getAllPlaces()
        allCampusPlaces.value = (places || []).map(p => ({
            id: p.properties?.id || p.id,
            name: p.properties?.name || p.name || 'Lieu',
            category: p.properties?.category || p.category || '',
            type: p.properties?.type || p.type || '',
            coordinates: p.geometry?.coordinates || null,
        })).filter(p => p.name && p.name !== 'Lieu')
    } catch (e) {
        console.error('Error loading campus places for sharing:', e)
    }
}

const filteredCampusPlaces = computed(() => {
    if (!placeSearchQuery.value) return allCampusPlaces.value.slice(0, 30)
    const q = placeSearchQuery.value.toLowerCase()
    return allCampusPlaces.value.filter(p => 
        p.name.toLowerCase().includes(q) || 
        (p.category && p.category.toLowerCase().includes(q))
    ).slice(0, 30)
})

const getPlaceIcon = (category) => {
    const c = (category || '').toLowerCase()
    if (c.includes('amphi') || c.includes('cours') || c.includes('enseign')) return 'ph:graduation-cap-bold'
    if (c.includes('resto') || c.includes('manger') || c.includes('cafet')) return 'ph:fork-knife-bold'
    if (c.includes('biblio') || c.includes('bu')) return 'ph:books-bold'
    if (c.includes('admin') || c.includes('rectorat')) return 'ph:bank-bold'
    if (c.includes('sante') || c.includes('medical')) return 'ph:first-aid-bold'
    if (c.includes('sport')) return 'ph:football-bold'
    return 'ph:map-pin-bold'
}

// Open Location Share modal
const openLocationModal = async () => {
    showLocationModal.value = true
    if (allCampusPlaces.value.length === 0) {
        await loadCampusPlaces()
    }
}

// Share current live GPS coordinates
const shareCurrentGPSLocation = () => {
    if (!navigator.geolocation) {
        errorHandler.error("La géolocalisation n'est pas supportée par votre navigateur.")
        return
    }

    gpsLoading.value = true
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            gpsLoading.value = false
            showLocationModal.value = false
            const { latitude, longitude } = position.coords
            const me = authService.getCurrentUser()
            const name = me?.name || 'Moi'
            const msgContent = `📍 Je suis ici sur le campus : [POSITION:${latitude.toFixed(6)},${longitude.toFixed(6)}|Position de ${name}]`
            await sendMessageDirect(msgContent)
        },
        (err) => {
            gpsLoading.value = false
            if (err.code === 1) {
                errorHandler.error("Accès GPS refusé. Veuillez autoriser la localisation.")
            } else {
                errorHandler.error("Impossible d'obtenir votre position GPS.")
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    )
}

// Share a specific campus place
const shareCampusPlace = async (place) => {
    showLocationModal.value = false
    const msgContent = `🏛️ Retrouvons-nous ici : [LIEU:${place.name}|${place.id}]`
    await sendMessageDirect(msgContent)
}

// Direct send helper (for location messages, quick starters)
const sendMessageDirect = async (content) => {
    if (!activeChat.value || !content.trim()) return
    messageInput.value = content
    await handleSendMessage()
}

// Render message body with modern interactive location cards
const renderMessageBody = (content, isMe) => {
    if (!content || typeof content !== 'string') return ''

    // 1. Sanitize HTML
    let sanitized = content
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')

    // 2. Parse [POSITION:lat,lng|Label] -> Interactive GPS Card
    sanitized = sanitized.replace(/\[POSITION:([^,]+),([^\|]+)\|([^\]]+)\]/g, (match, lat, lng, label) => {
        const btnClass = isMe
            ? 'bg-white/20 hover:bg-white/30 text-white border border-white/30'
            : 'bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20'
        
        const targetUrl = `/map?lat=${lat}&lng=${lng}&route=true&label=${encodeURIComponent(label)}`
        return `<div class="mt-2 mb-1 p-3 rounded-xl ${isMe ? 'bg-black/15 border border-white/20' : 'bg-blue-50 dark:bg-slate-900 border border-blue-200 dark:border-white/10'} shadow-sm">
            <div class="flex items-center gap-2 mb-1.5 font-bold ${isMe ? 'text-white' : 'text-blue-600 dark:text-blue-400'} text-xs">
                <span>📍 ${label}</span>
            </div>
            <div class="text-[10px] opacity-75 font-mono mb-2">Lat: ${lat} • Lng: ${lng}</div>
            <button type="button" onclick="window.navigateToMap('${targetUrl}')" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${btnClass} cursor-pointer border-none">
               <span>🗺️ Voir sur la carte & Itinéraire</span>
            </button>
        </div>`
    })

    // 3. Parse [LIEU:Nom|id] -> Interactive Campus Place Card
    sanitized = sanitized.replace(/\[LIEU:([^\|]+)\|([^\]]+)\]/g, (match, name, id) => {
        const btnClass = isMe
            ? 'bg-white/20 hover:bg-white/30 text-white border border-white/30'
            : 'bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20'

        const targetUrl = `/map?place=${id}&route=true`
        return `<div class="mt-2 mb-1 p-3 rounded-xl ${isMe ? 'bg-black/15 border border-white/20' : 'bg-blue-50 dark:bg-slate-900 border border-blue-200 dark:border-white/10'} shadow-sm">
            <div class="flex items-center gap-2 mb-1.5 font-bold ${isMe ? 'text-white' : 'text-blue-600 dark:text-blue-400'} text-xs">
                <span>🏛️ ${name}</span>
            </div>
            <button type="button" onclick="window.navigateToMap('${targetUrl}')" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${btnClass} cursor-pointer border-none">
               <span>📍 Voir sur la carte & Itinéraire</span>
            </button>
        </div>`
    })

    return sanitized
}

const formatSnippet = (content) => {
    if (!content) return ''
    if (content.includes('[POSITION:')) return '📍 Position GPS partagée'
    if (content.includes('[LIEU:')) {
        const match = content.match(/\[LIEU:([^\|]+)\|/)
        return match ? `📍 Lieu : ${match[1]}` : '📍 Lieu partagé'
    }
    return content
}

// Subscribe to a private Echo channel with clean sender isolation
const subscribeToChatChannel = () => {
    if (!activeChat.value || activeChat.value?.isAI || !echo) return
    unsubscribeFromChatChannel()

    const me = authService.getCurrentUser()
    if (!me) return

    const partnerId = activeChat.value.id
    const minId = Math.min(me.id, partnerId)
    const maxId = Math.max(me.id, partnerId)
    const channelName = `chat.${minId}.${maxId}`
    currentEchoChannel = channelName

    try {
        echo.private(channelName)
            .listen('.message.sent', (data) => {
                // Strict isolation: only append if this message belongs to the current open chat
                if (activeChat.value && !activeChat.value.isAI && activeChat.value.id === partnerId) {
                    if (data.sender_id === partnerId && data.receiver_id === me.id) {
                        // Check if already in list to avoid duplicates
                        if (!chatMessages.value.some(m => m.id === data.id)) {
                            chatMessages.value.push({
                                ...data,
                                content: data.content,
                            })
                            scrollToBottom()
                        }
                    }
                }
                _updateConversationSidebar(data)
            })
            .listenForWhisper('typing', (e) => {
                if (activeChat.value?.id === partnerId) {
                    isTyping.value = !!e.isTyping
                    if (typingTimeout) clearTimeout(typingTimeout)
                    if (e.isTyping) {
                        typingTimeout = setTimeout(() => {
                            isTyping.value = false
                        }, 3000)
                    }
                }
            })
    } catch (e) {
        console.warn('Echo subscription warning:', e)
    }

    // Start background sync poll every 3.5s for seamless local dev
    startPollingMessages(partnerId)
}

const startPollingMessages = (partnerId) => {
    stopPollingMessages()
    activePollInterval = setInterval(async () => {
        if (!activeChat.value || activeChat.value.isAI || activeChat.value.id !== partnerId) {
            stopPollingMessages()
            return
        }
        try {
            const response = await messageService.getMessages(partnerId)
            const msgs = Array.isArray(response.data) ? response.data : (Array.isArray(response) ? response : [])
            if (activeChat.value?.id === partnerId && msgs.length > 0) {
                // Merge cleanly preserving optimistic messages
                const optimistic = chatMessages.value.filter(m => m._optimistic)
                const realIds = new Set(msgs.map(m => m.id))
                const pendingOptimistic = optimistic.filter(o => !realIds.has(o.id))
                
                // If message count or latest ID changed, update smoothly
                if (msgs.length !== (chatMessages.value.length - optimistic.length)) {
                    chatMessages.value = [...msgs, ...pendingOptimistic]
                    scrollToBottom()
                }
            }
        } catch (e) {
            // silent poll failure
        }
    }, 3500)
}

const stopPollingMessages = () => {
    if (activePollInterval) {
        clearInterval(activePollInterval)
        activePollInterval = null
    }
}

const handleTyping = () => {
    if (!activeChat.value || activeChat.value?.isAI || !currentEchoChannel || !echo) return
    try {
        echo.private(currentEchoChannel).whisper('typing', { isTyping: true })
    } catch {}
}

const unsubscribeFromChatChannel = () => {
    stopPollingMessages()
    if (currentEchoChannel && echo) {
        try {
            echo.leaveChannel(`private-${currentEchoChannel}`)
        } catch {}
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

const handleAuthExpired = () => {
    isLoggedIn.value = false
    activeChat.value = null
    chatMessages.value = []
}

onMounted(async () => {
    window.navigateToMap = (path) => router.push(path)
    window.addEventListener('auth:expired', handleAuthExpired)
    if (isLoggedIn.value) {
        // Parallel load using cached data first
        await Promise.all([
            loadStudents(),
            loadConversations(),
            loadStudyBuddies(),
            loadCampusPlaces()
        ])

        const me = authService.getCurrentUser()
        if (me) {
            myStudyStatus.value = me.study_status || ''
            myStudyLocation.value = me.study_location || ''
        }

        if (route.query.chat) {
            await syncChatFromQuery()
        }
    }
})

onUnmounted(() => {
    delete window.navigateToMap
    window.removeEventListener('auth:expired', handleAuthExpired)
    unsubscribeFromChatChannel()
})

// Watch route query to switch active chats dynamically with zero bleed
watch(() => route.query.chat, async (newChatId, oldChatId) => {
    if (newChatId && newChatId !== oldChatId) {
        await syncChatFromQuery()
    } else if (!newChatId) {
        unsubscribeFromChatChannel()
        activeChat.value = null
        chatMessages.value = []
    }
})

const syncChatFromQuery = async () => {
    const chatId = route.query.chat
    if (!chatId) return

    if (chatId === 'ai') {
        selectAIChat()
    } else {
        let student = (Array.isArray(conversations.value) ? conversations.value.find(c => c.id == chatId) : null) ||
                      (Array.isArray(students.value) ? students.value.find(s => s.id == chatId) : null)
        if (!student) {
            await loadStudents()
            await loadConversations()
            student = (Array.isArray(conversations.value) ? conversations.value.find(c => c.id == chatId) : null) ||
                      (Array.isArray(students.value) ? students.value.find(s => s.id == chatId) : null)
        }
        if (student) {
            activeChat.value = { ...student, isAI: false }
            await loadMessages(student.id)
            subscribeToChatChannel()
        } else {
            closeChat()
        }
    }
}

const loadConversations = async () => {
    if (conversations.value.length === 0) {
        conversationsLoading.value = true
    }
    try {
        const res = await messageService.getConversations()
        conversations.value = res.data || res || []
    } catch (e) {
        console.error('Error loading conversations:', e)
        if (conversations.value.length === 0) {
            conversations.value = []
        }
    } finally {
        conversationsLoading.value = false
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

const logout = () => {
    authService.logout()
    isLoggedIn.value = false
    activeChat.value = null
    router.replace({ query: {} })
    router.push('/login')
}

const selectAIChat = () => {
    unsubscribeFromChatChannel()
    chatMessages.value = [
        { role: 'assistant', content: "Bonjour ! 👋 Je suis l'intelligence artificielle officielle de l'UAC. Posez-moi des questions sur les lieux, amphithéâtres, restaurants ou la bibliothèque pour vous orienter directement sur le campus !" }
    ]
    activeChat.value = { id: 'ai', name: 'U-Map Copilot AI', isAI: true, avatar: null }
    if (route.query.chat !== 'ai') {
        router.push({ query: { chat: 'ai' } })
    }
    scrollToBottom()
}

const selectChat = async (student) => {
    if (activeChat.value?.id === student.id) return

    unsubscribeFromChatChannel()
    activeChat.value = { ...student, isAI: false }

    if (route.query.chat != student.id) {
        await router.push({ query: { chat: student.id } })
    }
    await loadMessages(student.id)
    subscribeToChatChannel()
}

const closeChat = () => {
    unsubscribeFromChatChannel()
    activeChat.value = null
    chatMessages.value = []
    router.push({ query: {} })
}

const loadMessages = async (targetId) => {
    const fetchId = targetId || activeChat.value?.id
    if (!fetchId || activeChat.value?.isAI) return

    // Fast memory/storage cache lookup first
    const cached = messageService.messagesCache?.get(String(fetchId))
    if (cached && Array.isArray(cached) && cached.length > 0) {
        chatMessages.value = cached
        scrollToBottom()
        messagesLoading.value = false
    } else {
        messagesLoading.value = true
    }

    try {
        const response = await messageService.getMessages(fetchId)
        // Strict guard: verify user hasn't switched chat while request was pending
        if (activeChat.value?.id === fetchId) {
            const list = Array.isArray(response.data) ? response.data : (Array.isArray(response) ? response : [])
            chatMessages.value = list
            scrollToBottom()
        }
    } catch (e) {
        console.error('Error loading messages:', e)
    } finally {
        messagesLoading.value = false
    }
}


const handleSendMessage = async () => {
    if (!messageInput.value.trim() || !activeChat.value) return
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
        const targetReceiverId = activeChat.value.id

        // Optimistic update: add message immediately in UI
        const optimisticMsg = {
            id: `temp-${Date.now()}`,
            content,
            sender_id: me?.id,
            receiver_id: targetReceiverId,
            created_at: new Date().toISOString(),
            is_read: false,
            _optimistic: true,
        }
        chatMessages.value.push(optimisticMsg)
        scrollToBottom()

        try {
            const newMsg = await messageService.sendMessage(targetReceiverId, content)

            // Replace optimistic message with real one from server
            const idx = chatMessages.value.findIndex(m => m._optimistic && m.id === optimisticMsg.id)
            if (idx !== -1) {
                chatMessages.value.splice(idx, 1, newMsg)
            }

            // Update conversation sidebar locally
            _updateConversationSidebar(newMsg)
        } catch (e) {
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
    return msg.sender_id === me?.id
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

const formatMessageTime = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return ''
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const scrollToBottom = () => {
    nextTick(() => {
        nextTick(() => {
            const container = document.getElementById('chat-messages')
            if (container) {
                container.scrollTop = container.scrollHeight
            }
        })
    })
}

const _updateConversationSidebar = (msg) => {
    const me = authService.getCurrentUser()
    if (!me || !msg) return

    const otherId = msg.sender_id === me.id ? msg.receiver_id : msg.sender_id
    const lastMsgPayload = {
        id: msg.id,
        content: msg.content,
        created_at: msg.created_at,
        sender_id: msg.sender_id,
        is_read: msg.sender_id === me.id,
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
        const rest = conversations.value.filter((_, i) => i !== existingIdx)
        conversations.value = [updated, ...rest]
    } else {
        const buddy = students.value.find(s => s.id === otherId)
        conversations.value = [{
            ...(buddy || { id: otherId, name: `Étudiant #${otherId}` }),
            last_message: lastMsgPayload,
            last_message_at: msg.created_at,
            unread_count: msg.sender_id !== me.id ? 1 : 0,
        }, ...(conversations.value || [])]
    }
}

const submitReport = async () => {
    if (reporting.value || !activeChat.value) return
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
            errorHandler.success('L\'utilisateur a bien été signalé aux administrateurs.')
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
