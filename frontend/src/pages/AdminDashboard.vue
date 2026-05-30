<template>
  <div class="admin-root">

    <!-- ══════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════ -->
    <transition name="sidebar-slide">
      <aside class="sidebar" :class="{ 'sidebar-open': mobileMenuOpen }">
        <!-- Logo -->
        <div class="sidebar-logo">
          <div class="sidebar-logo-icon">
            <Icon icon="ph:map-trifold-fill" />
          </div>
          <div>
            <span class="sidebar-logo-text">U-Map<span class="text-indigo">.</span></span>
            <p class="sidebar-logo-sub">Administration</p>
          </div>
        </div>

        <!-- Nav -->
        <nav class="sidebar-nav">
          <p class="nav-section-label">Général</p>
          <button
            v-for="item in navItems" :key="item.id"
            @click="setTab(item.id)"
            class="nav-item"
            :class="{ 'nav-item--active': currentTab === item.id }"
          >
            <div class="nav-item-left">
              <div class="nav-item-icon-wrap">
                <Icon :icon="currentTab === item.id ? item.iconFill : item.icon" class="nav-item-icon" />
              </div>
              <span>{{ item.label }}</span>
            </div>
            <div class="nav-item-badge" v-if="item.badge && item.badge() > 0">
              {{ item.badge() }}
            </div>
          </button>
        </nav>

        <!-- Bottom -->
        <div class="sidebar-bottom">
          <div class="sidebar-admin-info">
            <div class="admin-avatar">
              <Icon icon="ph:user-circle-fill" />
            </div>
            <div>
              <p class="admin-name">Administrateur</p>
              <div class="admin-status">
                <span class="status-dot"></span>
                <span>En ligne</span>
              </div>
            </div>
          </div>
          <button @click="handleLogout" class="logout-btn">
            <Icon icon="ph:sign-out-bold" />
            <span>Déconnexion</span>
          </button>
        </div>
      </aside>
    </transition>

    <!-- Sidebar backdrop for mobile -->
    <transition name="fade">
      <div v-if="mobileMenuOpen" class="sidebar-backdrop" @click="mobileMenuOpen = false"></div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MAIN
    ══════════════════════════════════════════════════════ -->
    <main class="admin-main">

      <!-- Topbar -->
      <header class="topbar">
        <div class="topbar-left">
          <!-- Mobile hamburger -->
          <button class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen">
            <Icon icon="ph:list-bold" />
          </button>
          <div>
            <h1 class="topbar-title">{{ tabTitles[currentTab] }}</h1>
            <p class="topbar-sub">U-Map · Panneau d'Administration</p>
          </div>
        </div>
        <div class="topbar-right">
          <div class="topbar-pill">
            <span class="pulse-dot"></span>
            <span>Système opérationnel</span>
          </div>
          <button class="topbar-refresh" @click="loadData" :class="{ 'is-refreshing': loading }">
            <Icon icon="ph:arrows-clockwise-bold" />
          </button>
          <div class="topbar-time">{{ currentTime }}</div>
        </div>
      </header>

      <!-- Content -->
      <div class="content-area">

        <!-- Loading overlay -->
        <transition name="fade">
          <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner">
              <div class="spinner-ring"></div>
              <div class="spinner-ring spinner-ring--2"></div>
              <Icon icon="ph:map-trifold-fill" class="spinner-icon" />
            </div>
            <p class="loading-text">Chargement des données...</p>
          </div>
        </transition>

        <div class="content-inner">
          <transition name="tab-switch" mode="out-in">

            <!-- ════════════════════════════════
                 TAB: DASHBOARD
            ════════════════════════════════ -->
            <div v-if="currentTab === 'dashboard'" key="dashboard">

              <!-- Stats Grid -->
              <div class="stats-grid">
                <div
                  v-for="(card, i) in statCards" :key="i"
                  class="stat-card"
                  :class="card.color"
                  :style="{ '--delay': i * 0.08 + 's' }"
                  @click="card.onClick && card.onClick()"
                >
                  <div class="stat-card-bg"></div>
                  <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                      <Icon :icon="card.icon" class="stat-icon" />
                    </div>
                    <span v-if="card.trend" class="stat-trend">
                      <Icon icon="ph:trend-up-bold" />
                      +{{ card.trend }}
                    </span>
                    <span v-if="card.alert" class="stat-alert-badge">
                      <Icon icon="ph:warning-bold" />
                      Action requise
                    </span>
                  </div>
                  <div class="stat-card-value">{{ card.value }}</div>
                  <div class="stat-card-label">{{ card.label }}</div>
                  <div class="stat-card-bar">
                    <div class="stat-bar-fill" :style="{ width: card.fill + '%' }"></div>
                  </div>
                </div>
              </div>

              <!-- Second row -->
              <div class="dash-grid">

                <!-- Category distribution -->
                <div class="dash-card">
                  <div class="dash-card-head">
                    <h2 class="dash-card-title">
                      <Icon icon="ph:chart-pie-fill" class="title-icon" />
                      Répartition des Lieux
                    </h2>
                  </div>
                  <div class="category-list">
                    <div
                      v-for="(item, i) in (stats?.placesByCategory || [])"
                      :key="i"
                      class="category-item"
                      :style="{ '--delay': i * 0.05 + 's' }"
                    >
                      <div class="cat-info">
                        <span class="cat-dot" :style="{ background: catColors[i % catColors.length] }"></span>
                        <span class="cat-name">{{ item.category }}</span>
                        <span class="cat-count">{{ item.count }}</span>
                      </div>
                      <div class="cat-bar-bg">
                        <div
                          class="cat-bar-fill"
                          :style="{
                            width: stats?.totalPlaces ? `${(item.count / stats.totalPlaces) * 100}%` : '0%',
                            background: catColors[i % catColors.length]
                          }"
                        ></div>
                      </div>
                    </div>
                    <div v-if="!stats?.placesByCategory?.length" class="empty-state-small">
                      <Icon icon="ph:chart-pie" />
                      Aucune donnée de catégorie
                    </div>
                  </div>
                </div>

                <!-- Quick actions -->
                <div class="dash-actions-card">
                  <div class="dash-actions-glow"></div>
                  <div class="dash-actions-content">
                    <Icon icon="ph:lightning-fill" class="dash-actions-hero" />
                    <h2 class="dash-actions-title">Actions Rapides</h2>
                    <p class="dash-actions-sub">Gérez les éléments critiques en priorité.</p>
                    <div class="quick-actions">
                      <button @click="setTab('places')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon">
                            <Icon icon="ph:map-pin-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Lieux en attente</p>
                            <p class="qa-desc">{{ pendingPlacesList.length }} lieu(x) à modérer</p>
                          </div>
                        </div>
                        <span class="qa-badge" v-if="pendingPlacesList.length > 0">{{ pendingPlacesList.length }}</span>
                        <Icon icon="ph:arrow-right-bold" class="qa-arrow" />
                      </button>
                      <button @click="setTab('reports')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon qa-icon--red">
                            <Icon icon="ph:flag-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Signalements</p>
                            <p class="qa-desc">{{ pendingReportsList.length }} signalement(s) en attente</p>
                          </div>
                        </div>
                        <span class="qa-badge qa-badge--red" v-if="pendingReportsList.length > 0">{{ pendingReportsList.length }}</span>
                        <Icon icon="ph:arrow-right-bold" class="qa-arrow" />
                      </button>
                      <button @click="setTab('users')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon qa-icon--green">
                            <Icon icon="ph:users-three-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Utilisateurs</p>
                            <p class="qa-desc">{{ stats?.totalUsers || 0 }} inscrits au total</p>
                          </div>
                        </div>
                        <Icon icon="ph:arrow-right-bold" class="qa-arrow" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Recent activity -->
              <div class="dash-card">
                <div class="dash-card-head">
                  <h2 class="dash-card-title">
                    <Icon icon="ph:activity-bold" class="title-icon" />
                    Activité Récente
                  </h2>
                </div>
                <div class="activity-list">
                  <div class="activity-item" v-if="stats?.recentUsers > 0">
                    <div class="activity-dot blue"></div>
                    <div class="activity-content">
                      <p class="activity-text">
                        <strong>{{ stats.recentUsers }}</strong> nouvel(s) utilisateur(s) inscrit(s) cette semaine
                      </p>
                    </div>
                    <span class="activity-time">Cette semaine</span>
                  </div>
                  <div class="activity-item" v-if="pendingPlacesList.length > 0">
                    <div class="activity-dot orange"></div>
                    <div class="activity-content">
                      <p class="activity-text">
                        <strong>{{ pendingPlacesList.length }}</strong> lieu(x) en attente de validation
                      </p>
                    </div>
                    <button @click="setTab('places')" class="activity-action">Voir →</button>
                  </div>
                  <div class="activity-item" v-if="pendingReportsList.length > 0">
                    <div class="activity-dot red"></div>
                    <div class="activity-content">
                      <p class="activity-text">
                        <strong>{{ pendingReportsList.length }}</strong> signalement(s) en attente de traitement
                      </p>
                    </div>
                    <button @click="setTab('reports')" class="activity-action activity-action--red">Traiter →</button>
                  </div>
                  <div v-if="!stats?.recentUsers && !pendingPlacesList.length && !pendingReportsList.length" class="empty-state-small">
                    <Icon icon="ph:check-circle-fill" />
                    Tout est à jour !
                  </div>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: USERS
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'users'" key="users">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Gestion des Utilisateurs</h2>
                  <p class="section-sub">{{ filteredUsers.length }} utilisateur(s) affiché(s) sur {{ users.length }} total</p>
                </div>
              </div>

              <!-- Filters bar -->
              <div class="filters-bar">
                <div class="search-wrap">
                  <Icon icon="ph:magnifying-glass-bold" class="search-icon" />
                  <input
                    v-model="userSearch"
                    placeholder="Rechercher par nom ou email..."
                    class="search-input"
                  />
                  <button v-if="userSearch" @click="userSearch = ''" class="search-clear">
                    <Icon icon="ph:x-circle-fill" />
                  </button>
                </div>
                <div class="filter-tabs">
                  <button
                    v-for="f in userFilters" :key="f.id"
                    @click="userFilter = f.id"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': userFilter === f.id }"
                  >
                    <Icon :icon="f.icon" />
                    {{ f.label }}
                    <span class="filter-count">{{ f.count() }}</span>
                  </button>
                </div>
              </div>

              <!-- Users table -->
              <div class="table-card">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Utilisateur</th>
                      <th>Email</th>
                      <th>Inscription</th>
                      <th>Statut</th>
                      <th class="th-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="user in paginatedUsers" :key="user.id" class="table-row">
                      <td>
                        <div class="user-cell">
                          <div class="user-avatar" :style="{ background: userAvatarGradient(user.name) }">
                            {{ user.name?.charAt(0).toUpperCase() }}
                          </div>
                          <div>
                            <p class="user-name" :class="{ 'user-name--restricted': user.is_restricted }">
                              {{ user.name }}
                            </p>
                            <p class="user-id">#{{ user.id }}</p>
                          </div>
                        </div>
                      </td>
                      <td class="td-email">{{ user.email }}</td>
                      <td class="td-date">{{ formatDate(user.created_at) }}</td>
                      <td>
                        <span class="status-chip" :class="user.is_restricted ? 'chip-restricted' : 'chip-active'">
                          <Icon :icon="user.is_restricted ? 'ph:lock-fill' : 'ph:check-circle-fill'" />
                          {{ user.is_restricted ? 'Restreint' : 'Actif' }}
                        </span>
                      </td>
                      <td class="td-actions">
                        <button
                          @click="confirmAction('restrict', user)"
                          class="action-btn"
                          :class="user.is_restricted ? 'btn-unlock' : 'btn-lock'"
                          :title="user.is_restricted ? 'Débloquer' : 'Restreindre'"
                        >
                          <Icon :icon="user.is_restricted ? 'ph:lock-open-fill' : 'ph:lock-key-fill'" />
                        </button>
                        <button
                          @click="confirmAction('delete', user)"
                          class="action-btn btn-delete"
                          title="Supprimer"
                        >
                          <Icon icon="ph:trash-fill" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                      <td colspan="5">
                        <div class="empty-state">
                          <Icon icon="ph:user-minus" class="empty-icon" />
                          <p>Aucun utilisateur trouvé</p>
                          <button @click="userSearch = ''; userFilter = 'all'" class="empty-reset">
                            Réinitialiser les filtres
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination" v-if="totalPages > 1">
                  <button @click="page--" :disabled="page === 1" class="page-btn">
                    <Icon icon="ph:caret-left-bold" />
                  </button>
                  <button
                    v-for="p in totalPages" :key="p"
                    @click="page = p"
                    class="page-btn"
                    :class="{ 'page-btn--active': page === p }"
                  >{{ p }}</button>
                  <button @click="page++" :disabled="page === totalPages" class="page-btn">
                    <Icon icon="ph:caret-right-bold" />
                  </button>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: PLACES
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'places'" key="places">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Base de Données des Lieux</h2>
                  <p class="section-sub">{{ places.length }} lieu(x) enregistré(s)</p>
                </div>
              </div>

              <!-- Pending places alert -->
              <div v-if="pendingPlacesList.length > 0" class="pending-alert">
                <div class="pending-alert-icon">
                  <Icon icon="ph:warning-octagon-fill" />
                </div>
                <div>
                  <p class="pending-alert-title">{{ pendingPlacesList.length }} lieu(x) nécessitent votre validation</p>
                  <p class="pending-alert-sub">Examinez et approuvez ou rejetez chaque lieu soumis.</p>
                </div>
              </div>

              <!-- Pending places grid -->
              <div v-if="pendingPlacesList.length > 0" class="places-grid">
                <div v-for="place in pendingPlacesList" :key="place.id" class="place-card place-card--pending">
                  <div class="place-card-badge">EN ATTENTE</div>
                  <h3 class="place-card-name">{{ place.name }}</h3>
                  <div class="place-card-meta">
                    <span><Icon icon="ph:tag-fill" /> {{ place.category }}</span>
                    <span><Icon icon="ph:user-fill" /> {{ place.added_by }}</span>
                  </div>
                  <p class="place-card-desc">{{ place.description || 'Aucune description fournie.' }}</p>
                  <div class="place-card-coords">
                    <Icon icon="ph:map-pin-fill" />
                    {{ place.latitude?.toFixed(4) }}, {{ place.longitude?.toFixed(4) }}
                  </div>
                  <div class="place-card-actions">
                    <button @click="approvePlace(place.id)" class="place-btn place-btn--approve">
                      <Icon icon="ph:check-bold" />
                      Approuver
                    </button>
                    <button @click="confirmAction('deletePlace', place)" class="place-btn place-btn--reject">
                      <Icon icon="ph:x-bold" />
                      Rejeter
                    </button>
                  </div>
                </div>
              </div>

              <!-- Approved places table -->
              <div class="table-card" style="margin-top: 24px;">
                <div class="table-head-bar">
                  <h3 class="table-head-title">
                    <Icon icon="ph:check-circle-fill" style="color: #22c55e;" />
                    Lieux Approuvés ({{ approvedPlacesList.length }})
                  </h3>
                </div>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Nom</th>
                      <th>Catégorie</th>
                      <th>Ajouté par</th>
                      <th class="th-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="place in approvedPlacesList" :key="place.id" class="table-row">
                      <td class="td-bold">{{ place.name }}</td>
                      <td>
                        <span class="cat-tag">{{ place.category }}</span>
                      </td>
                      <td class="td-muted">{{ place.added_by }}</td>
                      <td class="td-actions">
                        <button @click="confirmAction('deletePlace', place)" class="action-btn btn-delete" title="Supprimer">
                          <Icon icon="ph:trash-fill" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="approvedPlacesList.length === 0">
                      <td colspan="4">
                        <div class="empty-state">
                          <Icon icon="ph:map-pin" class="empty-icon" />
                          <p>Aucun lieu approuvé</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: MESSAGES
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'messages'" key="messages">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Logs de Messagerie</h2>
                  <p class="section-sub">50 messages les plus récents · Usage de débogage uniquement</p>
                </div>
              </div>
              <div class="table-card">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>De</th>
                      <th>À</th>
                      <th>Contenu</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="msg in messages" :key="msg.id" class="table-row">
                      <td class="td-date">{{ formatDateTime(msg.created_at) }}</td>
                      <td class="td-bold">{{ msg.sender?.name || '—' }}</td>
                      <td class="td-muted">{{ msg.receiver?.name || '—' }}</td>
                      <td class="td-truncate" :title="msg.content">{{ msg.content }}</td>
                      <td>
                        <span class="status-chip" :class="msg.is_read ? 'chip-read' : 'chip-unread'">
                          <Icon :icon="msg.is_read ? 'ph:check-double-bold' : 'ph:clock-bold'" />
                          {{ msg.is_read ? 'Lu' : 'Non lu' }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="messages.length === 0">
                      <td colspan="5">
                        <div class="empty-state">
                          <Icon icon="ph:chats" class="empty-icon" />
                          <p>Aucun message à afficher</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: REPORTS
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'reports'" key="reports">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Modération & Signalements</h2>
                  <p class="section-sub">{{ pendingReportsList.length }} signalement(s) en attente de traitement</p>
                </div>
              </div>

              <div v-if="pendingReportsList.length > 0" class="reports-grid">
                <div v-for="report in pendingReportsList" :key="report.id" class="report-card">
                  <div class="report-card-top">
                    <div class="report-card-flag">
                      <Icon icon="ph:flag-fill" />
                    </div>
                    <div>
                      <p class="report-card-date">{{ formatDate(report.created_at) }}</p>
                    </div>
                    <span class="report-pending-badge">En attente</span>
                  </div>
                  <div class="report-users">
                    <div class="report-user">
                      <span class="report-user-label">Plaignant</span>
                      <span class="report-user-name">{{ report.reporter?.name || '—' }}</span>
                    </div>
                    <Icon icon="ph:arrow-right-bold" class="report-arrow" />
                    <div class="report-user">
                      <span class="report-user-label">Signalé</span>
                      <span class="report-user-name report-user-name--red">{{ report.reported_user?.name || '—' }}</span>
                    </div>
                  </div>
                  <div class="report-reason">
                    <Icon icon="ph:quotes-bold" />
                    {{ report.reason }}
                  </div>
                  <div class="report-actions">
                    <button @click="resolveReport(report.id)" class="report-btn report-btn--resolve">
                      <Icon icon="ph:check-circle-bold" />
                      Résoudre
                    </button>
                    <button @click="confirmAction('restrict', { id: report.reported_user_id, name: report.reported_user?.name })" class="report-btn report-btn--restrict">
                      <Icon icon="ph:lock-key-bold" />
                      Restreindre
                    </button>
                  </div>
                </div>
              </div>

              <!-- Resolved -->
              <div class="table-card" style="margin-top: 24px;">
                <div class="table-head-bar">
                  <h3 class="table-head-title">
                    <Icon icon="ph:check-circle-fill" style="color: #22c55e;" />
                    Signalements Résolus ({{ resolvedReportsList.length }})
                  </h3>
                </div>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Plaignant</th>
                      <th>Signalé</th>
                      <th>Raison</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="report in resolvedReportsList" :key="report.id" class="table-row table-row--resolved">
                      <td class="td-date">{{ formatDate(report.created_at) }}</td>
                      <td class="td-muted">{{ report.reporter?.name || '—' }}</td>
                      <td class="td-muted">{{ report.reported_user?.name || '—' }}</td>
                      <td class="td-muted">{{ report.reason }}</td>
                    </tr>
                    <tr v-if="resolvedReportsList.length === 0">
                      <td colspan="4">
                        <div class="empty-state">
                          <Icon icon="ph:flag" class="empty-icon" />
                          <p>Aucun signalement résolu</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </transition>
        </div>
      </div>
    </main>

    <!-- ══════════════════════════════════════════════════════
         CONFIRMATION MODAL (replaces native confirm/alert)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="modal.visible" class="modal-overlay" @click.self="modal.visible = false">
        <div class="modal-card">
          <div class="modal-icon-wrap" :class="modal.type">
            <Icon :icon="modal.icon" />
          </div>
          <h3 class="modal-title">{{ modal.title }}</h3>
          <p class="modal-body">{{ modal.body }}</p>
          <div class="modal-actions">
            <button @click="modal.visible = false" class="modal-btn modal-btn--cancel">
              Annuler
            </button>
            <button @click="modal.confirm()" class="modal-btn" :class="'modal-btn--' + modal.type">
              <Icon :icon="modal.icon" />
              {{ modal.confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast notification -->
    <transition name="toast">
      <div v-if="toast.visible" class="toast" :class="'toast--' + toast.type">
        <Icon :icon="toast.type === 'success' ? 'ph:check-circle-fill' : 'ph:warning-circle-fill'" />
        {{ toast.message }}
      </div>
    </transition>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
const reports = ref([])

// User filters
const userSearch = ref('')
const userFilter = ref('all')
const page = ref(1)
const PER_PAGE = 8

// Time
const currentTime = ref('')
let timeInterval = null

// ─── NAV ───────────────────────────────────────
const navItems = computed(() => [
  {
    id: 'dashboard',
    label: "Vue d'ensemble",
    icon: 'ph:squares-four',
    iconFill: 'ph:squares-four-fill',
    badge: null
  },
  {
    id: 'users',
    label: 'Utilisateurs',
    icon: 'ph:users',
    iconFill: 'ph:users-fill',
    badge: () => users.value.length
  },
  {
    id: 'places',
    label: 'Lieux',
    icon: 'ph:map-pin',
    iconFill: 'ph:map-pin-fill',
    badge: () => pendingPlacesList.value.length
  },
  {
    id: 'messages',
    label: 'Messages',
    icon: 'ph:chats',
    iconFill: 'ph:chats-fill',
    badge: null
  },
  {
    id: 'reports',
    label: 'Signalements',
    icon: 'ph:flag',
    iconFill: 'ph:flag-fill',
    badge: () => pendingReportsList.value.length
  }
])

const tabTitles = {
  dashboard: "Vue d'ensemble",
  users: "Gestion des Utilisateurs",
  places: "Lieux & Cartographie",
  messages: "Logs Messagerie",
  reports: "Modération"
}

// ─── COMPUTED ───────────────────────────────────
const pendingPlacesList = computed(() => places.value.filter(p => p.status === 'pending'))
const approvedPlacesList = computed(() => places.value.filter(p => p.status === 'approved'))
const pendingReportsList = computed(() => reports.value.filter(r => r.status === 'pending'))
const resolvedReportsList = computed(() => reports.value.filter(r => r.status === 'resolved'))

const filteredUsers = computed(() => {
  let list = users.value
  if (userFilter.value === 'active') list = list.filter(u => !u.is_restricted)
  if (userFilter.value === 'restricted') list = list.filter(u => u.is_restricted)
  if (userSearch.value.trim()) {
    const q = userSearch.value.toLowerCase()
    list = list.filter(u => u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q))
  }
  return list
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / PER_PAGE))
const paginatedUsers = computed(() => {
  const s = (page.value - 1) * PER_PAGE
  return filteredUsers.value.slice(s, s + PER_PAGE)
})

const userFilters = computed(() => [
  { id: 'all', label: 'Tous', icon: 'ph:users', count: () => users.value.length },
  { id: 'active', label: 'Actifs', icon: 'ph:check-circle', count: () => users.value.filter(u => !u.is_restricted).length },
  { id: 'restricted', label: 'Restreints', icon: 'ph:lock', count: () => users.value.filter(u => u.is_restricted).length }
])

const statCards = computed(() => {
  const total = stats.value?.totalUsers || 0
  return [
    {
      icon: 'ph:users-three-fill',
      value: stats.value?.totalUsers || 0,
      label: 'Utilisateurs inscrits',
      trend: stats.value?.recentUsers,
      color: 'card-blue',
      fill: Math.min((stats.value?.totalUsers || 0) / 100 * 100, 100),
      onClick: () => setTab('users')
    },
    {
      icon: 'ph:map-pin-fill',
      value: stats.value?.approvedPlaces || 0,
      label: 'Lieux approuvés',
      color: 'card-green',
      fill: Math.min((stats.value?.approvedPlaces || 0) / 50 * 100, 100),
      onClick: () => setTab('places')
    },
    {
      icon: 'ph:warning-circle-fill',
      value: pendingPlacesList.value.length,
      label: 'Lieux en attente',
      alert: pendingPlacesList.value.length > 0,
      color: pendingPlacesList.value.length > 0 ? 'card-orange' : 'card-gray',
      fill: Math.min(pendingPlacesList.value.length / 10 * 100, 100),
      onClick: () => setTab('places')
    },
    {
      icon: 'ph:chats-fill',
      value: stats.value?.totalMessages || 0,
      label: 'Messages échangés',
      trend: stats.value?.recentMessages,
      color: 'card-purple',
      fill: Math.min((stats.value?.totalMessages || 0) / 200 * 100, 100),
      onClick: () => setTab('messages')
    }
  ]
})

const catColors = [
  '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#06b6d4',
  '#a855f7', '#f97316', '#14b8a6', '#ec4899', '#84cc16'
]

// ─── MODAL ─────────────────────────────────────
const modal = ref({
  visible: false,
  title: '',
  body: '',
  icon: '',
  type: 'danger',
  confirmLabel: 'Confirmer',
  confirm: () => {}
})

const showModal = (opts) => {
  modal.value = { visible: true, ...opts }
}

// ─── TOAST ─────────────────────────────────────
const toast = ref({ visible: false, message: '', type: 'success' })
let toastTimer = null
const showToast = (message, type = 'success') => {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { visible: true, message, type }
  toastTimer = setTimeout(() => { toast.value.visible = false }, 3500)
}

// ─── INIT ─────────────────────────────────────
onMounted(async () => {
  // Update clock
  const tick = () => {
    currentTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  }
  tick()
  timeInterval = setInterval(tick, 30000)

  // Auth check
  if (!adminService.isAuthenticated()) {
    router.push('/admin/login'); return
  }
  try {
    const isValid = await adminService.verify()
    if (!isValid) { adminService.logout(); router.push('/admin/login'); return }
    await loadData()
  } catch (e) {
    router.push('/admin/login')
  }
})

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval)
})

const loadData = async () => {
  loading.value = true
  try {
    const [s, u, p, m, r] = await Promise.all([
      adminService.getStats(),
      adminService.getUsers(),
      adminService.getPlaces(),
      adminService.getMessages(),
      adminService.getReports()
    ])
    stats.value = s
    users.value = u
    places.value = p
    messages.value = m
    reports.value = r
  } catch (e) {
    showToast('Erreur lors du chargement des données', 'error')
  } finally {
    loading.value = false
  }
}

const setTab = (tab) => {
  currentTab.value = tab
  mobileMenuOpen.value = false
  page.value = 1
}

// ─── ACTIONS (with modal confirm) ─────────────
const confirmAction = (type, item) => {
  const configs = {
    delete: {
      title: 'Supprimer l\'utilisateur',
      body: `Êtes-vous sûr de vouloir supprimer définitivement "${item.name}" ? Cette action est irréversible.`,
      icon: 'ph:trash-fill',
      type: 'danger',
      confirmLabel: 'Supprimer',
      confirm: async () => {
        modal.value.visible = false
        await deleteUser(item.id)
      }
    },
    restrict: {
      title: item.is_restricted ? 'Débloquer l\'utilisateur' : 'Restreindre l\'utilisateur',
      body: `Voulez-vous ${item.is_restricted ? 'débloquer' : 'restreindre'} l'accès de "${item.name}" ?`,
      icon: item.is_restricted ? 'ph:lock-open-fill' : 'ph:lock-key-fill',
      type: 'warning',
      confirmLabel: item.is_restricted ? 'Débloquer' : 'Restreindre',
      confirm: async () => {
        modal.value.visible = false
        await toggleRestrictUser(item.id)
      }
    },
    deletePlace: {
      title: 'Rejeter / Supprimer le lieu',
      body: `Êtes-vous sûr de vouloir supprimer "${item.name}" ? Cette action est irréversible.`,
      icon: 'ph:trash-fill',
      type: 'danger',
      confirmLabel: 'Supprimer',
      confirm: async () => {
        modal.value.visible = false
        await deletePlace(item.id)
      }
    }
  }
  showModal(configs[type])
}

const deleteUser = async (id) => {
  try {
    await adminService.deleteUser(id)
    users.value = users.value.filter(u => u.id !== id)
    if (stats.value) stats.value.totalUsers--
    showToast('Utilisateur supprimé avec succès')
  } catch (e) { showToast(e.message, 'error') }
}

const toggleRestrictUser = async (id) => {
  try {
    const res = await adminService.toggleRestrictUser(id)
    const user = users.value.find(u => u.id === id)
    if (user) user.is_restricted = res.is_restricted
    showToast(res.message)
  } catch (e) { showToast(e.message, 'error') }
}

const resolveReport = async (id) => {
  try {
    await adminService.resolveReport(id)
    const r = reports.value.find(rep => rep.id === id)
    if (r) r.status = 'resolved'
    showToast('Signalement marqué comme résolu')
  } catch (e) { showToast(e.message, 'error') }
}

const approvePlace = async (id) => {
  try {
    await adminService.approvePlace(id)
    const p = places.value.find(p => p.id === id)
    if (p) p.status = 'approved'
    if (stats.value) { stats.value.pendingPlaces--; stats.value.approvedPlaces++ }
    showToast('Lieu approuvé avec succès')
  } catch (e) { showToast(e.message, 'error') }
}

const deletePlace = async (id) => {
  try {
    await adminService.deletePlace(id)
    const p = places.value.find(p => p.id === id)
    if (p && stats.value) {
      if (p.status === 'pending') stats.value.pendingPlaces--
      else stats.value.approvedPlaces--
    }
    places.value = places.value.filter(p => p.id !== id)
    showToast('Lieu supprimé avec succès')
  } catch (e) { showToast(e.message, 'error') }
}

const handleLogout = () => {
  adminService.logout()
  router.push('/admin/login')
}

// ─── HELPERS ─────────────────────────────────
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const formatDateTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

const avatarGradients = [
  ['#6366f1', '#8b5cf6'], ['#06b6d4', '#3b82f6'],
  ['#22c55e', '#14b8a6'], ['#f59e0b', '#ef4444'],
  ['#ec4899', '#a855f7']
]
const userAvatarGradient = (name) => {
  const idx = (name?.charCodeAt(0) || 0) % avatarGradients.length
  return `linear-gradient(135deg, ${avatarGradients[idx][0]}, ${avatarGradients[idx][1]})`
}
</script>

<style scoped>
/* ══════════════════════════════════════════
   GLOBAL VARS & RESET
══════════════════════════════════════════ */
.admin-root {
  --bg: #050a14;
  --surface: #0d1526;
  --surface-2: #111d33;
  --border: rgba(99,102,241,0.15);
  --border-hover: rgba(99,102,241,0.3);
  --indigo: #6366f1;
  --indigo-light: #818cf8;
  --text-primary: #e2e8f0;
  --text-secondary: rgba(148,163,184,0.7);
  --text-muted: rgba(148,163,184,0.4);
  --green: #22c55e;
  --orange: #f59e0b;
  --red: #ef4444;
  --blue: #3b82f6;
  --purple: #a855f7;

  min-height: 100vh;
  background: var(--bg);
  display: flex;
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}
.text-indigo { color: var(--indigo); }

/* ══════════════════════════════════════════
   SIDEBAR
══════════════════════════════════════════ */
.sidebar {
  width: 260px;
  min-height: 100vh;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0; bottom: 0;
  z-index: 50;
  flex-shrink: 0;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 24px 20px;
  border-bottom: 1px solid var(--border);
}
.sidebar-logo-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--indigo), #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  color: white;
  box-shadow: 0 8px 24px rgba(99,102,241,0.35);
  flex-shrink: 0;
}
.sidebar-logo-text {
  font-size: 20px;
  font-weight: 900;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}
.sidebar-logo-sub {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: var(--text-muted);
  margin: 0;
  font-weight: 600;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 12px;
  overflow-y: auto;
}
.nav-section-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: var(--text-muted);
  padding: 0 8px;
  margin-bottom: 8px;
}
.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 12px;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  margin-bottom: 2px;
  font-family: inherit;
}
.nav-item:hover {
  background: rgba(99,102,241,0.08);
  color: var(--text-primary);
}
.nav-item--active {
  background: rgba(99,102,241,0.15) !important;
  color: var(--indigo-light) !important;
  font-weight: 600;
}
.nav-item-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.nav-item-icon-wrap {
  width: 32px; height: 32px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  transition: background 0.2s;
}
.nav-item--active .nav-item-icon-wrap {
  background: rgba(99,102,241,0.2);
}
.nav-item-badge {
  font-size: 11px;
  font-weight: 700;
  background: var(--red);
  color: white;
  padding: 2px 7px;
  border-radius: 100px;
  min-width: 22px;
  text-align: center;
}

.sidebar-bottom {
  padding: 16px;
  border-top: 1px solid var(--border);
}
.sidebar-admin-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  background: rgba(99,102,241,0.05);
  margin-bottom: 10px;
}
.admin-avatar {
  font-size: 32px;
  color: var(--indigo-light);
  line-height: 1;
}
.admin-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 3px;
}
.admin-status {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--text-muted);
}
.status-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--green);
  animation: statusPulse 2s ease-in-out infinite;
}
@keyframes statusPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px;
  border-radius: 10px;
  border: none;
  background: rgba(239,68,68,0.05);
  border: 1px solid rgba(239,68,68,0.1);
  color: rgba(239,68,68,0.6);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.logout-btn:hover {
  background: rgba(239,68,68,0.1);
  color: #f87171;
  border-color: rgba(239,68,68,0.25);
}

/* Mobile sidebar handling */
.sidebar-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(4px);
  z-index: 40;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  .sidebar-open {
    transform: translateX(0) !important;
  }
  .sidebar-backdrop { display: block; }
}

/* ══════════════════════════════════════════
   MAIN
══════════════════════════════════════════ */
.admin-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  margin-left: 260px;
}
@media (max-width: 768px) { .admin-main { margin-left: 0; } }

/* ══════════════════════════════════════════
   TOPBAR
══════════════════════════════════════════ */
.topbar {
  height: 70px;
  background: rgba(13,21,38,0.8);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 20;
}
.topbar-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.hamburger {
  display: none;
  font-size: 22px;
  color: var(--text-secondary);
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
}
@media (max-width: 768px) { .hamburger { display: flex; } }
.topbar-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.3px;
  margin: 0;
}
.topbar-sub {
  font-size: 11px;
  color: var(--text-muted);
  margin: 2px 0 0;
}
.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.topbar-pill {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 6px 14px;
  background: rgba(34,197,94,0.08);
  border: 1px solid rgba(34,197,94,0.15);
  border-radius: 100px;
  font-size: 12px;
  font-weight: 600;
  color: #4ade80;
}
.pulse-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--green);
  animation: statusPulse 2s ease-in-out infinite;
}
.topbar-refresh {
  width: 36px; height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: var(--surface-2);
  color: var(--text-secondary);
  font-size: 16px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.topbar-refresh:hover {
  border-color: var(--indigo);
  color: var(--indigo-light);
}
.topbar-refresh.is-refreshing svg { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.topbar-time {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-muted);
  font-variant-numeric: tabular-nums;
}

/* ══════════════════════════════════════════
   CONTENT
══════════════════════════════════════════ */
.content-area {
  flex: 1;
  overflow-y: auto;
  position: relative;
}
.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(5,10,20,0.85);
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 20px;
  z-index: 50;
}
.loading-spinner {
  position: relative;
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.spinner-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top-color: var(--indigo);
  animation: spin 1s linear infinite;
}
.spinner-ring--2 {
  inset: 8px;
  border-top-color: #8b5cf6;
  animation-direction: reverse;
  animation-duration: 0.8s;
}
.spinner-icon {
  font-size: 22px;
  color: var(--indigo-light);
}
.loading-text {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 500;
}

.content-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 28px;
}

/* ══════════════════════════════════════════
   STAT CARDS
══════════════════════════════════════════ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
@media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .stats-grid { grid-template-columns: 1fr; } }

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
  animation: cardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) var(--delay, 0s) both;
}
.stat-card:hover {
  border-color: var(--border-hover);
  transform: translateY(-3px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}
@keyframes cardIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.stat-card-bg {
  position: absolute;
  inset: 0;
  opacity: 0.04;
  transition: opacity 0.3s;
}
.stat-card:hover .stat-card-bg { opacity: 0.08; }

.card-blue .stat-card-bg { background: linear-gradient(135deg, #3b82f6, #6366f1); }
.card-green .stat-card-bg { background: linear-gradient(135deg, #22c55e, #14b8a6); }
.card-orange .stat-card-bg { background: linear-gradient(135deg, #f59e0b, #ef4444); }
.card-purple .stat-card-bg { background: linear-gradient(135deg, #a855f7, #ec4899); }
.card-gray .stat-card-bg { background: linear-gradient(135deg, #64748b, #475569); }

.stat-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  position: relative;
}
.stat-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
}
.card-blue .stat-icon-wrap { background: rgba(59,130,246,0.15); color: #60a5fa; }
.card-green .stat-icon-wrap { background: rgba(34,197,94,0.15); color: #4ade80; }
.card-orange .stat-icon-wrap { background: rgba(245,158,11,0.15); color: #fbbf24; }
.card-purple .stat-icon-wrap { background: rgba(168,85,247,0.15); color: #c084fc; }
.card-gray .stat-icon-wrap { background: rgba(100,116,139,0.15); color: #94a3b8; }

.stat-trend {
  display: flex; align-items: center; gap: 3px;
  font-size: 11px; font-weight: 700;
  color: #4ade80;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.2);
  padding: 3px 8px;
  border-radius: 100px;
}
.stat-alert-badge {
  display: flex; align-items: center; gap: 3px;
  font-size: 11px; font-weight: 700;
  color: #fbbf24;
  background: rgba(245,158,11,0.1);
  border: 1px solid rgba(245,158,11,0.2);
  padding: 3px 8px;
  border-radius: 100px;
  animation: alertPulse 2s ease-in-out infinite;
}
@keyframes alertPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.stat-card-value {
  font-size: 36px;
  font-weight: 900;
  letter-spacing: -1px;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 4px;
  position: relative;
}
.stat-card-label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary);
  position: relative;
  margin-bottom: 16px;
}
.stat-card-bar {
  height: 3px;
  background: rgba(255,255,255,0.05);
  border-radius: 100px;
  overflow: hidden;
  position: relative;
}
.stat-bar-fill {
  height: 100%;
  border-radius: 100px;
  transition: width 1s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.card-blue .stat-bar-fill { background: linear-gradient(90deg, #3b82f6, #6366f1); }
.card-green .stat-bar-fill { background: linear-gradient(90deg, #22c55e, #14b8a6); }
.card-orange .stat-bar-fill { background: linear-gradient(90deg, #f59e0b, #ef4444); }
.card-purple .stat-bar-fill { background: linear-gradient(90deg, #a855f7, #ec4899); }
.card-gray .stat-bar-fill { background: linear-gradient(90deg, #64748b, #475569); }

/* ══════════════════════════════════════════
   DASHBOARD GRID
══════════════════════════════════════════ */
.dash-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}
@media (max-width: 900px) { .dash-grid { grid-template-columns: 1fr; } }

.dash-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
}
.dash-card-head {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
}
.dash-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.title-icon {
  color: var(--indigo-light);
}

.category-list { padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }
.category-item {
  animation: cardIn 0.4s ease var(--delay, 0s) both;
}
.cat-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}
.cat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.cat-name { flex: 1; font-size: 13px; color: var(--text-secondary); }
.cat-count { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.cat-bar-bg {
  height: 4px;
  background: rgba(255,255,255,0.05);
  border-radius: 100px;
  overflow: hidden;
}
.cat-bar-fill {
  height: 100%;
  border-radius: 100px;
  transition: width 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Quick actions card */
.dash-actions-card {
  background: linear-gradient(135deg, #1a1040, #0d1a3a);
  border: 1px solid rgba(99,102,241,0.25);
  border-radius: 20px;
  position: relative;
  overflow: hidden;
}
.dash-actions-glow {
  position: absolute;
  top: -60px; right: -60px;
  width: 240px; height: 240px;
  background: radial-gradient(circle, rgba(99,102,241,0.2), transparent 70%);
  pointer-events: none;
}
.dash-actions-content {
  position: relative;
  padding: 24px;
}
.dash-actions-hero {
  font-size: 32px;
  color: #fbbf24;
  margin-bottom: 12px;
  filter: drop-shadow(0 0 12px rgba(251,191,36,0.5));
}
.dash-actions-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 6px;
}
.dash-actions-sub {
  font-size: 13px;
  color: rgba(148,163,184,0.5);
  margin: 0 0 20px;
}

.quick-actions { display: flex; flex-direction: column; gap: 10px; }
.quick-action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.07);
  background: rgba(255,255,255,0.04);
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.quick-action:hover {
  background: rgba(255,255,255,0.08);
  border-color: rgba(99,102,241,0.3);
}
.qa-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}
.qa-icon {
  width: 36px; height: 36px;
  border-radius: 10px;
  background: rgba(99,102,241,0.2);
  border: 1px solid rgba(99,102,241,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  color: var(--indigo-light);
  flex-shrink: 0;
}
.qa-icon--red {
  background: rgba(239,68,68,0.15);
  border-color: rgba(239,68,68,0.25);
  color: #f87171;
}
.qa-icon--green {
  background: rgba(34,197,94,0.15);
  border-color: rgba(34,197,94,0.25);
  color: #4ade80;
}
.qa-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.qa-desc { font-size: 11px; color: var(--text-muted); margin: 0; }
.qa-badge {
  font-size: 11px; font-weight: 700;
  background: var(--indigo);
  color: white;
  padding: 3px 8px;
  border-radius: 100px;
}
.qa-badge--red { background: var(--red); }
.qa-arrow {
  font-size: 14px;
  color: var(--text-muted);
  transition: transform 0.2s;
}
.quick-action:hover .qa-arrow { transform: translateX(4px); }

/* Activity */
.activity-list { padding: 16px 24px; display: flex; flex-direction: column; gap: 2px; }
.activity-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.activity-dot.blue { background: #3b82f6; box-shadow: 0 0 8px rgba(59,130,246,0.5); }
.activity-dot.orange { background: #f59e0b; box-shadow: 0 0 8px rgba(245,158,11,0.5); }
.activity-dot.red { background: #ef4444; box-shadow: 0 0 8px rgba(239,68,68,0.5); }
.activity-content { flex: 1; }
.activity-text { font-size: 13px; color: var(--text-secondary); margin: 0; }
.activity-time { font-size: 11px; color: var(--text-muted); white-space: nowrap; }
.activity-action {
  font-size: 12px;
  font-weight: 700;
  color: var(--indigo-light);
  background: rgba(99,102,241,0.1);
  border: 1px solid rgba(99,102,241,0.2);
  padding: 4px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.activity-action:hover { background: rgba(99,102,241,0.2); }
.activity-action--red {
  color: #f87171;
  background: rgba(239,68,68,0.08);
  border-color: rgba(239,68,68,0.2);
}
.activity-action--red:hover { background: rgba(239,68,68,0.15); }

/* ══════════════════════════════════════════
   SECTION HEADERS
══════════════════════════════════════════ */
.section-header {
  margin-bottom: 20px;
}
.section-title {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: -0.5px;
  margin: 0 0 4px;
}
.section-sub {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0;
}

/* ══════════════════════════════════════════
   FILTERS BAR
══════════════════════════════════════════ */
.filters-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.search-wrap {
  flex: 1;
  min-width: 200px;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 14px;
  color: var(--text-muted);
  font-size: 16px;
  pointer-events: none;
}
.search-input {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 11px 40px;
  font-size: 14px;
  color: var(--text-primary);
  outline: none;
  transition: all 0.2s;
  font-family: inherit;
}
.search-input::placeholder { color: var(--text-muted); }
.search-input:focus {
  border-color: rgba(99,102,241,0.4);
  background: rgba(99,102,241,0.05);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
}
.search-clear {
  position: absolute;
  right: 12px;
  color: var(--text-muted);
  font-size: 18px;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
}
.search-clear:hover { color: var(--text-secondary); }
.filter-tabs {
  display: flex;
  gap: 6px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 5px;
}
.filter-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  border: none;
  background: none;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.filter-tab:hover { color: var(--text-primary); }
.filter-tab--active {
  background: rgba(99,102,241,0.15) !important;
  color: var(--indigo-light) !important;
  font-weight: 700;
}
.filter-count {
  font-size: 11px;
  font-weight: 700;
  background: rgba(255,255,255,0.08);
  padding: 1px 6px;
  border-radius: 100px;
}
.filter-tab--active .filter-count {
  background: rgba(99,102,241,0.25);
  color: var(--indigo-light);
}

/* ══════════════════════════════════════════
   TABLE
══════════════════════════════════════════ */
.table-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  overflow: hidden;
}
.table-head-bar {
  padding: 18px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 10px;
}
.table-head-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.data-table thead tr { background: rgba(99,102,241,0.04); }
.data-table th {
  padding: 12px 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-muted);
  text-align: left;
  border-bottom: 1px solid var(--border);
}
.th-right { text-align: right; }
.table-row { border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.15s; }
.table-row:last-child { border-bottom: none; }
.table-row:hover { background: rgba(99,102,241,0.04); }
.table-row--resolved { opacity: 0.5; }
.data-table td { padding: 14px 20px; font-size: 13px; }

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-avatar {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  font-weight: 800;
  color: white;
  flex-shrink: 0;
}
.user-name { font-size: 14px; font-weight: 600; color: var(--text-primary); margin: 0 0 2px; }
.user-name--restricted { text-decoration: line-through; color: var(--text-muted); }
.user-id { font-size: 11px; color: var(--text-muted); margin: 0; }

.td-email { color: var(--text-secondary); font-size: 13px; }
.td-date { color: var(--text-muted); font-size: 12px; white-space: nowrap; }
.td-bold { font-weight: 600; color: var(--text-primary); }
.td-muted { color: var(--text-muted); }
.td-truncate { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-secondary); }
.td-actions {
  display: flex;
  justify-content: flex-end;
  gap: 6px;
}

.action-btn {
  width: 34px; height: 34px;
  border-radius: 9px;
  border: none;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-lock {
  background: rgba(245,158,11,0.1);
  color: #fbbf24;
  border: 1px solid rgba(245,158,11,0.15);
}
.btn-lock:hover { background: rgba(245,158,11,0.2); }
.btn-unlock {
  background: rgba(34,197,94,0.1);
  color: #4ade80;
  border: 1px solid rgba(34,197,94,0.15);
}
.btn-unlock:hover { background: rgba(34,197,94,0.2); }
.btn-delete {
  background: rgba(239,68,68,0.08);
  color: #f87171;
  border: 1px solid rgba(239,68,68,0.12);
}
.btn-delete:hover { background: rgba(239,68,68,0.18); }

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 100px;
  font-size: 11px;
  font-weight: 700;
}
.chip-active {
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.2);
  color: #4ade80;
}
.chip-restricted {
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  color: #f87171;
}
.chip-read {
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.2);
  color: #4ade80;
}
.chip-unread {
  background: rgba(100,116,139,0.1);
  border: 1px solid rgba(100,116,139,0.2);
  color: #94a3b8;
}
.cat-tag {
  display: inline-flex;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  background: rgba(99,102,241,0.1);
  border: 1px solid rgba(99,102,241,0.2);
  color: var(--indigo-light);
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 16px;
  border-top: 1px solid var(--border);
}
.page-btn {
  min-width: 36px; height: 36px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: none;
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
  font-family: inherit;
}
.page-btn:hover:not(:disabled) {
  border-color: var(--indigo);
  color: var(--indigo-light);
  background: rgba(99,102,241,0.1);
}
.page-btn--active {
  background: var(--indigo) !important;
  border-color: var(--indigo) !important;
  color: white !important;
}
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

/* ══════════════════════════════════════════
   PLACES
══════════════════════════════════════════ */
.pending-alert {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 18px 20px;
  background: rgba(245,158,11,0.06);
  border: 1px solid rgba(245,158,11,0.2);
  border-radius: 16px;
  margin-bottom: 20px;
}
.pending-alert-icon {
  font-size: 24px;
  color: #fbbf24;
  flex-shrink: 0;
  line-height: 1;
  margin-top: 2px;
}
.pending-alert-title { font-size: 14px; font-weight: 700; color: #fbbf24; margin: 0 0 3px; }
.pending-alert-sub { font-size: 12px; color: rgba(245,158,11,0.5); margin: 0; }

.places-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 8px;
}
.place-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 20px;
  position: relative;
  transition: all 0.2s;
}
.place-card:hover {
  border-color: rgba(245,158,11,0.3);
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}
.place-card--pending { border-color: rgba(245,158,11,0.2); }
.place-card-badge {
  position: absolute;
  top: 14px; right: 14px;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: rgba(245,158,11,0.15);
  border: 1px solid rgba(245,158,11,0.25);
  color: #fbbf24;
  padding: 3px 8px;
  border-radius: 6px;
}
.place-card-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 8px;
  padding-right: 70px;
}
.place-card-meta {
  display: flex;
  gap: 12px;
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 10px;
}
.place-card-meta span { display: flex; align-items: center; gap: 4px; }
.place-card-desc {
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 38px;
}
.place-card-coords {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--text-muted);
  background: rgba(99,102,241,0.05);
  border: 1px solid rgba(99,102,241,0.1);
  padding: 6px 10px;
  border-radius: 8px;
  margin-bottom: 14px;
}
.place-card-actions { display: flex; gap: 8px; }
.place-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px;
  border-radius: 10px;
  border: none;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.place-btn--approve {
  background: rgba(34,197,94,0.15);
  border: 1px solid rgba(34,197,94,0.25);
  color: #4ade80;
}
.place-btn--approve:hover { background: rgba(34,197,94,0.25); }
.place-btn--reject {
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  color: #f87171;
}
.place-btn--reject:hover { background: rgba(239,68,68,0.2); }

/* ══════════════════════════════════════════
   REPORTS
══════════════════════════════════════════ */
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
  margin-bottom: 8px;
}
.report-card {
  background: var(--surface);
  border: 1px solid rgba(239,68,68,0.15);
  border-radius: 18px;
  padding: 20px;
  transition: all 0.2s;
}
.report-card:hover {
  border-color: rgba(239,68,68,0.3);
  box-shadow: 0 8px 24px rgba(239,68,68,0.05);
}
.report-card-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.report-card-flag {
  width: 36px; height: 36px;
  border-radius: 10px;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 17px;
  color: #f87171;
  flex-shrink: 0;
}
.report-card-date { font-size: 12px; color: var(--text-muted); }
.report-pending-badge {
  margin-left: auto;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  background: rgba(245,158,11,0.12);
  border: 1px solid rgba(245,158,11,0.2);
  color: #fbbf24;
  padding: 3px 8px;
  border-radius: 6px;
}
.report-users {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.report-user { flex: 1; }
.report-user-label {
  display: block;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted);
  font-weight: 700;
  margin-bottom: 3px;
}
.report-user-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
}
.report-user-name--red { color: #f87171; }
.report-arrow { color: var(--text-muted); font-size: 12px; flex-shrink: 0; }
.report-reason {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
  background: rgba(239,68,68,0.04);
  border: 1px solid rgba(239,68,68,0.08);
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 14px;
  line-height: 1.4;
}
.report-actions { display: flex; gap: 8px; }
.report-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px;
  border-radius: 10px;
  border: none;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.report-btn--resolve {
  background: rgba(34,197,94,0.12);
  border: 1px solid rgba(34,197,94,0.2);
  color: #4ade80;
}
.report-btn--resolve:hover { background: rgba(34,197,94,0.22); }
.report-btn--restrict {
  background: rgba(245,158,11,0.1);
  border: 1px solid rgba(245,158,11,0.2);
  color: #fbbf24;
}
.report-btn--restrict:hover { background: rgba(245,158,11,0.2); }

/* ══════════════════════════════════════════
   MODAL
══════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
  padding: 20px;
}
.modal-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px;
  padding: 32px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 40px 80px rgba(0,0,0,0.5);
  text-align: center;
}
.modal-icon-wrap {
  width: 64px; height: 64px;
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  font-size: 30px;
  margin: 0 auto 20px;
}
.modal-icon-wrap.danger {
  background: rgba(239,68,68,0.1);
  border: 2px solid rgba(239,68,68,0.2);
  color: #f87171;
}
.modal-icon-wrap.warning {
  background: rgba(245,158,11,0.1);
  border: 2px solid rgba(245,158,11,0.2);
  color: #fbbf24;
}
.modal-title {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 10px;
}
.modal-body {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0 0 24px;
}
.modal-actions {
  display: flex;
  gap: 10px;
}
.modal-btn {
  flex: 1;
  padding: 12px;
  border-radius: 12px;
  border: none;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}
.modal-btn--cancel {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--text-secondary);
}
.modal-btn--cancel:hover { background: rgba(255,255,255,0.09); }
.modal-btn--danger {
  background: rgba(239,68,68,0.15);
  border: 1px solid rgba(239,68,68,0.25);
  color: #f87171;
}
.modal-btn--danger:hover { background: rgba(239,68,68,0.25); }
.modal-btn--warning {
  background: rgba(245,158,11,0.12);
  border: 1px solid rgba(245,158,11,0.22);
  color: #fbbf24;
}
.modal-btn--warning:hover { background: rgba(245,158,11,0.22); }

/* ══════════════════════════════════════════
   TOAST
══════════════════════════════════════════ */
.toast {
  position: fixed;
  bottom: 28px; right: 28px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  z-index: 300;
  box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}
.toast--success {
  background: rgba(34,197,94,0.15);
  border: 1px solid rgba(34,197,94,0.25);
  color: #4ade80;
}
.toast--error {
  background: rgba(239,68,68,0.15);
  border: 1px solid rgba(239,68,68,0.25);
  color: #f87171;
}

/* ══════════════════════════════════════════
   EMPTY STATES
══════════════════════════════════════════ */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.empty-icon { font-size: 40px; color: var(--text-muted); opacity: 0.5; }
.empty-state p { font-size: 14px; color: var(--text-muted); margin: 0; }
.empty-reset {
  font-size: 12px;
  font-weight: 600;
  color: var(--indigo-light);
  background: rgba(99,102,241,0.1);
  border: none;
  padding: 6px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}
.empty-reset:hover { background: rgba(99,102,241,0.2); }
.empty-state-small {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-muted);
  padding: 12px 0;
}

/* ══════════════════════════════════════════
   TRANSITIONS
══════════════════════════════════════════ */
.tab-switch-enter-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.tab-switch-leave-active { transition: all 0.15s ease; }
.tab-switch-enter-from { opacity: 0; transform: translateY(12px); }
.tab-switch-leave-to { opacity: 0; transform: translateY(-8px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.sidebar-slide-enter-active, .sidebar-slide-leave-active { transition: transform 0.3s ease; }
.sidebar-slide-enter-from, .sidebar-slide-leave-to { transform: translateX(-100%); }

.modal-enter-active {
  transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-card, .modal-leave-to .modal-card { transform: scale(0.9) translateY(20px); }

.toast-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateX(40px); }
.toast-leave-to { opacity: 0; transform: translateX(40px); }
</style>
