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
            <Icon icon="ph:shield-check-fill" />
          </div>
          <div>
            <span class="sidebar-logo-text">U-Map<span class="text-indigo">.</span></span>
            <p class="sidebar-logo-sub">Administration & Modération</p>
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
            <div class="nav-item-badge" v-if="item.badge && item.badge() > 0" :class="item.badgeClass || ''">
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
                <span>En ligne · Prod</span>
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
          <button class="hamburger" @click="mobileMenuOpen = !mobileMenuOpen">
            <Icon icon="ph:list-bold" />
          </button>
          <div>
            <h1 class="topbar-title">{{ tabTitles[currentTab] }}</h1>
            <p class="topbar-sub">U-Map · Centre de Modération et Gestion</p>
          </div>
        </div>
        <div class="topbar-right">
          <div class="topbar-pill">
            <span class="pulse-dot"></span>
            <span>Système opérationnel</span>
          </div>
          <button class="topbar-refresh" @click="loadData" :class="{ 'is-refreshing': loading }" title="Actualiser">
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
              <Icon icon="ph:shield-fill" class="spinner-icon" />
            </div>
            <p class="loading-text">Synchronisation des données...</p>
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
                      <button @click="setTab('reports')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon qa-icon--red">
                            <Icon icon="ph:flag-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Signalements en attente</p>
                            <p class="qa-desc">{{ pendingReportsCount }} cas à modérer</p>
                          </div>
                        </div>
                        <span class="qa-badge qa-badge--red" v-if="pendingReportsCount > 0">{{ pendingReportsCount }}</span>
                        <Icon icon="ph:arrow-right-bold" class="qa-arrow" />
                      </button>
                      <button @click="setTab('places')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon">
                            <Icon icon="ph:map-pin-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Lieux en attente</p>
                            <p class="qa-desc">{{ pendingPlacesList.length }} lieu(x) à valider</p>
                          </div>
                        </div>
                        <span class="qa-badge" v-if="pendingPlacesList.length > 0">{{ pendingPlacesList.length }}</span>
                        <Icon icon="ph:arrow-right-bold" class="qa-arrow" />
                      </button>
                      <button @click="setTab('audit')" class="quick-action">
                        <div class="qa-left">
                          <div class="qa-icon qa-icon--purple">
                            <Icon icon="ph:clock-counter-clockwise-fill" />
                          </div>
                          <div>
                            <p class="qa-label">Journal d'Audit</p>
                            <p class="qa-desc">Historique des sanctions & actions</p>
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
                  <div class="activity-item" v-if="pendingReportsCount > 0">
                    <div class="activity-dot red"></div>
                    <div class="activity-content">
                      <p class="activity-text">
                        <strong>{{ pendingReportsCount }}</strong> signalement(s) urgent(s) en attente de décision
                      </p>
                    </div>
                    <button @click="setTab('reports')" class="activity-action activity-action--red">Traiter →</button>
                  </div>
                  <div class="activity-item" v-if="pendingPlacesList.length > 0">
                    <div class="activity-dot orange"></div>
                    <div class="activity-content">
                      <p class="activity-text">
                        <strong>{{ pendingPlacesList.length }}</strong> lieu(x) soumis par la communauté
                      </p>
                    </div>
                    <button @click="setTab('places')" class="activity-action">Voir →</button>
                  </div>
                  <div v-if="!stats?.recentUsers && !pendingPlacesList.length && !pendingReportsCount" class="empty-state-small">
                    <Icon icon="ph:check-circle-fill" />
                    Toutes les files de modération sont à jour !
                  </div>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: ANALYTICS (PHASE 4)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'analytics'" key="analytics">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Statistiques & Analytics Avancés</h2>
                  <p class="section-sub">Volumétrie, activité temporelle, KPIs de modération et cartographie</p>
                </div>
                <!-- Period Switcher -->
                <div class="period-switcher">
                  <button
                    v-for="p in [ { id: '7d', label: '7 jours' }, { id: '30d', label: '30 jours' }, { id: '90d', label: '90 jours' } ]"
                    :key="p.id"
                    @click="changeAnalyticsPeriod(p.id)"
                    class="period-btn"
                    :class="{ 'period-btn--active': analyticsPeriod === p.id }"
                  >
                    {{ p.label }}
                  </button>
                  <button @click="loadAnalytics(analyticsPeriod)" class="topbar-refresh" :class="{ 'is-refreshing': analyticsLoading }" title="Actualiser les analytics">
                    <Icon icon="ph:arrows-clockwise-bold" />
                  </button>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="analyticsLoading" class="context-loading">
                <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
                <span>Calcul des indicateurs analytiques...</span>
              </div>

              <div v-else-if="analyticsData" class="analytics-content">
                <!-- 4 Top KPIs -->
                <div class="analytics-kpi-grid">
                  <div class="kpi-card kpi-card--green">
                    <div class="kpi-card-head">
                      <span class="kpi-title">Taux de Résolution</span>
                      <Icon icon="ph:shield-check-fill" class="kpi-icon" />
                    </div>
                    <div class="kpi-val">{{ analyticsData.kpis?.resolution_rate }}%</div>
                    <p class="kpi-sub">{{ analyticsData.kpis?.resolved_reports }} / {{ analyticsData.kpis?.total_reports }} signalements traités</p>
                  </div>

                  <div class="kpi-card kpi-card--blue">
                    <div class="kpi-card-head">
                      <span class="kpi-title">Délai Moyen Modération</span>
                      <Icon icon="ph:clock-countdown-fill" class="kpi-icon" />
                    </div>
                    <div class="kpi-val">{{ formatDuration(analyticsData.kpis?.avg_resolution_minutes) }}</div>
                    <p class="kpi-sub">Temps de prise de décision par cas</p>
                  </div>

                  <div class="kpi-card kpi-card--purple">
                    <div class="kpi-card-head">
                      <span class="kpi-title">Nouveaux Étudiants</span>
                      <Icon icon="ph:user-plus-fill" class="kpi-icon" />
                    </div>
                    <div class="kpi-val">+{{ analyticsData.kpis?.recent_users }}</div>
                    <p class="kpi-sub">{{ analyticsData.kpis?.total_users }} comptes enregistrés au total</p>
                  </div>

                  <div class="kpi-card kpi-card--orange">
                    <div class="kpi-card-head">
                      <span class="kpi-title">Volume de Messages</span>
                      <Icon icon="ph:chat-teardrop-text-fill" class="kpi-icon" />
                    </div>
                    <div class="kpi-val">{{ analyticsData.kpis?.recent_messages }}</div>
                    <p class="kpi-sub">{{ analyticsData.kpis?.total_messages }} messages cumulés sur l'app</p>
                  </div>
                </div>

                <!-- Main Charts Row -->
                <div class="analytics-charts-grid">
                  <!-- Chart 1: Activity Timeline -->
                  <div class="analytics-chart-card">
                    <div class="chart-head">
                      <div>
                        <h3 class="chart-title"><Icon icon="ph:trend-up-bold" /> Dynamique des Inscriptions & Activité</h3>
                        <p class="chart-sub">Volume journalier d'inscriptions et de messages</p>
                      </div>
                      <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot dot-users"></span> Inscriptions</span>
                        <span class="legend-item"><span class="legend-dot dot-messages"></span> Messages</span>
                      </div>
                    </div>
                    <!-- Histogram Chart -->
                    <div class="chart-bars-container" v-if="combinedTimeline.length > 0">
                      <div v-for="(item, idx) in combinedTimeline" :key="idx" class="chart-bar-group" :title="item.date + ' : ' + item.users + ' inscriptions, ' + item.messages + ' messages'">
                        <div class="bar-pair">
                          <div class="bar-col bar-users" :style="{ height: getBarHeight(item.users, maxTimelineVal) + '%' }">
                            <span v-if="item.users > 0" class="bar-tooltip">{{ item.users }}</span>
                          </div>
                          <div class="bar-col bar-messages" :style="{ height: getBarHeight(item.messages, maxTimelineVal) + '%' }">
                            <span v-if="item.messages > 0" class="bar-tooltip">{{ item.messages }}</span>
                          </div>
                        </div>
                        <span class="bar-label">{{ formatChartDate(item.date) }}</span>
                      </div>
                    </div>
                    <div v-else class="empty-state-small">
                      <Icon icon="ph:chart-bar" />
                      Pas encore assez de données d'activité pour cette période.
                    </div>
                  </div>

                  <!-- Chart 2: Moderation Distribution Breakdown -->
                  <div class="analytics-chart-card">
                    <div class="chart-head">
                      <div>
                        <h3 class="chart-title"><Icon icon="ph:flag-bold" /> Répartition des Signalements & Sanctions</h3>
                        <p class="chart-sub">Types d'infractions et priorités</p>
                      </div>
                    </div>

                    <div class="breakdown-sections">
                      <!-- Priorités -->
                      <div class="breakdown-group">
                        <h4 class="breakdown-title">Par Priorité</h4>
                        <div class="breakdown-bars">
                          <div v-for="p in analyticsData.reports_by_priority" :key="p.priority" class="breakdown-row">
                            <span class="breakdown-name">{{ formatPriority(p.priority) }}</span>
                            <div class="breakdown-track">
                              <div class="breakdown-fill" :class="'fill-priority-' + p.priority" :style="{ width: getPercentage(p.count, analyticsData.kpis?.total_reports) + '%' }"></div>
                            </div>
                            <span class="breakdown-count">{{ p.count }}</span>
                          </div>
                        </div>
                      </div>

                      <!-- Sanctions Types -->
                      <div class="breakdown-group">
                        <h4 class="breakdown-title">Sanctions Appliquées</h4>
                        <div class="breakdown-bars">
                          <div v-for="s in analyticsData.sanctions_by_type" :key="s.type" class="breakdown-row">
                            <span class="breakdown-name">{{ formatSanctionType(s.type) }}</span>
                            <div class="breakdown-track">
                              <div class="breakdown-fill fill-sanction" :style="{ width: getPercentage(s.count, totalSanctionsCount) + '%' }"></div>
                            </div>
                            <span class="breakdown-count">{{ s.count }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Secondary Charts Row: Demographics & Campus Places -->
                <div class="analytics-bottom-grid">
                  <!-- Faculty distribution -->
                  <div class="analytics-chart-card">
                    <div class="chart-head">
                      <div>
                        <h3 class="chart-title"><Icon icon="ph:buildings-bold" /> Répartition par Faculté / Filière</h3>
                        <p class="chart-sub">Comptes étudiants par département</p>
                      </div>
                    </div>
                    <div class="faculty-distribution-list">
                      <div v-for="(fac, i) in (analyticsData.users_by_faculty || [])" :key="i" class="fac-dist-item">
                        <div class="fac-dist-left">
                          <span class="fac-dist-rank">{{ i + 1 }}</span>
                          <span class="fac-dist-name">{{ fac.faculty }}</span>
                        </div>
                        <div class="fac-dist-bar-wrap">
                          <div class="fac-dist-bar" :style="{ width: getPercentage(fac.count, analyticsData.kpis?.total_users) + '%', background: catColors[i % catColors.length] }"></div>
                        </div>
                        <span class="fac-dist-val">{{ fac.count }}</span>
                      </div>
                      <div v-if="!analyticsData.users_by_faculty?.length" class="empty-state-small">
                        <Icon icon="ph:student" />
                        Aucune donnée de filière renseignée.
                      </div>
                    </div>
                  </div>

                  <!-- Roles & Places Distribution -->
                  <div class="analytics-chart-card">
                    <div class="chart-head">
                      <div>
                        <h3 class="chart-title"><Icon icon="ph:map-pin-bold" /> Cartographie & Lieux</h3>
                        <p class="chart-sub">Statut d'approbation des lieux du campus</p>
                      </div>
                    </div>
                    <div class="places-status-pills">
                      <div class="status-stat-box box-approved">
                        <Icon icon="ph:check-circle-fill" />
                        <div>
                          <div class="stat-box-num">{{ placesStatusCount('approved') }}</div>
                          <div class="stat-box-lbl">Lieux Approuvés</div>
                        </div>
                      </div>
                      <div class="status-stat-box box-pending">
                        <Icon icon="ph:clock-countdown-fill" />
                        <div>
                          <div class="stat-box-num">{{ placesStatusCount('pending') }}</div>
                          <div class="stat-box-lbl">En Attente</div>
                        </div>
                      </div>
                      <div class="status-stat-box box-hidden">
                        <Icon icon="ph:eye-slash-fill" />
                        <div>
                          <div class="stat-box-num">{{ placesStatusCount('hidden') }}</div>
                          <div class="stat-box-lbl">Masqués</div>
                        </div>
                      </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="category-list" style="margin-top: 14px;">
                      <div v-for="(item, i) in (analyticsData.places_by_category || [])" :key="i" class="category-item">
                        <div class="cat-info">
                          <span class="cat-dot" :style="{ background: catColors[i % catColors.length] }"></span>
                          <span class="cat-name">{{ item.category }}</span>
                          <span class="cat-count">{{ item.count }}</span>
                        </div>
                        <div class="cat-bar-bg">
                          <div class="cat-bar-fill" :style="{ width: getPercentage(item.count, analyticsData.kpis?.total_places) + '%', background: catColors[i % catColors.length] }"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: REPORTS (MODÉRATION ENRICHIE)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'reports'" key="reports">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Modération des Signalements</h2>
                  <p class="section-sub">{{ filteredReports.length }} signalement(s) correspondant aux filtres</p>
                </div>
              </div>

              <!-- Filter toolbar -->
              <div class="moderation-filters">
                <!-- Status tabs -->
                <div class="filter-group">
                  <span class="filter-label">Statut :</span>
                  <div class="filter-tabs">
                    <button
                      v-for="st in reportStatusTabs" :key="st.id"
                      @click="reportStatusFilter = st.id"
                      class="filter-tab"
                      :class="{ 'filter-tab--active': reportStatusFilter === st.id }"
                    >
                      <Icon :icon="st.icon" />
                      {{ st.label }}
                      <span class="filter-count" :class="st.badgeClass">{{ st.count() }}</span>
                    </button>
                  </div>
                </div>

                <!-- Priority & Type selects -->
                <div class="filter-selects">
                  <div class="select-wrap">
                    <Icon icon="ph:warning-circle-bold" class="select-icon" />
                    <select v-model="reportPriorityFilter" class="custom-select">
                      <option value="all">Toutes priorités</option>
                      <option value="urgent">🔴 Urgent</option>
                      <option value="high">🟠 Haute</option>
                      <option value="medium">🔵 Moyenne</option>
                      <option value="low">⚪ Basse</option>
                    </select>
                  </div>

                  <div class="select-wrap">
                    <Icon icon="ph:tag-bold" class="select-icon" />
                    <select v-model="reportTypeFilter" class="custom-select">
                      <option value="all">Tous types</option>
                      <option value="message">💬 Message</option>
                      <option value="user">👤 Utilisateur</option>
                      <option value="place">📍 Lieu</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Reports list / cards -->
              <div v-if="filteredReports.length > 0" class="reports-grid">
                <div
                  v-for="report in filteredReports" :key="report.id"
                  class="report-card"
                  :class="['report-priority--' + (report.priority || 'medium'), 'report-status--' + (report.status || 'pending')]"
                >
                  <div class="report-card-top">
                    <div class="report-badges">
                      <!-- Priority chip -->
                      <span class="priority-chip" :class="'priority-' + (report.priority || 'medium')">
                        <Icon icon="ph:warning-fill" />
                        {{ formatPriority(report.priority) }}
                      </span>
                      <!-- Type chip -->
                      <span class="type-chip">
                        <Icon :icon="getReportTypeIcon(report.reportable_type)" />
                        {{ formatReportType(report.reportable_type) }}
                      </span>
                      <!-- Status chip -->
                      <span class="status-chip" :class="'chip-report-' + (report.status || 'pending')">
                        {{ formatReportStatus(report.status) }}
                      </span>
                    </div>
                    <span class="report-card-date">{{ formatDateTime(report.created_at) }}</span>
                  </div>

                  <!-- Users involved -->
                  <div class="report-users">
                    <div class="report-user">
                      <span class="report-user-label">Plaignant</span>
                      <span class="report-user-name">{{ report.reporter?.name || 'Utilisateur anonyme' }}</span>
                      <span class="report-user-email">{{ report.reporter?.email }}</span>
                    </div>
                    <Icon icon="ph:arrow-right-bold" class="report-arrow" />
                    <div class="report-user report-user--target">
                      <span class="report-user-label">Signalé</span>
                      <div class="flex-items-center gap-1">
                        <span class="report-user-name report-user-name--red">{{ report.reported_user?.name || 'Inconnu' }}</span>
                        <!-- Status tags for target user -->
                        <span v-if="report.reported_user?.is_banned" class="mini-tag tag-banned">Banni</span>
                        <span v-else-if="report.reported_user?.suspended_until" class="mini-tag tag-suspended">Suspendu</span>
                        <span v-else-if="report.reported_user?.muted_until" class="mini-tag tag-muted">Muet</span>
                      </div>
                      <span class="report-user-email">{{ report.reported_user?.email }}</span>
                    </div>
                  </div>

                  <!-- Reason & Notes -->
                  <div class="report-reason">
                    <Icon icon="ph:quotes-bold" class="quote-icon" />
                    <p class="reason-text"><strong>Motif :</strong> {{ report.reason }}</p>
                  </div>

                  <div v-if="report.admin_notes" class="report-admin-notes">
                    <Icon icon="ph:notepad-fill" />
                    <span><strong>Note admin :</strong> {{ report.admin_notes }}</span>
                  </div>

                  <!-- Action Buttons Bar -->
                  <div class="report-actions-bar">
                    <button
                      @click="openContextModal(report)"
                      class="rep-action-btn rep-btn--context"
                      title="Lire les messages avant et après pour juger en contexte"
                    >
                      <Icon icon="ph:chats-circle-bold" />
                      Voir contexte
                    </button>

                    <button
                      @click="openSanctionModal(report)"
                      class="rep-action-btn rep-btn--sanction"
                    >
                      <Icon icon="ph:gavel-bold" />
                      Sanctionner
                    </button>

                    <button
                      @click="openUserProfileModal(report.reported_user_id)"
                      class="rep-action-btn rep-btn--profile"
                      title="Historique des sanctions et note interne"
                    >
                      <Icon icon="ph:user-gear-bold" />
                      Fiche
                    </button>

                    <div class="report-status-dropdown">
                      <select
                        :value="report.status"
                        @change="handleQuickStatusChange(report, $event.target.value)"
                        class="status-quick-select"
                      >
                        <option value="pending">En attente</option>
                        <option value="in_progress">En cours</option>
                        <option value="resolved">Résolu</option>
                        <option value="dismissed">Rejeté / Sans suite</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div v-else class="empty-state">
                <Icon icon="ph:shield-check-fill" class="empty-icon" style="color: var(--green);" />
                <p>Aucun signalement dans cette vue</p>
                <button @click="resetReportFilters" class="empty-reset">
                  Réinitialiser les filtres
                </button>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: USERS (UTILISATEURS & MODÉRATION)
            ════════════════════════════════ -->
            <!-- ════════════════════════════════
                 TAB: USERS (PHASE 3 ENRICHED)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'users'" key="users">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Comptes Utilisateurs & Gestion des Rôles</h2>
                  <p class="section-sub">{{ filteredUsers.length }} utilisateur(s) affiché(s) sur {{ users.length }} inscrits · Fiches détaillées, attributions et sécurité</p>
                </div>
              </div>

              <!-- Filters bar -->
              <div class="filters-bar">
                <div class="search-wrap">
                  <Icon icon="ph:magnifying-glass-bold" class="search-icon" />
                  <input
                    v-model="userSearch"
                    placeholder="Rechercher par nom, email, filière ou matricule..."
                    class="search-input"
                  />
                  <button v-if="userSearch" @click="userSearch = ''" class="search-clear">
                    <Icon icon="ph:x-circle-fill" />
                  </button>
                </div>

                <div class="filter-select-wrap">
                  <select v-model="userRoleFilter" class="custom-select">
                    <option value="all">Tous les rôles</option>
                    <option value="user">Étudiants (Utilisateurs)</option>
                    <option value="moderator">Modérateurs</option>
                    <option value="admin">Administrateurs</option>
                    <option value="super_admin">Super Admins</option>
                  </select>
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
                      <th>Rôle & Droits</th>
                      <th>Faculté / Filière</th>
                      <th>Statut Modération</th>
                      <th>Signalements</th>
                      <th>Inscription</th>
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
                            <p class="user-name" :class="{ 'user-name--banned': user.is_banned, 'user-name--restricted': user.is_restricted }">
                              {{ user.name }}
                            </p>
                            <p class="user-id">#{{ user.id }} <span v-if="user.student_id" class="student-id-badge">· {{ user.student_id }}</span></p>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="role-chip" :class="'role-chip--' + (user.role || 'user')">
                          <Icon :icon="getRoleIcon(user.role)" />
                          {{ formatRole(user.role) }}
                        </span>
                      </td>
                      <td>
                        <div class="faculty-cell" v-if="user.faculty || user.study_level">
                          <p class="faculty-name">{{ user.faculty || 'Non renseignée' }}</p>
                          <span v-if="user.study_level" class="study-level-badge">{{ user.study_level }}</span>
                        </div>
                        <span v-else class="td-muted">—</span>
                      </td>
                      <td>
                        <span v-if="user.is_banned" class="status-chip chip-banned">
                          <Icon icon="ph:prohibit-bold" />
                          Banni
                        </span>
                        <span v-else-if="user.suspended_until && new Date(user.suspended_until) > new Date()" class="status-chip chip-suspended">
                          <Icon icon="ph:pause-circle-bold" />
                          Suspendu
                        </span>
                        <span v-else-if="user.muted_until && new Date(user.muted_until) > new Date()" class="status-chip chip-muted">
                          <Icon icon="ph:speaker-simple-slash-bold" />
                          Muet
                        </span>
                        <span v-else-if="user.is_restricted" class="status-chip chip-restricted">
                          <Icon icon="ph:lock-fill" />
                          Restreint
                        </span>
                        <span v-else class="status-chip chip-active">
                          <Icon icon="ph:check-circle-fill" />
                          Actif
                        </span>
                      </td>
                      <td>
                        <span class="reports-count-badge" :class="{ 'has-reports': (user.reports_received_count || 0) > 0 }">
                          {{ user.reports_received_count || 0 }} reçu(s)
                        </span>
                      </td>
                      <td class="td-date">{{ formatDate(user.created_at) }}</td>
                      <td class="td-actions">
                        <!-- Dossier Complet -->
                        <button
                          @click="openUserProfileModal(user.id)"
                          class="action-btn btn-profile"
                          title="Dossier utilisateur & Gestion des rôles"
                        >
                          <Icon icon="ph:user-gear-fill" />
                        </button>
                        <!-- Sanctionner -->
                        <button
                          @click="openDirectSanctionModal(user)"
                          class="action-btn btn-gavel"
                          title="Sanctionner l'utilisateur"
                        >
                          <Icon icon="ph:gavel-fill" />
                        </button>
                        <!-- Reset Password -->
                        <button
                          @click="openResetPasswordModal(user)"
                          class="action-btn btn-key"
                          title="Réinitialiser le mot de passe"
                        >
                          <Icon icon="ph:key-bold" />
                        </button>
                        <!-- Force Logout -->
                        <button
                          @click="confirmForceLogout(user)"
                          class="action-btn btn-logout-force"
                          title="Déconnexion forcée (révoquer les sessions)"
                        >
                          <Icon icon="ph:sign-out-bold" />
                        </button>
                        <!-- Lever sanctions -->
                        <button
                          v-if="user.is_banned || user.muted_until || user.suspended_until || user.is_restricted"
                          @click="confirmUnban(user)"
                          class="action-btn btn-unlock"
                          title="Lever toutes les sanctions"
                        >
                          <Icon icon="ph:shield-slash-fill" />
                        </button>
                        <!-- Supprimer -->
                        <button
                          @click="confirmAction('delete', user)"
                          class="action-btn btn-delete"
                          title="Supprimer définitivement"
                        >
                          <Icon icon="ph:trash-fill" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                      <td colspan="7">
                        <div class="empty-state">
                          <Icon icon="ph:user-minus" class="empty-icon" />
                          <p>Aucun utilisateur ne correspond aux critères</p>
                          <button @click="resetUserFilters" class="empty-reset">
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
                 TAB: PLACES (PHASE 2)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'places'" key="places">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Base de Données des Lieux & Cartographie</h2>
                  <p class="section-sub">{{ places.length }} lieu(x) répertorié(s) · Gestion, enrichissement IA & détection de doublons</p>
                </div>
                <div class="header-actions">
                  <button @click="loadDuplicates" class="secondary-btn" :class="{ 'btn-pulsing': duplicatesList.length > 0 }">
                    <Icon icon="ph:copy-bold" />
                    Analyser les doublons ({{ duplicatesList.length }})
                  </button>
                </div>
              </div>

              <!-- Pending places alert banner -->
              <div v-if="pendingPlacesList.length > 0 && placesStatusFilter !== 'pending'" class="pending-alert">
                <div class="pending-alert-icon">
                  <Icon icon="ph:warning-octagon-fill" />
                </div>
                <div class="flex-1">
                  <p class="pending-alert-title">{{ pendingPlacesList.length }} lieu(x) en attente de validation</p>
                  <p class="pending-alert-sub">Des suggestions d'étudiants attendent votre approbation avant d'apparaître sur la carte.</p>
                </div>
                <button @click="placesStatusFilter = 'pending'" class="pending-action-btn">
                  Voir la file d'attente
                </button>
              </div>

              <!-- Filters bar -->
              <div class="filters-bar">
                <div class="search-wrap">
                  <Icon icon="ph:magnifying-glass-bold" class="search-icon" />
                  <input
                    v-model="placesSearch"
                    placeholder="Rechercher par nom, description ou catégorie..."
                    class="search-input"
                  />
                  <button v-if="placesSearch" @click="placesSearch = ''" class="search-clear">
                    <Icon icon="ph:x-circle-fill" />
                  </button>
                </div>

                <div class="filter-select-wrap">
                  <select v-model="placesCategoryFilter" class="custom-select">
                    <option value="all">Toutes les catégories</option>
                    <option value="amphi">Amphithéâtres & Salles</option>
                    <option value="studies">Facultés & Départements</option>
                    <option value="library">Bibliothèques & Étude</option>
                    <option value="food">Restauration & Cafés</option>
                    <option value="housing">Résidences & Logements</option>
                    <option value="admin">Administration & Services</option>
                    <option value="health">Santé & Urgences</option>
                    <option value="other">Autres points d'intérêt</option>
                  </select>
                </div>

                <div class="filter-tabs">
                  <button
                    @click="placesStatusFilter = 'all'"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': placesStatusFilter === 'all' }"
                  >
                    <Icon icon="ph:list-bullets-bold" />
                    Tous
                    <span class="filter-count">{{ places.length }}</span>
                  </button>
                  <button
                    @click="placesStatusFilter = 'approved'"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': placesStatusFilter === 'approved' }"
                  >
                    <Icon icon="ph:check-circle-bold" />
                    Approuvés
                    <span class="filter-count">{{ approvedPlacesList.length }}</span>
                  </button>
                  <button
                    @click="placesStatusFilter = 'pending'"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': placesStatusFilter === 'pending' }"
                  >
                    <Icon icon="ph:hourglass-medium-bold" />
                    En attente
                    <span class="filter-count" :class="{ 'count-alert': pendingPlacesList.length > 0 }">{{ pendingPlacesList.length }}</span>
                  </button>
                  <button
                    @click="placesStatusFilter = 'hidden'"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': placesStatusFilter === 'hidden' }"
                  >
                    <Icon icon="ph:eye-slash-bold" />
                    Masqués
                    <span class="filter-count">{{ hiddenPlacesList.length }}</span>
                  </button>
                  <button
                    @click="placesStatusFilter = 'duplicates'"
                    class="filter-tab filter-tab--dup"
                    :class="{ 'filter-tab--active': placesStatusFilter === 'duplicates' }"
                  >
                    <Icon icon="ph:copy-bold" />
                    Doublons
                    <span class="filter-count" :class="{ 'count-alert': duplicatesList.length > 0 }">{{ duplicatesList.length }}</span>
                  </button>
                </div>
              </div>

              <!-- VIEW 1: DUPLICATES VIEW -->
              <div v-if="placesStatusFilter === 'duplicates'" class="duplicates-container">
                <div class="duplicates-header-card">
                  <div class="dup-header-icon">
                    <Icon icon="ph:intersect-bold" />
                  </div>
                  <div>
                    <h3 class="dup-header-title">Détection Automatique des Doublons Géographiques & Textuels</h3>
                    <p class="dup-header-sub">Algorithme de calcul Haversine (distance &lt; 60m) et comparaison de similarité de nom.</p>
                  </div>
                  <button @click="loadDuplicates" class="refresh-dup-btn" :disabled="duplicatesLoading">
                    <Icon :icon="duplicatesLoading ? 'ph:spinner-gap-bold' : 'ph:arrows-clockwise-bold'" :class="{ 'spinner-animate': duplicatesLoading }" />
                    Réanalyser
                  </button>
                </div>

                <div v-if="duplicatesLoading" class="context-loading">
                  <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
                  <span>Analyse spatiale en cours...</span>
                </div>

                <div v-else-if="duplicatesList.length === 0" class="empty-state">
                  <Icon icon="ph:check-circle" class="empty-icon" style="color: #22c55e;" />
                  <p>Aucun doublon potentiel détecté sur le campus !</p>
                  <p class="td-muted">Tous les lieux ont des coordonnées distinctes et des appellations uniques.</p>
                </div>

                <div v-else class="duplicates-list">
                  <div v-for="(dup, idx) in duplicatesList" :key="idx" class="dup-pair-card">
                    <div class="dup-pair-banner">
                      <div class="dup-metrics">
                        <span class="dup-badge dup-badge--dist">
                          <Icon icon="ph:map-pin-line-fill" />
                          {{ dup.distance_meters }} m d'écart
                        </span>
                        <span class="dup-badge dup-badge--sim">
                          <Icon icon="ph:text-t-bold" />
                          {{ dup.similarity_percent }}% de similarité
                        </span>
                      </div>
                      <span class="dup-reason">{{ dup.reason }}</span>
                    </div>

                    <div class="dup-comparison-grid">
                      <!-- Place A -->
                      <div class="dup-place-box">
                        <div class="dup-place-head">
                          <span class="dup-label">Candidat A (ID #{{ dup.place_a.id }})</span>
                          <span class="status-chip" :class="'chip-' + dup.place_a.status">{{ formatStatusLabel(dup.place_a.status) }}</span>
                        </div>
                        <h4 class="dup-place-name">{{ dup.place_a.name }}</h4>
                        <p class="dup-place-cat"><Icon icon="ph:tag-fill" /> {{ dup.place_a.category }}</p>
                        <p class="dup-place-desc">{{ dup.place_a.description || 'Aucune description' }}</p>
                        <div class="dup-place-coords">
                          <Icon icon="ph:map-pin-fill" /> {{ formatCoord(dup.place_a.latitude, 5) }}, {{ formatCoord(dup.place_a.longitude, 5) }}
                        </div>
                        <div class="dup-box-actions">
                          <button @click="openEditPlaceModal(dup.place_a)" class="place-btn place-btn--edit">
                            <Icon icon="ph:pencil-simple-bold" />
                            Éditer A
                          </button>
                          <button @click="resolveKeepOne(dup.place_a, dup.place_b)" class="place-btn place-btn--approve" title="Garder A et supprimer B">
                            <Icon icon="ph:check-bold" />
                            Garder A
                          </button>
                        </div>
                      </div>

                      <div class="dup-vs-divider">
                        <span>VS</span>
                      </div>

                      <!-- Place B -->
                      <div class="dup-place-box">
                        <div class="dup-place-head">
                          <span class="dup-label">Candidat B (ID #{{ dup.place_b.id }})</span>
                          <span class="status-chip" :class="'chip-' + dup.place_b.status">{{ formatStatusLabel(dup.place_b.status) }}</span>
                        </div>
                        <h4 class="dup-place-name">{{ dup.place_b.name }}</h4>
                        <p class="dup-place-cat"><Icon icon="ph:tag-fill" /> {{ dup.place_b.category }}</p>
                        <p class="dup-place-desc">{{ dup.place_b.description || 'Aucune description' }}</p>
                        <div class="dup-place-coords">
                          <Icon icon="ph:map-pin-fill" /> {{ formatCoord(dup.place_b.latitude, 5) }}, {{ formatCoord(dup.place_b.longitude, 5) }}
                        </div>
                        <div class="dup-box-actions">
                          <button @click="openEditPlaceModal(dup.place_b)" class="place-btn place-btn--edit">
                            <Icon icon="ph:pencil-simple-bold" />
                            Éditer B
                          </button>
                          <button @click="resolveKeepOne(dup.place_b, dup.place_a)" class="place-btn place-btn--approve" title="Garder B et supprimer A">
                            <Icon icon="ph:check-bold" />
                            Garder B
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- VIEW 2: STANDARD PLACES TABLE -->
              <div v-else class="table-card">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Lieu & Visuel</th>
                      <th>Catégorie</th>
                      <th>Coordonnées GPS</th>
                      <th>Statut / Visibilité</th>
                      <th>Ajouté par</th>
                      <th class="th-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="place in paginatedPlaces" :key="place.id" class="table-row">
                      <td>
                        <div class="place-cell">
                          <div class="place-thumb">
                            <img v-if="place.image_url" :src="place.image_url" :alt="place.name" class="place-thumb-img" @error="onImgError" />
                            <div v-else class="place-thumb-placeholder" :style="{ background: getCategoryBg(place.category) }">
                              <Icon :icon="getCategoryIcon(place.category)" />
                            </div>
                          </div>
                          <div class="place-cell-info">
                            <p class="place-name">{{ place.name }}</p>
                            <p class="place-desc-preview" :title="place.description">{{ place.description ? truncateText(place.description, 60) : 'Pas de description' }}</p>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="cat-tag" :style="{ borderColor: getCategoryColor(place.category), color: getCategoryColor(place.category) }">
                          <Icon :icon="getCategoryIcon(place.category)" />
                          {{ place.category }}
                        </span>
                      </td>
                      <td>
                        <div class="coords-badge" :title="'Lat: ' + place.latitude + ', Lng: ' + place.longitude">
                          <Icon icon="ph:map-pin-bold" />
                          <span>{{ formatCoord(place.latitude, 4) }}, {{ formatCoord(place.longitude, 4) }}</span>
                        </div>
                      </td>
                      <td>
                        <span v-if="place.status === 'approved'" class="status-chip chip-approved">
                          <Icon icon="ph:check-circle-fill" />
                          Approuvé
                        </span>
                        <span v-else-if="place.status === 'hidden'" class="status-chip chip-hidden">
                          <Icon icon="ph:eye-slash-fill" />
                          Masqué
                        </span>
                        <span v-else class="status-chip chip-pending">
                          <Icon icon="ph:clock-countdown-fill" />
                          En attente
                        </span>
                      </td>
                      <td class="td-muted">{{ place.added_by || 'Système' }}</td>
                      <td class="td-actions">
                        <!-- Edit Place & AI -->
                        <button
                          @click="openEditPlaceModal(place)"
                          class="action-btn btn-profile"
                          title="Éditer les détails & description IA"
                        >
                          <Icon icon="ph:pencil-simple-fill" />
                        </button>

                        <!-- Quick Visibility Toggle -->
                        <button
                          v-if="place.status === 'approved'"
                          @click="togglePlaceVisibility(place, 'hidden')"
                          class="action-btn btn-warning-soft"
                          title="Masquer de la carte"
                        >
                          <Icon icon="ph:eye-slash-fill" />
                        </button>
                        <button
                          v-else
                          @click="togglePlaceVisibility(place, 'approved')"
                          class="action-btn btn-unlock"
                          title="Approuver / Rendre visible"
                        >
                          <Icon icon="ph:check-bold" />
                        </button>

                        <!-- Delete Place -->
                        <button
                          @click="confirmAction('deletePlace', place)"
                          class="action-btn btn-delete"
                          title="Supprimer ce lieu"
                        >
                          <Icon icon="ph:trash-fill" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="filteredPlaces.length === 0">
                      <td colspan="6">
                        <div class="empty-state">
                          <Icon icon="ph:map-pin" class="empty-icon" />
                          <p>Aucun lieu ne correspond aux critères de recherche</p>
                          <button @click="resetPlaceFilters" class="empty-reset">
                            Réinitialiser les filtres
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Places Pagination -->
                <div class="pagination" v-if="totalPlacesPages > 1">
                  <button @click="placesPage--" :disabled="placesPage === 1" class="page-btn">
                    <Icon icon="ph:caret-left-bold" />
                  </button>
                  <button
                    v-for="p in totalPlacesPages" :key="p"
                    @click="placesPage = p"
                    class="page-btn"
                    :class="{ 'page-btn--active': placesPage === p }"
                  >
                    {{ p }}
                  </button>
                  <button @click="placesPage++" :disabled="placesPage === totalPlacesPages" class="page-btn">
                    <Icon icon="ph:caret-right-bold" />
                  </button>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: MESSAGES
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'messages'" key="messages">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Logs de Messagerie Récente</h2>
                  <p class="section-sub">50 messages les plus récents · Modération directe</p>
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
                      <th class="th-right">Action</th>
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
                      <td class="td-actions">
                        <button
                          @click="confirmDeleteMessage(msg.id)"
                          class="action-btn btn-delete"
                          title="Supprimer ce message"
                        >
                          <Icon icon="ph:trash-fill" />
                        </button>
                      </td>
                    </tr>
                    <tr v-if="messages.length === 0">
                      <td colspan="6">
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
                 TAB: AUDIT (JOURNAL D'AUDIT)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'audit'" key="audit">
              <div class="section-header">
                <div>
                  <h2 class="section-title">Journal d'Audit & Historique des Décisions</h2>
                  <p class="section-sub">Traçabilité complète des actions de modération et de gestion</p>
                </div>
                <button @click="loadAuditLogs" class="refresh-btn">
                  <Icon icon="ph:arrows-clockwise-bold" />
                  Actualiser
                </button>
              </div>

              <div class="table-card">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date & Heure</th>
                      <th>Action</th>
                      <th>Cible</th>
                      <th>Détails</th>
                      <th>IP</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="log in auditLogs" :key="log.id" class="table-row">
                      <td class="td-date">{{ formatDateTime(log.created_at) }}</td>
                      <td>
                        <span class="audit-action-badge" :class="'audit-type-' + log.action">
                          <Icon :icon="getAuditActionIcon(log.action)" />
                          {{ formatAuditAction(log.action) }}
                        </span>
                      </td>
                      <td class="td-bold">
                        <span v-if="log.target_type" class="target-chip">{{ log.target_type }} #{{ log.target_id }}</span>
                        <span v-else>—</span>
                      </td>
                      <td class="td-details">
                        <span v-if="log.details">{{ formatAuditDetails(log.details) }}</span>
                        <span v-else class="td-muted">—</span>
                      </td>
                      <td class="td-muted">{{ log.ip_address || '—' }}</td>
                    </tr>
                    <tr v-if="auditLogs.length === 0">
                      <td colspan="5">
                        <div class="empty-state">
                          <Icon icon="ph:notepad" class="empty-icon" />
                          <p>Aucun enregistrement d'audit</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: SYSTEM (SUPERVISION TECHNIQUE & SANTÉ SYSTÈME - PHASE 5)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'system'" key="system">
              <!-- Section Header -->
              <div class="section-header">
                <div>
                  <h2 class="section-title">Supervision Technique & Santé Système</h2>
                  <p class="section-sub">Surveillance en temps réel de PostgreSQL (Neon), Caches, Passerelles IA, Ressources et Logs</p>
                </div>
                <div class="flex-items-center gap-2">
                  <button @click="loadSystemHealth" class="refresh-btn" :disabled="systemLoading">
                    <Icon icon="ph:arrows-clockwise-bold" :class="{ 'spinner-animate': systemLoading }" />
                    Tester la santé
                  </button>
                </div>
              </div>

              <!-- Services Status Grid (4 Main Service Cards) -->
              <div class="system-health-grid">
                <!-- 1. Database Neon PostgreSQL -->
                <div class="health-card" :class="systemHealthData?.services?.database?.status === 'ok' ? 'health-card--good' : 'health-card--error'">
                  <div class="health-card-head">
                    <div class="health-icon-wrap db-icon">
                      <Icon icon="ph:database-fill" />
                    </div>
                    <span class="health-status-badge" :class="systemHealthData?.services?.database?.status === 'ok' ? 'badge-good' : 'badge-error'">
                      <span class="status-indicator-dot"></span>
                      {{ systemHealthData?.services?.database?.status === 'ok' ? 'Opérationnel' : 'Erreur' }}
                    </span>
                  </div>
                  <h3 class="health-title">PostgreSQL (Neon Cloud)</h3>
                  <p class="health-desc">Base de données principale U-map</p>
                  <div class="health-metrics-row">
                    <div class="health-metric">
                      <span class="metric-val text-green">{{ systemHealthData?.services?.database?.latency_ms ?? '—' }} ms</span>
                      <span class="metric-lbl">Latence probe</span>
                    </div>
                    <div class="health-metric">
                      <span class="metric-val">{{ systemHealthData?.services?.database?.driver ?? 'pgsql' }}</span>
                      <span class="metric-lbl">Driver</span>
                    </div>
                  </div>
                  <p v-if="systemHealthData?.services?.database?.error" class="health-err-text">
                    {{ systemHealthData.services.database.error }}
                  </p>
                </div>

                <!-- 2. Application Cache -->
                <div class="health-card" :class="systemHealthData?.services?.cache?.status === 'ok' ? 'health-card--good' : 'health-card--error'">
                  <div class="health-card-head">
                    <div class="health-icon-wrap cache-icon">
                      <Icon icon="ph:lightning-fill" />
                    </div>
                    <span class="health-status-badge" :class="systemHealthData?.services?.cache?.status === 'ok' ? 'badge-good' : 'badge-error'">
                      <span class="status-indicator-dot"></span>
                      {{ systemHealthData?.services?.cache?.status === 'ok' ? 'Opérationnel' : 'Erreur' }}
                    </span>
                  </div>
                  <h3 class="health-title">Cache Applicatif</h3>
                  <p class="health-desc">Session, Token Admin & Requêtes rapides</p>
                  <div class="health-metrics-row">
                    <div class="health-metric">
                      <span class="metric-val text-blue">{{ systemHealthData?.services?.cache?.latency_ms ?? '—' }} ms</span>
                      <span class="metric-lbl">Temps écriture/lecture</span>
                    </div>
                    <div class="health-metric">
                      <span class="metric-val">{{ systemHealthData?.services?.cache?.driver ?? 'file' }}</span>
                      <span class="metric-lbl">Driver</span>
                    </div>
                  </div>
                </div>

                <!-- 3. AI Gateway (Groq Llama 3.3 & Gemini) -->
                <div class="health-card" :class="(systemHealthData?.services?.ai_groq?.configured || systemHealthData?.services?.ai_gemini?.configured) ? 'health-card--good' : 'health-card--warning'">
                  <div class="health-card-head">
                    <div class="health-icon-wrap ai-icon">
                      <Icon icon="ph:sparkle-fill" />
                    </div>
                    <span class="health-status-badge" :class="systemHealthData?.services?.ai_groq?.configured ? 'badge-good' : 'badge-warn'">
                      <span class="status-indicator-dot"></span>
                      {{ systemHealthData?.services?.ai_groq?.configured ? 'Groq Prêt' : 'Fallback' }}
                    </span>
                  </div>
                  <h3 class="health-title">Passerelles IA</h3>
                  <p class="health-desc">Groq (Llama 3.3 70B) & Gemini Flash</p>
                  <div class="health-metrics-row">
                    <div class="health-metric">
                      <span class="metric-val text-purple">{{ systemHealthData?.services?.ai_groq?.configured ? 'Active' : 'Non config' }}</span>
                      <span class="metric-lbl">Clé Groq API</span>
                    </div>
                    <div class="health-metric">
                      <span class="metric-val text-indigo">{{ systemHealthData?.services?.ai_gemini?.configured ? 'Active' : 'Non config' }}</span>
                      <span class="metric-lbl">Clé Gemini</span>
                    </div>
                  </div>
                </div>

                <!-- 4. Serveur PHP & Environnement -->
                <div class="health-card health-card--info">
                  <div class="health-card-head">
                    <div class="health-icon-wrap srv-icon">
                      <Icon icon="ph:cpu-fill" />
                    </div>
                    <span class="health-status-badge badge-good">
                      <span class="status-indicator-dot"></span>
                      PHP {{ systemHealthData?.server?.php_version?.split('-')[0] || '8.2+' }}
                    </span>
                  </div>
                  <h3 class="health-title">Environnement Laravel</h3>
                  <p class="health-desc">Laravel v{{ systemHealthData?.server?.laravel_version || '11' }} · {{ systemHealthData?.server?.environment || 'production' }}</p>
                  <div class="health-metrics-row">
                    <div class="health-metric">
                      <span class="metric-val text-orange">{{ systemHealthData?.resources?.memory_usage_formatted || '—' }}</span>
                      <span class="metric-lbl">RAM active</span>
                    </div>
                    <div class="health-metric">
                      <span class="metric-val">{{ systemHealthData?.resources?.disk_free_formatted || '—' }}</span>
                      <span class="metric-lbl">Disque libre</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Maintenance Toolkit Bar -->
              <div class="maintenance-box">
                <div class="maintenance-left">
                  <div class="maintenance-icon">
                    <Icon icon="ph:wrench-fill" />
                  </div>
                  <div>
                    <h3 class="maintenance-title">Boîte à Outils de Maintenance Système</h3>
                    <p class="maintenance-sub">Exécutez les routines de vidage de cache, réinitialisation de configuration et optimisation</p>
                  </div>
                </div>
                <div class="maintenance-actions">
                  <button
                    @click="handleClearCache"
                    :disabled="maintenanceRunning"
                    class="maint-btn maint-btn--cache"
                    title="Vider Cache, Config, Routes et Vues compilées"
                  >
                    <Icon :icon="maintenanceRunning === 'cache' ? 'ph:spinner-gap-bold' : 'ph:broom-bold'" :class="{ 'spinner-animate': maintenanceRunning === 'cache' }" />
                    Vider les caches
                  </button>
                  <button
                    @click="handleOptimizeSystem"
                    :disabled="maintenanceRunning"
                    class="maint-btn maint-btn--optimize"
                    title="Compiler et optimiser les classes et routes"
                  >
                    <Icon :icon="maintenanceRunning === 'optimize' ? 'ph:spinner-gap-bold' : 'ph:rocket-launch-bold'" :class="{ 'spinner-animate': maintenanceRunning === 'optimize' }" />
                    Optimiser (Optimize)
                  </button>
                  <button
                    @click="handleClearLogs"
                    :disabled="maintenanceRunning"
                    class="maint-btn maint-btn--danger"
                    title="Purger le fichier storage/logs/laravel.log"
                  >
                    <Icon :icon="maintenanceRunning === 'logs' ? 'ph:spinner-gap-bold' : 'ph:trash-bold'" :class="{ 'spinner-animate': maintenanceRunning === 'logs' }" />
                    Purger les logs ({{ systemHealthData?.resources?.log_file_size_formatted || '0 KB' }})
                  </button>
                </div>
              </div>

              <!-- Real-time Log Explorer -->
              <div class="log-explorer-card">
                <div class="log-explorer-head">
                  <div class="flex-items-center gap-2 flex-wrap">
                    <div class="log-icon-badge">
                      <Icon icon="ph:terminal-window-fill" />
                    </div>
                    <div>
                      <h3 class="log-card-title">Explorateur des Logs Applicatifs (storage/logs/laravel.log)</h3>
                      <p class="log-card-sub">
                        Taille du fichier : <strong>{{ systemLogsData?.file_size || '0 KB' }}</strong> · 
                        {{ systemLogsData?.total_parsed || 0 }} entrées récentes analysées
                      </p>
                    </div>
                  </div>
                  <div class="log-controls">
                    <!-- Level Filter -->
                    <select v-model="logFilterLevel" @change="loadSystemLogs" class="custom-select log-select">
                      <option value="all">Tous les niveaux</option>
                      <option value="error">Erreurs (Error / Critical)</option>
                      <option value="warning">Avertissements (Warning)</option>
                      <option value="info">Informations (Info)</option>
                      <option value="debug">Débogage (Debug)</option>
                    </select>
                    <!-- Search input -->
                    <div class="log-search-wrap">
                      <Icon icon="ph:magnifying-glass" class="log-search-icon" />
                      <input
                        v-model="logSearch"
                        @keyup.enter="loadSystemLogs"
                        placeholder="Rechercher dans les logs..."
                        class="log-search-input"
                      />
                    </div>
                    <button @click="loadSystemLogs" class="refresh-btn-small" title="Recharger les logs">
                      <Icon icon="ph:arrows-clockwise-bold" />
                    </button>
                  </div>
                </div>

                <!-- Logs Table / List -->
                <div class="logs-container custom-scrollbar">
                  <div v-if="systemLogsLoading" class="context-loading">
                    <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
                    <span>Lecture et analyse du journal en cours...</span>
                  </div>

                  <div v-else-if="systemLogsData?.logs?.length > 0" class="log-entries-list">
                    <div
                      v-for="log in systemLogsData.logs" :key="log.id"
                      class="log-row"
                      :class="'log-row--' + log.level"
                      @click="openLogDetail(log)"
                    >
                      <div class="log-row-left">
                        <span class="log-level-chip" :class="'chip-level-' + log.level">
                          {{ log.level.toUpperCase() }}
                        </span>
                        <span class="log-timestamp">{{ log.timestamp }}</span>
                        <span class="log-env">[{{ log.environment }}]</span>
                      </div>
                      <div class="log-row-msg">
                        {{ log.message }}
                      </div>
                      <div class="log-row-actions">
                        <span v-if="log.stack_trace" class="trace-indicator" title="Trace d'exécution disponible">
                          <Icon icon="ph:code-bold" /> Trace
                        </span>
                        <button class="log-view-btn" title="Voir les détails complets">
                          <Icon icon="ph:arrow-square-out-bold" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-else class="empty-state">
                    <Icon icon="ph:check-circle" class="empty-icon text-green" />
                    <p>Aucun log correspondant aux critères de recherche.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- ════════════════════════════════
                 TAB: AI CAMPUS (ASSISTANT & SUGGESTIONS CAMPUS - PHASE 6)
            ════════════════════════════════ -->
            <div v-else-if="currentTab === 'ai_campus'" key="ai_campus">
              <!-- Section Header -->
              <div class="section-header">
                <div>
                  <h2 class="section-title">Assistant IA & Suggestions Campus</h2>
                  <p class="section-sub">Audit qualité des métadonnées cartographiques, détection de lacunes et génération de bulletins d'actualité</p>
                </div>
                <div class="flex-items-center gap-2">
                  <button @click="runCampusAudit" class="refresh-btn" :disabled="campusAuditLoading">
                    <Icon icon="ph:sparkle-bold" :class="{ 'spinner-animate': campusAuditLoading }" />
                    Lancer l'audit qualité
                  </button>
                </div>
              </div>

              <!-- Top Row: Scorecard & Actions -->
              <div class="ai-campus-top-grid">
                <!-- Quality Score Card -->
                <div class="ai-score-card">
                  <div class="score-card-left">
                    <div class="score-circle" :style="{ borderColor: getScoreColor(campusAuditData?.quality_score ?? 85) }">
                      <span class="score-num" :style="{ color: getScoreColor(campusAuditData?.quality_score ?? 85) }">
                        {{ campusAuditData?.quality_score ?? 85 }}
                      </span>
                      <span class="score-max">/100</span>
                    </div>
                    <div>
                      <h3 class="score-title">Indice de Qualité Cartographique</h3>
                      <p class="score-desc">Calculé sur la complétude des descriptions, photos, catégories et coordonnées</p>
                    </div>
                  </div>
                  <div class="score-breakdown">
                    <div class="score-stat-item">
                      <span class="stat-badge badge-warn">{{ campusAuditData?.summary?.missing_descriptions ?? 0 }}</span>
                      <span class="stat-lbl">Sans description</span>
                    </div>
                    <div class="score-stat-item">
                      <span class="stat-badge badge-error">{{ campusAuditData?.summary?.missing_categories ?? 0 }}</span>
                      <span class="stat-lbl">Sans catégorie</span>
                    </div>
                    <div class="score-stat-item">
                      <span class="stat-badge badge-error">{{ campusAuditData?.summary?.missing_coordinates ?? 0 }}</span>
                      <span class="stat-lbl">GPS invalides</span>
                    </div>
                  </div>
                </div>

                <!-- Weekly Digest Generator Box -->
                <div class="digest-generator-card">
                  <div class="digest-gen-head">
                    <div class="flex-items-center gap-2">
                      <div class="digest-icon">
                        <Icon icon="ph:newspaper-clipping-fill" />
                      </div>
                      <div>
                        <h3 class="digest-title">Bulletin Campus IA</h3>
                        <p class="digest-sub">Synthèse hebdomadaire prête à publier</p>
                      </div>
                    </div>
                    <button
                      @click="generateWeeklyDigest"
                      :disabled="campusDigestLoading"
                      class="generate-digest-btn"
                    >
                      <Icon :icon="campusDigestLoading ? 'ph:spinner-gap-bold' : 'ph:sparkle-fill'" :class="{ 'spinner-animate': campusDigestLoading }" />
                      {{ campusDigestData ? 'Régénérer' : 'Générer avec IA' }}
                    </button>
                  </div>

                  <div v-if="campusDigestData" class="digest-preview-content custom-scrollbar">
                    <div class="digest-meta-bar">
                      <span class="source-tag">Source : {{ campusDigestData.source }}</span>
                      <span class="digest-date">{{ campusDigestData.generated_at }}</span>
                      <button @click="copyDigestToClipboard" class="copy-digest-btn">
                        <Icon :icon="copiedDigest ? 'ph:check-bold' : 'ph:copy-bold'" />
                        {{ copiedDigest ? 'Copié !' : 'Copier' }}
                      </button>
                    </div>
                    <div class="digest-text-markdown">
                      <pre class="digest-pre">{{ campusDigestData.digest }}</pre>
                    </div>
                  </div>
                  <div v-else class="digest-placeholder">
                    <Icon icon="ph:sparkle" class="placeholder-icon" />
                    <p>Cliquez sur "Générer avec IA" pour créer automatiquement le bulletin hebdomadaire du campus UAC basé sur vos données réelles.</p>
                  </div>
                </div>
              </div>

              <!-- Quality Audit Issues Table -->
              <div class="audit-issues-section">
                <div class="section-header mb-3">
                  <div>
                    <h3 class="section-title">Anomalies & Lieux Nécessitant une Attention ({{ campusAuditData?.issues?.length || 0 }})</h3>
                    <p class="section-sub">Recommandations automatiques pour améliorer l'expérience des étudiants</p>
                  </div>
                </div>

                <div v-if="campusAuditLoading" class="context-loading">
                  <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
                  <span>Audit des lieux en cours...</span>
                </div>

                <div v-else-if="campusAuditData?.issues?.length > 0" class="audit-issues-grid">
                  <div v-for="item in campusAuditData.issues" :key="item.place_id" class="audit-issue-card">
                    <div class="issue-card-head">
                      <div>
                        <h4 class="issue-place-name">{{ item.place_name }}</h4>
                        <span class="issue-place-cat">{{ item.category }}</span>
                      </div>
                      <span class="status-chip" :class="'chip-' + item.status">{{ formatStatusLabel(item.status) }}</span>
                    </div>

                    <div class="issue-badges-list">
                      <div
                        v-for="(iss, idx) in item.issues" :key="idx"
                        class="issue-pill"
                        :class="'pill-severity-' + iss.severity"
                      >
                        <Icon :icon="iss.severity === 'critical' ? 'ph:x-circle-fill' : 'ph:warning-circle-fill'" />
                        <span>{{ iss.message }}</span>
                      </div>
                    </div>

                    <div class="issue-card-actions">
                      <button
                        @click="openEditPlaceModal({ id: item.place_id, name: item.place_name, category: item.category, status: item.status })"
                        class="action-btn-pill btn-edit-pill"
                      >
                        <Icon icon="ph:pencil-simple-bold" />
                        Éditer
                      </button>
                      <button
                        @click="regeneratePlaceDescriptionDirect(item.place_id)"
                        class="action-btn-pill btn-ai-pill"
                        title="Générer et enregistrer automatiquement une description avec l'IA"
                      >
                        <Icon icon="ph:sparkle-bold" />
                        Auto-enrichir IA
                      </button>
                    </div>
                  </div>
                </div>

                <div v-else class="empty-state">
                  <Icon icon="ph:shield-check-fill" class="empty-icon text-green" />
                  <p>Félicitations ! Tous les lieux répertoriés sont parfaitement renseignés.</p>
                </div>
              </div>
            </div>

          </transition>
        </div>
      </div>
    </main>

    <!-- ══════════════════════════════════════════════════════
         MODAL : VUE CONTEXTUELLE DU SIGNALEMENT (CHAT TIMELINE)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="contextModal.visible" class="modal-overlay" @click.self="contextModal.visible = false">
        <div class="modal-card modal-card--lg">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon">
                <Icon icon="ph:chats-circle-fill" />
              </div>
              <div>
                <h3 class="modal-title">Contexte du Signalement #{{ contextModal.report?.id }}</h3>
                <p class="modal-sub">
                  Signalé par <strong>{{ contextModal.report?.reporter?.name }}</strong> contre <strong>{{ contextModal.report?.reported_user?.name }}</strong>
                </p>
              </div>
            </div>
            <button @click="contextModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <!-- Reason Alert -->
          <div class="context-reason-box">
            <Icon icon="ph:warning-fill" class="reason-box-icon" />
            <div>
              <p class="reason-box-label">Motif du signalement :</p>
              <p class="reason-box-text">{{ contextModal.report?.reason }}</p>
            </div>
          </div>

          <!-- Chat Timeline messages -->
          <div class="context-chat-container custom-scrollbar">
            <div v-if="contextModal.loading" class="context-loading">
              <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
              <span>Chargement du contexte...</span>
            </div>

            <div v-else-if="contextModal.messages.length === 0" class="context-empty">
              <p>Aucun message trouvé pour cette conversation.</p>
            </div>

            <div v-else class="context-messages-list">
              <div
                v-for="msg in contextModal.messages" :key="msg.id"
                class="context-msg-item"
                :class="{
                  'is-target-msg': contextModal.targetMessage?.id === msg.id,
                  'is-reported-author': msg.sender_id === contextModal.report?.reported_user_id
                }"
              >
                <div class="context-msg-meta">
                  <span class="context-msg-author" :class="{ 'author-target': msg.sender_id === contextModal.report?.reported_user_id }">
                    {{ msg.sender?.name || ('Utilisateur #' + msg.sender_id) }}
                  </span>
                  <span class="context-msg-time">{{ formatDateTime(msg.created_at) }}</span>
                  <span v-if="contextModal.targetMessage?.id === msg.id" class="target-flag-badge">
                    <Icon icon="ph:flag-fill" /> Message Signalé
                  </span>
                </div>

                <div class="context-msg-bubble">
                  {{ msg.content }}
                </div>

                <div v-if="contextModal.targetMessage?.id === msg.id" class="context-msg-actions">
                  <button @click="confirmDeleteMessage(msg.id)" class="del-msg-btn">
                    <Icon icon="ph:trash-fill" />
                    Supprimer ce message
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer actions -->
          <div class="modal-actions modal-actions--split">
            <button @click="contextModal.visible = false" class="modal-btn modal-btn--cancel">
              Fermer
            </button>
            <div class="flex-items-center gap-2">
              <button
                @click="openSanctionModal(contextModal.report)"
                class="modal-btn modal-btn--danger"
              >
                <Icon icon="ph:gavel-bold" />
                Sanctionner l'auteur
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL : ACTIONS GRADUÉES DE MODÉRATION
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="sanctionModal.visible" class="modal-overlay" @click.self="sanctionModal.visible = false">
        <div class="modal-card">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon modal-head-icon--danger">
                <Icon icon="ph:gavel-fill" />
              </div>
              <div>
                <h3 class="modal-title">Appliquer une Sanction</h3>
                <p class="modal-sub">Cible : <strong>{{ sanctionModal.user?.name }}</strong> ({{ sanctionModal.user?.email }})</p>
              </div>
            </div>
            <button @click="sanctionModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <div class="modal-form-body">
            <!-- Sanction Type Radios -->
            <label class="form-label">Type de sanction :</label>
            <div class="sanction-types-grid">
              <label
                v-for="st in sanctionOptions" :key="st.id"
                class="sanction-type-card"
                :class="{ 'is-selected': sanctionModal.type === st.id }"
              >
                <input type="radio" v-model="sanctionModal.type" :value="st.id" class="hidden-radio" />
                <div class="st-icon-wrap" :class="'st-color-' + st.color">
                  <Icon :icon="st.icon" />
                </div>
                <div>
                  <h4 class="st-title">{{ st.title }}</h4>
                  <p class="st-desc">{{ st.desc }}</p>
                </div>
              </label>
            </div>

            <!-- Duration (if mute or suspension) -->
            <div v-if="sanctionModal.type === 'mute' || sanctionModal.type === 'suspension'" class="form-group">
              <label class="form-label">Durée de la sanction :</label>
              <select v-model="sanctionModal.durationHours" class="custom-select full-width">
                <option :value="1">1 heure</option>
                <option :value="12">12 heures</option>
                <option :value="24">24 heures (1 jour)</option>
                <option :value="72">3 jours</option>
                <option :value="168">7 jours (1 semaine)</option>
                <option :value="720">30 jours (1 mois)</option>
              </select>
            </div>

            <!-- Reason (mandatory) -->
            <div class="form-group">
              <label class="form-label">Motif de la sanction (obligatoire) :</label>
              <textarea
                v-model="sanctionModal.reason"
                rows="3"
                placeholder="Expliquez la raison (ex: propos injurieux répétés, spam)..."
                class="form-textarea"
              ></textarea>
            </div>

            <!-- Auto-resolve report checkbox -->
            <div v-if="sanctionModal.reportId" class="form-checkbox-row">
              <input type="checkbox" v-model="sanctionModal.autoResolveReport" id="autoResolve" />
              <label for="autoResolve">Clôturer automatiquement le signalement lié</label>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="sanctionModal.visible = false" class="modal-btn modal-btn--cancel">
              Annuler
            </button>
            <button
              @click="submitSanction"
              :disabled="!sanctionModal.reason.trim() || sanctionModal.submitting"
              class="modal-btn modal-btn--danger"
            >
              <Icon :icon="sanctionModal.submitting ? 'ph:spinner-gap-bold' : 'ph:check-bold'" :class="{ 'spinner-animate': sanctionModal.submitting }" />
              Appliquer la sanction
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL : DOSSIER UTILISATEUR & GESTION DES RÔLES (PHASE 3)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="profileModal.visible" class="modal-overlay" @click.self="profileModal.visible = false">
        <div class="modal-card modal-card--lg dossier-modal-card">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon" style="background: rgba(99,102,241,0.2); color: #818cf8;">
                <Icon icon="ph:user-gear-fill" />
              </div>
              <div>
                <h3 class="modal-title">Dossier & Profil Utilisateur</h3>
                <p class="modal-sub">Rôles, historique d'activité, infractions et notes confidentielles</p>
              </div>
            </div>
            <button @click="profileModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <div v-if="profileModal.loading" class="context-loading">
            <Icon icon="ph:spinner-gap-bold" class="spinner-animate" />
            <span>Chargement du dossier complet...</span>
          </div>

          <div v-else-if="profileModal.data" class="profile-modal-content custom-scrollbar">
            <!-- User Summary Header -->
            <div class="profile-header-card">
              <div class="user-avatar user-avatar--lg" :style="{ background: userAvatarGradient(profileModal.data.user.name) }">
                {{ profileModal.data.user.name?.charAt(0).toUpperCase() }}
              </div>
              <div class="profile-header-info">
                <div class="flex-items-center gap-2 flex-wrap">
                  <h3 class="profile-name">{{ profileModal.data.user.name }}</h3>
                  <span class="role-chip" :class="'role-chip--' + (profileModal.data.user.role || 'user')">
                    <Icon :icon="getRoleIcon(profileModal.data.user.role)" />
                    {{ formatRole(profileModal.data.user.role) }}
                  </span>
                </div>
                <p class="profile-email">{{ profileModal.data.user.email }}</p>
                <div class="profile-tags">
                  <span class="user-id-tag">ID: #{{ profileModal.data.user.id }}</span>
                  <span v-if="profileModal.data.user.student_id" class="student-id-tag">Matricule: {{ profileModal.data.user.student_id }}</span>
                  <span v-if="profileModal.data.user.faculty" class="user-faculty-tag">{{ profileModal.data.user.faculty }} ({{ profileModal.data.user.study_level || 'Niveau N/A' }})</span>
                  <span class="user-date-tag">Inscrit le {{ formatDate(profileModal.data.user.created_at) }}</span>
                </div>
              </div>
              <div class="profile-status-box">
                <span v-if="profileModal.data.user.is_banned" class="status-chip chip-banned">Banni</span>
                <span v-else-if="profileModal.data.user.suspended_until && new Date(profileModal.data.user.suspended_until) > new Date()" class="status-chip chip-suspended">Suspendu</span>
                <span v-else-if="profileModal.data.user.muted_until && new Date(profileModal.data.user.muted_until) > new Date()" class="status-chip chip-muted">Muet</span>
                <span v-else-if="profileModal.data.user.is_restricted" class="status-chip chip-restricted">Restreint</span>
                <span v-else class="status-chip chip-active">Actif</span>
              </div>
            </div>

            <!-- Role Management Bar -->
            <div class="role-management-card">
              <div class="role-mgmt-left">
                <Icon icon="ph:shield-check-bold" class="role-mgmt-icon" />
                <div>
                  <h4 class="role-mgmt-title">Attribution du Rôle & Privilèges</h4>
                  <p class="role-mgmt-sub">Définir le niveau d'autorisation dans la plateforme</p>
                </div>
              </div>
              <div class="role-mgmt-controls">
                <select v-model="profileModal.selectedRole" class="custom-select role-select">
                  <option value="user">Étudiant (Utilisateur standard)</option>
                  <option value="moderator">Modérateur (Gestion des signalements)</option>
                  <option value="admin">Administrateur (Gestion complète)</option>
                  <option value="super_admin">Super Administrateur</option>
                </select>
                <button
                  @click="submitUpdateRole"
                  :disabled="profileModal.updatingRole || profileModal.selectedRole === profileModal.data.user.role"
                  class="role-save-btn"
                >
                  <Icon :icon="profileModal.updatingRole ? 'ph:spinner-gap-bold' : 'ph:check-bold'" :class="{ 'spinner-animate': profileModal.updatingRole }" />
                  Enregistrer le rôle
                </button>
              </div>
            </div>

            <!-- Stats Counters Row (4 metrics) -->
            <div class="profile-counters-row">
              <div class="counter-box" @click="profileModal.activeTab = 'places'" :class="{ 'counter-box--active': profileModal.activeTab === 'places' }">
                <span class="counter-num text-green">{{ profileModal.data.stats?.places_count || 0 }}</span>
                <span class="counter-lbl">Lieux proposés</span>
              </div>
              <div class="counter-box" @click="profileModal.activeTab = 'messages'" :class="{ 'counter-box--active': profileModal.activeTab === 'messages' }">
                <span class="counter-num text-blue">{{ profileModal.data.stats?.messages_count || 0 }}</span>
                <span class="counter-lbl">Messages envoyés</span>
              </div>
              <div class="counter-box" @click="profileModal.activeTab = 'reports'" :class="{ 'counter-box--active': profileModal.activeTab === 'reports' }">
                <span class="counter-num text-red">{{ profileModal.data.stats?.reports_received_count || 0 }}</span>
                <span class="counter-lbl">Signalements reçus</span>
              </div>
              <div class="counter-box" @click="profileModal.activeTab = 'sanctions'" :class="{ 'counter-box--active': profileModal.activeTab === 'sanctions' }">
                <span class="counter-num text-orange">{{ profileModal.data.stats?.sanctions_count || 0 }}</span>
                <span class="counter-lbl">Sanctions passées</span>
              </div>
            </div>

            <!-- Dossier Sub-tabs -->
            <div class="dossier-tabs-nav">
              <button
                @click="profileModal.activeTab = 'sanctions'"
                class="dossier-tab-btn"
                :class="{ 'dossier-tab-btn--active': profileModal.activeTab === 'sanctions' }"
              >
                <Icon icon="ph:gavel-bold" />
                Sanctions & Notes
              </button>
              <button
                @click="profileModal.activeTab = 'places'"
                class="dossier-tab-btn"
                :class="{ 'dossier-tab-btn--active': profileModal.activeTab === 'places' }"
              >
                <Icon icon="ph:map-pin-bold" />
                Lieux soumis ({{ profileModal.data.places?.length || 0 }})
              </button>
              <button
                @click="profileModal.activeTab = 'messages'"
                class="dossier-tab-btn"
                :class="{ 'dossier-tab-btn--active': profileModal.activeTab === 'messages' }"
              >
                <Icon icon="ph:chat-teardrop-dots-bold" />
                Messages récents ({{ profileModal.data.recent_messages?.length || 0 }})
              </button>
              <button
                @click="profileModal.activeTab = 'reports'"
                class="dossier-tab-btn"
                :class="{ 'dossier-tab-btn--active': profileModal.activeTab === 'reports' }"
              >
                <Icon icon="ph:flag-bold" />
                Signalements
              </button>
            </div>

            <!-- TAB 1: SANCTIONS & INTERNAL NOTE -->
            <div v-if="profileModal.activeTab === 'sanctions'" class="dossier-tab-pane">
              <!-- Internal Admin Note Editor -->
              <div class="internal-note-box">
                <div class="internal-note-head">
                  <div class="flex-items-center gap-1.5">
                    <Icon icon="ph:note-pencil-bold" />
                    <strong>Note interne confidentielle (visible uniquement des administrateurs)</strong>
                  </div>
                  <button @click="saveUserAdminNote" class="save-note-btn" :disabled="profileModal.savingNote">
                    <Icon :icon="profileModal.savingNote ? 'ph:spinner-gap-bold' : 'ph:floppy-disk-bold'" :class="{ 'spinner-animate': profileModal.savingNote }" />
                    Enregistrer la note
                  </button>
                </div>
                <textarea
                  v-model="profileModal.adminNote"
                  rows="3"
                  placeholder="Renseignez ici les antécédents, observations ou avertissements informels..."
                  class="form-textarea"
                ></textarea>
              </div>

              <!-- Sanctions History Timeline -->
              <div class="sanctions-timeline-section">
                <h4 class="section-subtitle">
                  <Icon icon="ph:clock-countdown-bold" />
                  Historique des sanctions reçues
                </h4>

                <div v-if="profileModal.data.sanctions?.length > 0" class="sanctions-timeline">
                  <div v-for="sanc in profileModal.data.sanctions" :key="sanc.id" class="timeline-item">
                    <div class="timeline-dot" :class="'dot-' + sanc.type"></div>
                    <div class="timeline-card">
                      <div class="timeline-head">
                        <span class="timeline-type" :class="'type-' + sanc.type">{{ formatSanctionType(sanc.type) }}</span>
                        <span class="timeline-date">{{ formatDateTime(sanc.created_at) }}</span>
                      </div>
                      <p class="timeline-reason">{{ sanc.reason }}</p>
                      <div class="timeline-meta">
                        <span v-if="sanc.admin?.username">Par : <strong>@{{ sanc.admin.username }}</strong></span>
                        <span v-if="sanc.expires_at">Expire le : {{ formatDateTime(sanc.expires_at) }}</span>
                        <span v-if="sanc.is_active && (!sanc.expires_at || new Date(sanc.expires_at) > new Date())" class="active-badge">Active</span>
                        <span v-else class="expired-badge">Expirée / Levée</span>
                      </div>
                    </div>
                  </div>
                </div>
                <p v-else class="no-sanctions-msg">Aucune sanction enregistrée pour cet utilisateur.</p>
              </div>
            </div>

            <!-- TAB 2: PLACES CONTRIBUTIONS -->
            <div v-else-if="profileModal.activeTab === 'places'" class="dossier-tab-pane">
              <div v-if="profileModal.data.places?.length > 0" class="dossier-list">
                <div v-for="p in profileModal.data.places" :key="p.id" class="dossier-item-card">
                  <div class="dossier-item-left">
                    <div class="dossier-item-icon" style="background: rgba(34,197,94,0.15); color: #4ade80;">
                      <Icon icon="ph:map-pin-fill" />
                    </div>
                    <div>
                      <h4 class="dossier-item-title">{{ p.name }}</h4>
                      <p class="dossier-item-sub"><Icon icon="ph:tag-fill" /> {{ p.category }} · {{ formatDate(p.created_at) }}</p>
                    </div>
                  </div>
                  <span class="status-chip" :class="'chip-' + p.status">{{ formatStatusLabel(p.status) }}</span>
                </div>
              </div>
              <div v-else class="empty-state-small">
                <Icon icon="ph:map-pin" style="font-size: 28px;" />
                <p>Aucun lieu proposé par cet utilisateur pour le moment.</p>
              </div>
            </div>

            <!-- TAB 3: RECENT MESSAGES -->
            <div v-else-if="profileModal.activeTab === 'messages'" class="dossier-tab-pane">
              <div v-if="profileModal.data.recent_messages?.length > 0" class="dossier-list">
                <div v-for="m in profileModal.data.recent_messages" :key="m.id" class="dossier-item-card">
                  <div class="dossier-item-left">
                    <div class="dossier-item-icon" style="background: rgba(59,130,246,0.15); color: #60a5fa;">
                      <Icon icon="ph:chat-text-fill" />
                    </div>
                    <div class="flex-1">
                      <p class="dossier-msg-text">Message #{{ m.id }} · Salon / Conversation {{ m.conversation_id || 'Général' }}</p>
                      <span class="dossier-item-sub">{{ formatDateTime(m.created_at) }}</span>
                    </div>
                  </div>
                  <button @click="confirmDeleteMessage(m.id)" class="action-btn btn-delete" title="Supprimer ce message">
                    <Icon icon="ph:trash-fill" />
                  </button>
                </div>
              </div>
              <div v-else class="empty-state-small">
                <Icon icon="ph:chat-slash" style="font-size: 28px;" />
                <p>Aucun message récent trouvé pour cet utilisateur.</p>
              </div>
            </div>

            <!-- TAB 4: REPORTS RECEIVED & SENT -->
            <div v-else-if="profileModal.activeTab === 'reports'" class="dossier-tab-pane">
              <div class="reports-split-view">
                <!-- Received -->
                <div class="reports-col">
                  <h4 class="section-subtitle text-red">
                    <Icon icon="ph:warning-circle-bold" />
                    Signalements Reçus ({{ profileModal.data.reports_received?.length || 0 }})
                  </h4>
                  <div v-if="profileModal.data.reports_received?.length > 0" class="dossier-list">
                    <div v-for="rep in profileModal.data.reports_received" :key="rep.id" class="dossier-item-card">
                      <div>
                        <div class="flex-items-center gap-2">
                          <span class="status-chip" :class="'chip-' + rep.status">{{ formatReportStatus(rep.status) }}</span>
                          <span class="dossier-item-sub">Par {{ rep.reporter?.name || 'Anonyme' }}</span>
                        </div>
                        <p class="dossier-report-reason">{{ rep.reason }}</p>
                      </div>
                    </div>
                  </div>
                  <p v-else class="no-sanctions-msg">Aucun signalement reçu.</p>
                </div>

                <!-- Sent -->
                <div class="reports-col">
                  <h4 class="section-subtitle text-blue">
                    <Icon icon="ph:flag-bold" />
                    Signalements Émis ({{ profileModal.data.reports_sent?.length || 0 }})
                  </h4>
                  <div v-if="profileModal.data.reports_sent?.length > 0" class="dossier-list">
                    <div v-for="rep in profileModal.data.reports_sent" :key="rep.id" class="dossier-item-card">
                      <div>
                        <div class="flex-items-center gap-2">
                          <span class="status-chip" :class="'chip-' + rep.status">{{ formatReportStatus(rep.status) }}</span>
                          <span class="dossier-item-sub">Cible : {{ rep.reported_user?.name || 'Inconnu' }}</span>
                        </div>
                        <p class="dossier-report-reason">{{ rep.reason }}</p>
                      </div>
                    </div>
                  </div>
                  <p v-else class="no-sanctions-msg">Aucun signalement émis.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer Quick Actions -->
          <div class="modal-actions modal-actions--split">
            <div class="flex-items-center gap-2">
              <button @click="profileModal.visible = false" class="modal-btn modal-btn--cancel">
                Fermer
              </button>
              <button
                v-if="profileModal.data?.user"
                @click="openResetPasswordModal(profileModal.data.user)"
                class="modal-btn modal-btn--secondary"
                title="Générer un mot de passe temporaire"
              >
                <Icon icon="ph:key-bold" />
                Réinitialiser MDP
              </button>
              <button
                v-if="profileModal.data?.user"
                @click="confirmForceLogout(profileModal.data.user)"
                class="modal-btn modal-btn--secondary"
                title="Déconnecter immédiatement toutes les sessions"
              >
                <Icon icon="ph:sign-out-bold" />
                Déconnexion forcée
              </button>
            </div>
            <div class="flex-items-center gap-2">
              <button
                v-if="profileModal.data?.user"
                @click="openDirectSanctionModal(profileModal.data.user)"
                class="modal-btn modal-btn--danger"
              >
                <Icon icon="ph:gavel-fill" />
                Sanctionner
              </button>
              <button
                v-if="profileModal.data?.user?.is_banned || profileModal.data?.user?.muted_until || profileModal.data?.user?.suspended_until || profileModal.data?.user?.is_restricted"
                @click="confirmUnbanFromModal(profileModal.data.user)"
                class="modal-btn modal-btn--unlock"
              >
                <Icon icon="ph:shield-slash-fill" />
                Lever les sanctions
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL : RÉINITIALISATION DE MOT DE PASSE (PHASE 3)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="resetPasswordModal.visible" class="modal-overlay" @click.self="resetPasswordModal.visible = false">
        <div class="modal-card reset-pwd-card">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon" style="background: rgba(245,158,11,0.2); color: #fbbf24;">
                <Icon icon="ph:key-bold" />
              </div>
              <div>
                <h3 class="modal-title">Réinitialiser le Mot de Passe</h3>
                <p class="modal-sub">Attribution d'un nouveau mot de passe pour {{ resetPasswordModal.user?.name }}</p>
              </div>
            </div>
            <button @click="resetPasswordModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <div class="modal-body-content">
            <div v-if="!resetPasswordModal.generatedPassword">
              <p class="reset-desc">
                Vous pouvez saisir un nouveau mot de passe spécifique ou laisser le champ vide pour en générer un automatiquement de manière sécurisée. Les sessions actives de l'utilisateur seront automatiquement révoquées.
              </p>
              <div class="form-group" style="margin-top: 14px;">
                <label class="form-label">Nouveau mot de passe (optionnel)</label>
                <input
                  v-model="resetPasswordModal.newPassword"
                  type="text"
                  class="form-input"
                  placeholder="Laisser vide pour auto-générer..."
                />
              </div>
            </div>

            <!-- Result Box -->
            <div v-else class="pwd-result-container">
              <div class="pwd-result-head">
                <Icon icon="ph:check-circle-fill" style="color: #4ade80; font-size: 20px;" />
                <span>Nouveau mot de passe temporaire défini :</span>
              </div>
              <div class="pwd-result-box">
                <code class="pwd-code">{{ resetPasswordModal.generatedPassword }}</code>
                <button @click="copyPasswordToClipboard" class="pwd-copy-btn">
                  <Icon :icon="resetPasswordModal.copied ? 'ph:check-bold' : 'ph:copy-bold'" />
                  {{ resetPasswordModal.copied ? 'Copié !' : 'Copier' }}
                </button>
              </div>
              <p class="pwd-hint">
                Transmettez ce mot de passe de manière sécurisée à l'étudiant. Il sera invité à le changer dès sa prochaine connexion.
              </p>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="resetPasswordModal.visible = false" class="modal-btn modal-btn--cancel">
              {{ resetPasswordModal.generatedPassword ? 'Fermer' : 'Annuler' }}
            </button>
            <button
              v-if="!resetPasswordModal.generatedPassword"
              @click="submitResetPassword"
              :disabled="resetPasswordModal.submitting"
              class="modal-btn modal-btn--warning"
            >
              <Icon :icon="resetPasswordModal.submitting ? 'ph:spinner-gap-bold' : 'ph:key-fill'" :class="{ 'spinner-animate': resetPasswordModal.submitting }" />
              Confirmer la réinitialisation
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL : ÉDITION DE LIEU (PHASE 2)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="editPlaceModal.visible" class="modal-overlay" @click.self="editPlaceModal.visible = false">
        <div class="modal-card modal-card--lg">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon" style="background: rgba(99,102,241,0.2); color: #a5b4fc;">
                <Icon icon="ph:pencil-simple-fill" />
              </div>
              <div>
                <h3 class="modal-title">Éditer le Lieu</h3>
                <p class="modal-sub">Modifier les informations, la visibilité et la description IA</p>
              </div>
            </div>
            <button @click="editPlaceModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <div class="edit-place-body custom-scrollbar">
            <!-- Name -->
            <div class="form-group">
              <label class="form-label">Nom du lieu</label>
              <input
                v-model="editPlaceModal.form.name"
                type="text"
                class="form-input"
                placeholder="Ex: Amphithéâtre Houdégbé"
              />
            </div>

            <!-- Category & Status side by side -->
            <div class="form-row-2">
              <div class="form-group">
                <label class="form-label">Catégorie</label>
                <select v-model="editPlaceModal.form.category" class="custom-select full-width">
                  <option value="amphi">Amphithéâtres & Salles</option>
                  <option value="studies">Facultés & Départements</option>
                  <option value="library">Bibliothèques & Étude</option>
                  <option value="food">Restauration & Cafés</option>
                  <option value="housing">Résidences & Logements</option>
                  <option value="admin">Administration & Services</option>
                  <option value="health">Santé & Urgences</option>
                  <option value="other">Autres points d'intérêt</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Statut de visibilité</label>
                <select v-model="editPlaceModal.form.status" class="custom-select full-width">
                  <option value="approved">✅ Approuvé (visible sur la carte)</option>
                  <option value="pending">⏳ En attente de validation</option>
                  <option value="hidden">🚫 Masqué (invisible de la carte)</option>
                </select>
              </div>
            </div>

            <!-- Description + AI Generate button -->
            <div class="form-group">
              <div class="form-label-row">
                <label class="form-label">Description</label>
                <button
                  @click="regenerateDescription"
                  class="ai-generate-btn"
                  :disabled="editPlaceModal.regenerating"
                >
                  <Icon :icon="editPlaceModal.regenerating ? 'ph:spinner-gap-bold' : 'ph:sparkle-fill'" :class="{ 'spinner-animate': editPlaceModal.regenerating }" />
                  {{ editPlaceModal.regenerating ? 'Génération IA...' : '✨ Régénérer avec Groq AI' }}
                </button>
              </div>
              <textarea
                v-model="editPlaceModal.form.description"
                rows="4"
                placeholder="Description attractive du lieu..."
                class="form-textarea"
              ></textarea>
              <p v-if="editPlaceModal.aiPreview" class="ai-preview-label">
                <Icon icon="ph:sparkle-fill" style="color: #fbbf24;" />
                Proposition IA (cliquez pour appliquer) :
              </p>
              <div
                v-if="editPlaceModal.aiPreview"
                @click="applyAiDescription"
                class="ai-preview-box"
              >
                {{ editPlaceModal.aiPreview }}
              </div>
            </div>

            <!-- Coordinates side by side -->
            <div class="form-row-2">
              <div class="form-group">
                <label class="form-label">Latitude</label>
                <input
                  v-model.number="editPlaceModal.form.latitude"
                  type="number"
                  step="0.000001"
                  class="form-input"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Longitude</label>
                <input
                  v-model.number="editPlaceModal.form.longitude"
                  type="number"
                  step="0.000001"
                  class="form-input"
                />
              </div>
            </div>

            <!-- Image URL -->
            <div class="form-group">
              <label class="form-label">URL de l'image (optionnel)</label>
              <input
                v-model="editPlaceModal.form.image_url"
                type="url"
                class="form-input"
                placeholder="https://..."
              />
            </div>

            <!-- Opening Hours -->
            <div class="form-group">
              <label class="form-label">Horaires d'ouverture (optionnel)</label>
              <input
                v-model="editPlaceModal.form.opening_hours"
                type="text"
                class="form-input"
                placeholder="Ex: Lun-Ven 8h-18h, Sam 8h-12h"
              />
            </div>
          </div>

          <div class="modal-actions">
            <button @click="editPlaceModal.visible = false" class="modal-btn modal-btn--cancel">
              Annuler
            </button>
            <button
              @click="submitEditPlace"
              :disabled="editPlaceModal.saving || !editPlaceModal.form.name?.trim()"
              class="modal-btn modal-btn--primary"
            >
              <Icon :icon="editPlaceModal.saving ? 'ph:spinner-gap-bold' : 'ph:floppy-disk-bold'" :class="{ 'spinner-animate': editPlaceModal.saving }" />
              Enregistrer les modifications
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         MODAL : DÉTAIL D'UN LOG & STACK TRACE (PHASE 5)
    ══════════════════════════════════════════════════════ -->
    <transition name="modal">
      <div v-if="logDetailModal.visible" class="modal-overlay" @click.self="logDetailModal.visible = false">
        <div class="modal-card modal-card--lg log-detail-modal-card">
          <div class="modal-head">
            <div class="flex-items-center gap-2">
              <div class="modal-head-icon" :class="'log-modal-icon--' + (logDetailModal.log?.level || 'info')">
                <Icon icon="ph:terminal-window-bold" />
              </div>
              <div>
                <h3 class="modal-title">Détail du Journal Applicatif</h3>
                <p class="modal-sub">
                  {{ logDetailModal.log?.timestamp }} · [{{ logDetailModal.log?.environment }}] · 
                  <span class="log-level-chip" :class="'chip-level-' + logDetailModal.log?.level">
                    {{ logDetailModal.log?.level?.toUpperCase() }}
                  </span>
                </p>
              </div>
            </div>
            <button @click="logDetailModal.visible = false" class="modal-close-btn">
              <Icon icon="ph:x-bold" />
            </button>
          </div>

          <div class="log-detail-body custom-scrollbar">
            <!-- Message -->
            <div class="form-group mb-3">
              <label class="form-label">Message d'erreur / Événement :</label>
              <div class="log-msg-fullbox">
                {{ logDetailModal.log?.message }}
              </div>
            </div>

            <!-- Stack Trace -->
            <div v-if="logDetailModal.log?.stack_trace" class="form-group">
              <div class="flex-between mb-1">
                <label class="form-label">Trace d'Exécution Complète (Stack Trace) :</label>
                <button @click="copyLogStackTrace" class="copy-trace-btn">
                  <Icon :icon="logDetailModal.copied ? 'ph:check-bold' : 'ph:copy-bold'" />
                  {{ logDetailModal.copied ? 'Copié !' : 'Copier la trace' }}
                </button>
              </div>
              <pre class="log-stack-pre custom-scrollbar">{{ logDetailModal.log.stack_trace }}</pre>
            </div>
          </div>

          <div class="modal-actions">
            <button @click="logDetailModal.visible = false" class="modal-btn modal-btn--cancel">
              Fermer
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ══════════════════════════════════════════════════════
         CONFIRMATION MODAL (Standard)
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
import { useMeta } from '../composables/useMeta'

useMeta('Tableau de Bord Admin', 'Administration et modération de la plateforme U-map.', { canonicalPath: '/admin/dashboard' })

const router = useRouter()
const currentTab = ref('dashboard')
const loading = ref(true)
const mobileMenuOpen = ref(false)

const stats = ref(null)
const users = ref([])
const places = ref([])
const messages = ref([])
const reports = ref([])
const auditLogs = ref([])

// User filters (Phase 3)
const userSearch = ref('')
const userFilter = ref('all')
const userRoleFilter = ref('all')
const page = ref(1)
const PER_PAGE = 8

// Reset Password Modal (Phase 3)
const resetPasswordModal = ref({
  visible: false,
  submitting: false,
  user: null,
  newPassword: '',
  generatedPassword: '',
  copied: false
})

// Report filters
const reportStatusFilter = ref('pending')
const reportPriorityFilter = ref('all')
const reportTypeFilter = ref('all')

// Place filters & state (Phase 2)
const placesSearch = ref('')
const placesStatusFilter = ref('all')
const placesCategoryFilter = ref('all')
const placesPage = ref(1)
const duplicatesList = ref([])
const duplicatesLoading = ref(false)

const editPlaceModal = ref({
  visible: false,
  saving: false,
  regenerating: false,
  aiPreview: '',
  placeId: null,
  form: {
    name: '',
    category: 'other',
    status: 'approved',
    description: '',
    latitude: '',
    longitude: '',
    image_url: '',
    opening_hours: ''
  }
})

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
    id: 'analytics',
    label: 'Statistiques & KPIs',
    icon: 'ph:chart-line-up',
    iconFill: 'ph:chart-line-up-fill',
    badge: null
  },
  {
    id: 'reports',
    label: 'Signalements',
    icon: 'ph:flag',
    iconFill: 'ph:flag-fill',
    badge: () => pendingReportsCount.value,
    badgeClass: 'badge-red'
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
    id: 'audit',
    label: "Journal d'Audit",
    icon: 'ph:clock-counter-clockwise',
    iconFill: 'ph:clock-counter-clockwise-fill',
    badge: null
  },
  {
    id: 'system',
    label: 'Supervision & Santé',
    icon: 'ph:cpu',
    iconFill: 'ph:cpu-fill',
    badge: () => systemHealthData.value?.status === 'degraded' ? '!' : null,
    badgeClass: 'badge-red'
  },
  {
    id: 'ai_campus',
    label: 'Assistant IA Campus',
    icon: 'ph:sparkle',
    iconFill: 'ph:sparkle-fill',
    badge: null
  }
])

const tabTitles = {
  dashboard: "Vue d'ensemble",
  analytics: "Statistiques & Analytics Avancés",
  reports: "Modération & Signalements",
  users: "Gestion des Utilisateurs",
  places: "Lieux & Cartographie",
  messages: "Logs Messagerie",
  audit: "Journal d'Audit",
  system: "Supervision Technique & Santé Système",
  ai_campus: "Assistant IA & Suggestions Campus"
}

// ─── COMPUTED ───────────────────────────────────
const pendingPlacesList = computed(() => places.value.filter(p => p.status === 'pending'))
const approvedPlacesList = computed(() => places.value.filter(p => p.status === 'approved'))
const hiddenPlacesList = computed(() => places.value.filter(p => p.status === 'hidden'))

const filteredPlaces = computed(() => {
  let list = places.value
  if (placesStatusFilter.value !== 'all' && placesStatusFilter.value !== 'duplicates') {
    list = list.filter(p => p.status === placesStatusFilter.value)
  }
  if (placesCategoryFilter.value !== 'all') {
    list = list.filter(p => p.category === placesCategoryFilter.value)
  }
  if (placesSearch.value.trim()) {
    const q = placesSearch.value.toLowerCase()
    list = list.filter(p =>
      p.name?.toLowerCase().includes(q) ||
      p.description?.toLowerCase().includes(q) ||
      p.category?.toLowerCase().includes(q)
    )
  }
  return list
})

const totalPlacesPages = computed(() => Math.max(1, Math.ceil(filteredPlaces.value.length / PER_PAGE)))
const paginatedPlaces = computed(() => {
  const s = (placesPage.value - 1) * PER_PAGE
  return filteredPlaces.value.slice(s, s + PER_PAGE)
})

const pendingReportsCount = computed(() => reports.value.filter(r => r.status === 'pending').length)

const reportStatusTabs = computed(() => [
  { id: 'pending', label: 'En attente', icon: 'ph:warning-circle', count: () => reports.value.filter(r => r.status === 'pending').length, badgeClass: 'badge-red' },
  { id: 'in_progress', label: 'En cours', icon: 'ph:hourglass-medium', count: () => reports.value.filter(r => r.status === 'in_progress').length, badgeClass: 'badge-blue' },
  { id: 'resolved', label: 'Résolus', icon: 'ph:check-circle', count: () => reports.value.filter(r => r.status === 'resolved').length },
  { id: 'dismissed', label: 'Rejetés', icon: 'ph:x-circle', count: () => reports.value.filter(r => r.status === 'dismissed').length },
  { id: 'all', label: 'Tous', icon: 'ph:list-bullets', count: () => reports.value.length }
])

const filteredReports = computed(() => {
  let list = reports.value
  if (reportStatusFilter.value !== 'all') {
    list = list.filter(r => r.status === reportStatusFilter.value)
  }
  if (reportPriorityFilter.value !== 'all') {
    list = list.filter(r => r.priority === reportPriorityFilter.value)
  }
  if (reportTypeFilter.value !== 'all') {
    list = list.filter(r => r.reportable_type === reportTypeFilter.value)
  }
  return list
})

const filteredUsers = computed(() => {
  let list = users.value
  if (userFilter.value === 'active') list = list.filter(u => !u.is_restricted && !u.is_banned)
  if (userFilter.value === 'restricted') list = list.filter(u => u.is_restricted || u.is_banned || u.muted_until || u.suspended_until)
  if (userRoleFilter.value !== 'all') list = list.filter(u => (u.role || 'user') === userRoleFilter.value)
  if (userSearch.value.trim()) {
    const q = userSearch.value.toLowerCase()
    list = list.filter(u =>
      u.name?.toLowerCase().includes(q) ||
      u.email?.toLowerCase().includes(q) ||
      u.student_id?.toLowerCase().includes(q) ||
      u.faculty?.toLowerCase().includes(q)
    )
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
  { id: 'active', label: 'Actifs', icon: 'ph:check-circle', count: () => users.value.filter(u => !u.is_restricted && !u.is_banned).length },
  { id: 'restricted', label: 'Sanctionnés / Restreints', icon: 'ph:lock', count: () => users.value.filter(u => u.is_restricted || u.is_banned || u.muted_until || u.suspended_until).length }
])

const statCards = computed(() => {
  return [
    {
      icon: 'ph:flag-fill',
      value: pendingReportsCount.value,
      label: 'Signalements en attente',
      alert: pendingReportsCount.value > 0,
      color: pendingReportsCount.value > 0 ? 'card-red' : 'card-gray',
      fill: Math.min(pendingReportsCount.value / 5 * 100, 100),
      onClick: () => setTab('reports')
    },
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

// ─── MODALS STATE ─────────────────────────────
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

const toast = ref({ visible: false, message: '', type: 'success' })
let toastTimer = null
const showToast = (message, type = 'success') => {
  if (toastTimer) clearTimeout(toastTimer)
  toast.value = { visible: true, message, type }
  toastTimer = setTimeout(() => { toast.value.visible = false }, 3500)
}

// ─── CONTEXT MODAL STATE ──────────────────────
const contextModal = ref({
  visible: false,
  loading: false,
  report: null,
  targetMessage: null,
  messages: []
})

const openContextModal = async (report) => {
  contextModal.value = {
    visible: true,
    loading: true,
    report,
    targetMessage: null,
    messages: []
  }
  try {
    const data = await adminService.getReportContext(report.id)
    contextModal.value.targetMessage = data.target_message
    contextModal.value.messages = data.context_messages || []
  } catch (e) {
    showToast('Erreur lors du chargement du contexte', 'error')
  } finally {
    contextModal.value.loading = false
  }
}

// ─── SANCTION MODAL STATE ─────────────────────
const sanctionModal = ref({
  visible: false,
  submitting: false,
  user: null,
  reportId: null,
  type: 'warning',
  durationHours: 24,
  reason: '',
  autoResolveReport: true
})

const sanctionOptions = [
  { id: 'warning', title: 'Avertissement', desc: 'Notification officielle transmise à l\'utilisateur', icon: 'ph:warning-fill', color: 'orange' },
  { id: 'mute', title: 'Mute Temporaire', desc: 'Bloque l\'envoi de messages pendant la durée choisie', icon: 'ph:speaker-simple-slash-fill', color: 'purple' },
  { id: 'suspension', title: 'Suspension de Compte', desc: 'Interdit toute connexion à l\'application', icon: 'ph:pause-circle-fill', color: 'red' },
  { id: 'ban', title: 'Bannissement Définitif', desc: 'Exclusion irréversible de la plateforme', icon: 'ph:prohibit-fill', color: 'black' }
]

const openSanctionModal = (report) => {
  sanctionModal.value = {
    visible: true,
    submitting: false,
    user: report.reported_user,
    reportId: report.id,
    type: 'warning',
    durationHours: 24,
    reason: report.reason ? `Suite à signalement : ${report.reason}` : '',
    autoResolveReport: true
  }
}

const openDirectSanctionModal = (user) => {
  sanctionModal.value = {
    visible: true,
    submitting: false,
    user: user,
    reportId: null,
    type: 'warning',
    durationHours: 24,
    reason: '',
    autoResolveReport: false
  }
}

const submitSanction = async () => {
  if (!sanctionModal.value.reason.trim()) {
    showToast('Veuillez renseigner un motif.', 'error')
    return
  }

  sanctionModal.value.submitting = true
  const { user, reportId, type, durationHours, reason, autoResolveReport } = sanctionModal.value

  try {
    let res
    const payload = {
      user_id: user.id,
      reason,
      report_id: autoResolveReport ? reportId : null
    }

    if (type === 'warning') {
      res = await adminService.warnUser(payload)
    } else if (type === 'mute') {
      res = await adminService.muteUser({ ...payload, duration_hours: durationHours })
    } else if (type === 'suspension') {
      res = await adminService.suspendUser({ ...payload, duration_hours: durationHours })
    } else if (type === 'ban') {
      res = await adminService.banUser(payload)
    }

    showToast(res.message || 'Sanction appliquée avec succès.')
    sanctionModal.value.visible = false
    if (contextModal.value.visible) contextModal.value.visible = false

    await loadData()
  } catch (e) {
    showToast(e.message || 'Erreur lors de l\'application de la sanction', 'error')
  } finally {
    sanctionModal.value.submitting = false
  }
}

// ─── USER PROFILE / DOSSIER MODAL (PHASE 3) ───
const profileModal = ref({
  visible: false,
  loading: false,
  savingNote: false,
  updatingRole: false,
  activeTab: 'sanctions',
  selectedRole: 'user',
  userId: null,
  data: null,
  adminNote: ''
})

const openUserProfileModal = async (userId) => {
  profileModal.value = {
    visible: true,
    loading: true,
    savingNote: false,
    updatingRole: false,
    activeTab: 'sanctions',
    selectedRole: 'user',
    userId,
    data: null,
    adminNote: ''
  }
  try {
    const res = await adminService.getUserDossier(userId)
    profileModal.value.data = res
    profileModal.value.adminNote = res.user.admin_note || ''
    profileModal.value.selectedRole = res.user.role || 'user'
  } catch (e) {
    showToast('Impossible de charger le dossier utilisateur', 'error')
  } finally {
    profileModal.value.loading = false
  }
}

const submitUpdateRole = async () => {
  if (!profileModal.value.userId) return
  profileModal.value.updatingRole = true
  try {
    const res = await adminService.updateUserRole(profileModal.value.userId, profileModal.value.selectedRole)
    showToast(res.message || 'Rôle mis à jour avec succès.')
    if (profileModal.value.data?.user) {
      profileModal.value.data.user.role = profileModal.value.selectedRole
    }
    const u = users.value.find(user => user.id === profileModal.value.userId)
    if (u) u.role = profileModal.value.selectedRole
  } catch (e) {
    showToast(e.message || 'Erreur lors de la mise à jour du rôle', 'error')
  } finally {
    profileModal.value.updatingRole = false
  }
}

const saveUserAdminNote = async () => {
  if (!profileModal.value.userId) return
  profileModal.value.savingNote = true
  try {
    await adminService.updateUserNote(profileModal.value.userId, profileModal.value.adminNote)
    showToast('Note interne enregistrée.')
    const u = users.value.find(user => user.id === profileModal.value.userId)
    if (u) u.admin_note = profileModal.value.adminNote
  } catch (e) {
    showToast('Erreur d\'enregistrement de la note', 'error')
  } finally {
    profileModal.value.savingNote = false
  }
}

// ─── RESET PASSWORD & FORCE LOGOUT (PHASE 3) ──
const openResetPasswordModal = (user) => {
  resetPasswordModal.value = {
    visible: true,
    submitting: false,
    user,
    newPassword: '',
    generatedPassword: '',
    copied: false
  }
}

const submitResetPassword = async () => {
  const { user, newPassword } = resetPasswordModal.value
  if (!user) return
  resetPasswordModal.value.submitting = true
  try {
    const res = await adminService.resetUserPassword(user.id, newPassword || null)
    resetPasswordModal.value.generatedPassword = res.temp_password
    showToast('Mot de passe réinitialisé avec succès !')
  } catch (e) {
    showToast(e.message || 'Erreur lors de la réinitialisation', 'error')
  } finally {
    resetPasswordModal.value.submitting = false
  }
}

const copyPasswordToClipboard = async () => {
  if (!resetPasswordModal.value.generatedPassword) return
  try {
    await navigator.clipboard.writeText(resetPasswordModal.value.generatedPassword)
    resetPasswordModal.value.copied = true
    showToast('Mot de passe copié dans le presse-papier !')
    setTimeout(() => { resetPasswordModal.value.copied = false }, 3000)
  } catch (e) {
    showToast('Impossible de copier automatiquement', 'error')
  }
}

const confirmForceLogout = (user) => {
  showModal({
    title: 'Déconnexion forcée',
    body: `Voulez-vous révoquer immédiatement toutes les sessions actives de "${user.name}" ? L'utilisateur devra se reconnecter.`,
    icon: 'ph:sign-out-bold',
    type: 'warning',
    confirmLabel: 'Déconnecter toutes les sessions',
    confirm: async () => {
      modal.value.visible = false
      try {
        const res = await adminService.forceLogoutUser(user.id)
        showToast(res.message || 'Toutes les sessions ont été révoquées.')
      } catch (e) {
        showToast(e.message || 'Erreur lors de la déconnexion', 'error')
      }
    }
  })
}

const resetUserFilters = () => {
  userSearch.value = ''
  userFilter.value = 'all'
  userRoleFilter.value = 'all'
  page.value = 1
}

const confirmUnban = (user) => {
  showModal({
    title: 'Lever toutes les sanctions',
    body: `Voulez-vous restaurer l'accès complet de "${user.name}" (retrait du ban, du mute et des suspensions) ?`,
    icon: 'ph:shield-slash-fill',
    type: 'warning',
    confirmLabel: 'Lever les sanctions',
    confirm: async () => {
      modal.value.visible = false
      try {
        const res = await adminService.unbanUser(user.id)
        showToast(res.message)
        await loadData()
      } catch (e) {
        showToast(e.message, 'error')
      }
    }
  })
}

const confirmUnbanFromModal = (user) => {
  profileModal.value.visible = false
  confirmUnban(user)
}

// ─── MESSAGE DELETION ─────────────────────────
const confirmDeleteMessage = (id) => {
  showModal({
    title: 'Supprimer le message litigieux',
    body: 'Voulez-vous supprimer définitivement ce message ? Cette action sera consignée dans le journal d\'audit.',
    icon: 'ph:trash-fill',
    type: 'danger',
    confirmLabel: 'Supprimer le message',
    confirm: async () => {
      modal.value.visible = false
      try {
        await adminService.deleteMessage(id)
        messages.value = messages.value.filter(m => m.id !== id)
        if (contextModal.value.messages) {
          contextModal.value.messages = contextModal.value.messages.filter(m => m.id !== id)
        }
        showToast('Message supprimé avec succès.')
      } catch (e) {
        showToast(e.message, 'error')
      }
    }
  })
}

// ─── STATUS UPDATES ───────────────────────────
const handleQuickStatusChange = async (report, newStatus) => {
  try {
    await adminService.updateReportStatus(report.id, { status: newStatus })
    report.status = newStatus
    showToast('Statut du signalement mis à jour.')
  } catch (e) {
    showToast(e.message, 'error')
  }
}

const resetReportFilters = () => {
  reportStatusFilter.value = 'all'
  reportPriorityFilter.value = 'all'
  reportTypeFilter.value = 'all'
}

// ─── AUDIT LOGS ───────────────────────────────
const loadAuditLogs = async () => {
  try {
    const res = await adminService.getAuditLogs()
    auditLogs.value = res.data || []
  } catch (e) {
    showToast('Erreur lors du chargement des logs', 'error')
  }
}

// ─── INIT ─────────────────────────────────────
onMounted(async () => {
  const tick = () => {
    currentTime.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
  }
  tick()
  timeInterval = setInterval(tick, 30000)

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

// ─── ANALYTICS STATE & HELPERS (PHASE 4) ───────
const analyticsPeriod = ref('30d')
const analyticsLoading = ref(false)
const analyticsData = ref(null)

const changeAnalyticsPeriod = async (period) => {
  analyticsPeriod.value = period
  await loadAnalytics(period)
}

const loadAnalytics = async (period = '30d') => {
  analyticsLoading.value = true
  try {
    const res = await adminService.getAnalytics(period)
    analyticsData.value = res
  } catch (e) {
    showToast('Erreur lors du chargement des analytics', 'error')
  } finally {
    analyticsLoading.value = false
  }
}

const combinedTimeline = computed(() => {
  if (!analyticsData.value) return []
  const userMap = {}
  const msgMap = {}
  ;(analyticsData.value.daily_users || []).forEach(u => { userMap[u.date] = u.count })
  ;(analyticsData.value.daily_messages || []).forEach(m => { msgMap[m.date] = m.count })

  const allDates = Array.from(new Set([...Object.keys(userMap), ...Object.keys(msgMap)])).sort()
  return allDates.map(date => ({
    date,
    users: userMap[date] || 0,
    messages: msgMap[date] || 0
  }))
})

const maxTimelineVal = computed(() => {
  if (combinedTimeline.value.length === 0) return 10
  const maxU = Math.max(...combinedTimeline.value.map(t => t.users), 0)
  const maxM = Math.max(...combinedTimeline.value.map(t => t.messages), 0)
  return Math.max(maxU, maxM, 5)
})

const totalSanctionsCount = computed(() => {
  if (!analyticsData.value?.sanctions_by_type) return 1
  return analyticsData.value.sanctions_by_type.reduce((acc, s) => acc + s.count, 0) || 1
})

const placesStatusCount = (status) => {
  if (!analyticsData.value?.places_by_status) return 0
  const found = analyticsData.value.places_by_status.find(p => p.status === status)
  return found ? found.count : 0
}

const getBarHeight = (val, max) => {
  if (!val || !max) return 4
  return Math.max(6, Math.round((val / max) * 100))
}

const getPercentage = (val, total) => {
  if (!val || !total) return 0
  return Math.min(100, Math.round((val / total) * 100))
}

const formatChartDate = (d) => {
  if (!d) return ''
  const parts = d.split('-')
  return parts.length === 3 ? `${parts[2]}/${parts[1]}` : d
}

const formatDuration = (mins) => {
  if (!mins || mins <= 0) return '< 1 min'
  if (mins < 60) return `${mins} min`
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return m > 0 ? `${h}h ${m}m` : `${h}h`
}

const loadData = async () => {
  loading.value = true
  try {
    const s = await adminService.getStats()
    stats.value = s

    // Fetch tab data on demand based on currentTab
    if (currentTab.value === 'analytics') {
      await loadAnalytics(analyticsPeriod.value)
    } else if (currentTab.value === 'users') {
      users.value = await adminService.getUsers()
    } else if (currentTab.value === 'places') {
      places.value = await adminService.getPlaces()
    } else if (currentTab.value === 'reports') {
      reports.value = await adminService.getReports()
    } else if (currentTab.value === 'messages') {
      messages.value = await adminService.getMessages()
    } else if (currentTab.value === 'audit') {
      const a = await adminService.getAuditLogs()
      auditLogs.value = a.data || []
    } else if (currentTab.value === 'system') {
      await Promise.all([loadSystemHealth(), loadSystemLogs()])
    } else if (currentTab.value === 'ai_campus') {
      await runCampusAudit()
    } else {
      // Dashboard tab: load places and reports for badges
      const [p, r] = await Promise.all([
        adminService.getPlaces(),
        adminService.getReports()
      ])
      places.value = p
      reports.value = r
    }
  } catch (e) {
    showToast('Erreur lors du chargement des données', 'error')
  } finally {
    loading.value = false
  }
}

const setTab = async (tab) => {
  currentTab.value = tab
  mobileMenuOpen.value = false
  page.value = 1

  try {
    if (tab === 'analytics') {
      if (!analyticsData.value) await loadAnalytics(analyticsPeriod.value)
    } else if (tab === 'users') {
      if (users.value.length === 0) users.value = await adminService.getUsers()
    } else if (tab === 'places') {
      if (places.value.length === 0) places.value = await adminService.getPlaces()
    } else if (tab === 'reports') {
      if (reports.value.length === 0) reports.value = await adminService.getReports()
    } else if (tab === 'messages') {
      if (messages.value.length === 0) messages.value = await adminService.getMessages()
    } else if (tab === 'audit') {
      if (auditLogs.value.length === 0) {
        const a = await adminService.getAuditLogs()
        auditLogs.value = a.data || []
      }
    } else if (tab === 'system') {
      if (!systemHealthData.value) loadSystemHealth()
      if (!systemLogsData.value) loadSystemLogs()
    } else if (tab === 'ai_campus') {
      if (!campusAuditData.value) runCampusAudit()
    }
  } catch (e) {
    // Non-blocking tab switch
  }
}

// ─── STANDARD ACTIONS ─────────────────────────
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

// ─── PHASE 2: PLACES & AI METHODS ─────────────
const openEditPlaceModal = (place) => {
  editPlaceModal.value = {
    visible: true,
    saving: false,
    regenerating: false,
    aiPreview: '',
    placeId: place.id,
    form: {
      name: place.name || '',
      category: place.category || 'other',
      status: place.status || 'approved',
      description: place.description || '',
      latitude: place.latitude != null ? place.latitude : '',
      longitude: place.longitude != null ? place.longitude : '',
      image_url: place.image_url || '',
      opening_hours: place.opening_hours || ''
    }
  }
}

const submitEditPlace = async () => {
  const { placeId, form } = editPlaceModal.value
  if (!form.name?.trim()) {
    showToast('Le nom du lieu est requis', 'error')
    return
  }
  editPlaceModal.value.saving = true
  try {
    const res = await adminService.updatePlace(placeId, form)
    const idx = places.value.findIndex(p => p.id === placeId)
    if (idx !== -1 && res.place) {
      places.value[idx] = { ...places.value[idx], ...res.place }
    }
    showToast('Lieu mis à jour avec succès')
    editPlaceModal.value.visible = false
  } catch (e) {
    showToast(e.message || 'Erreur lors de la mise à jour', 'error')
  } finally {
    editPlaceModal.value.saving = false
  }
}

const regenerateDescription = async () => {
  const { placeId } = editPlaceModal.value
  if (!placeId) return
  editPlaceModal.value.regenerating = true
  try {
    const res = await adminService.regeneratePlaceDescription(placeId, false)
    editPlaceModal.value.aiPreview = res.description
    showToast('✨ Description générée par IA ! Cliquez pour l\'appliquer.')
  } catch (e) {
    showToast(e.message || 'Erreur de génération IA', 'error')
  } finally {
    editPlaceModal.value.regenerating = false
  }
}

const applyAiDescription = () => {
  if (editPlaceModal.value.aiPreview) {
    editPlaceModal.value.form.description = editPlaceModal.value.aiPreview
    editPlaceModal.value.aiPreview = ''
    showToast('Description IA appliquée.')
  }
}

const togglePlaceVisibility = async (place, newStatus) => {
  try {
    const res = await adminService.updatePlaceVisibility(place.id, newStatus)
    place.status = newStatus
    showToast(res.message || 'Visibilité mise à jour')
  } catch (e) {
    showToast(e.message || 'Erreur lors du changement de visibilité', 'error')
  }
}

const loadDuplicates = async () => {
  duplicatesLoading.value = true
  placesStatusFilter.value = 'duplicates'
  try {
    const res = await adminService.getDuplicatePlaces()
    duplicatesList.value = res.duplicates || []
    showToast(`${duplicatesList.value.length} paire(s) de doublons potentiels identifiée(s)`)
  } catch (e) {
    showToast('Erreur lors de l\'analyse des doublons', 'error')
  } finally {
    duplicatesLoading.value = false
  }
}

const resolveKeepOne = (keepPlace, removePlace) => {
  showModal({
    title: 'Résolution de doublon',
    body: `Voulez-vous conserver "${keepPlace.name}" (ID #${keepPlace.id}) et supprimer définitivement le doublon "${removePlace.name}" (ID #${removePlace.id}) ?`,
    icon: 'ph:intersect-bold',
    type: 'danger',
    confirmLabel: 'Conserver et Supprimer l\'autre',
    confirm: async () => {
      modal.value.visible = false
      try {
        await adminService.deletePlace(removePlace.id)
        places.value = places.value.filter(p => p.id !== removePlace.id)
        duplicatesList.value = duplicatesList.value.filter(d =>
          d.place_a.id !== removePlace.id && d.place_b.id !== removePlace.id
        )
        showToast(`Doublon #${removePlace.id} supprimé. "${keepPlace.name}" conservé.`)
      } catch (e) {
        showToast(e.message || 'Erreur lors de la suppression', 'error')
      }
    }
  })
}

const resetPlaceFilters = () => {
  placesSearch.value = ''
  placesCategoryFilter.value = 'all'
  placesStatusFilter.value = 'all'
  placesPage.value = 1
}

const formatStatusLabel = (s) => {
  const map = { approved: 'Approuvé', pending: 'En attente', hidden: 'Masqué' }
  return map[s] || s
}

const getCategoryIcon = (c) => {
  const map = {
    amphi: 'ph:presentation-fill',
    studies: 'ph:graduation-cap-fill',
    library: 'ph:books-fill',
    food: 'ph:fork-knife-fill',
    housing: 'ph:house-line-fill',
    admin: 'ph:buildings-fill',
    health: 'ph:first-aid-kit-fill',
    other: 'ph:map-pin-fill'
  }
  return map[c] || 'ph:map-pin-fill'
}

const getCategoryColor = (c) => {
  const map = {
    amphi: '#6366f1',
    studies: '#3b82f6',
    library: '#06b6d4',
    food: '#f59e0b',
    housing: '#8b5cf6',
    admin: '#64748b',
    health: '#ef4444',
    other: '#10b981'
  }
  return map[c] || '#6366f1'
}

const getCategoryBg = (c) => {
  return `${getCategoryColor(c)}22`
}

const truncateText = (str, len = 60) => {
  if (!str) return ''
  return str.length > len ? str.slice(0, len) + '...' : str
}

const onImgError = (e) => {
  if (e && e.target) {
    e.target.style.display = 'none'
  }
}

const formatCoord = (val, precision = 4) => {
  if (val === null || val === undefined || val === '') return '—'
  const num = Number(val)
  return isNaN(num) ? String(val) : num.toFixed(precision)
}

const handleLogout = () => {
  adminService.logout()
  router.push('/admin/login')
}

// ─── FORMATTERS & HELPERS ─────────────────────
const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'
const formatDateTime = (d) => d ? new Date(d).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—'

const formatPriority = (p) => {
  const map = { urgent: 'Urgent', high: 'Haute', medium: 'Moyenne', low: 'Basse' }
  return map[p] || 'Moyenne'
}

const formatReportType = (t) => {
  const map = { message: 'Message', user: 'Utilisateur', place: 'Lieu' }
  return map[t] || 'Signalement'
}

const getReportTypeIcon = (t) => {
  const map = { message: 'ph:chat-teardrop-text-fill', user: 'ph:user-fill', place: 'ph:map-pin-fill' }
  return map[t] || 'ph:flag-fill'
}

const formatReportStatus = (s) => {
  const map = { pending: 'En attente', in_progress: 'En cours', resolved: 'Résolu', dismissed: 'Rejeté' }
  return map[s] || 'En attente'
}

const formatSanctionType = (t) => {
  const map = { warning: '⚠️ Avertissement', mute: '🔇 Sourdine (Mute)', suspension: '⏳ Suspension temporaire', ban: '🚫 Bannissement définitif' }
  return map[t] || t
}

const formatRole = (r) => {
  const map = {
    super_admin: 'Super Admin',
    admin: 'Administrateur',
    moderator: 'Modérateur',
    user: 'Étudiant'
  }
  return map[r] || 'Étudiant'
}

const getRoleIcon = (r) => {
  const map = {
    super_admin: 'ph:crown-fill',
    admin: 'ph:shield-star-fill',
    moderator: 'ph:shield-check-fill',
    user: 'ph:student-fill'
  }
  return map[r] || 'ph:student-fill'
}

const formatAuditAction = (a) => {
  const map = {
    login: 'Connexion admin',
    warn_user: 'Avertissement envoyé',
    mute_user: 'Mise en sourdine (mute)',
    suspend_user: 'Suspension de compte',
    ban_user: 'Bannissement utilisateur',
    unban_user: 'Sanctions levées',
    update_user_role: 'Rôle utilisateur modifié',
    force_logout_user: 'Déconnexion forcée',
    reset_user_password: 'Mot de passe réinitialisé',
    delete_message: 'Message supprimé',
    update_report_status: 'Statut signalement modifié',
    update_user_note: 'Note interne mise à jour',
    approve_place: 'Lieu approuvé',
    delete_place: 'Lieu supprimé',
    delete_user: 'Utilisateur supprimé',
    restrict_user: 'Utilisateur restreint',
    unrestrict_user: 'Restriction levée'
  }
  return map[a] || a
}

const getAuditActionIcon = (a) => {
  if (a.includes('ban') || a.includes('delete')) return 'ph:prohibit-bold'
  if (a.includes('warn')) return 'ph:warning-bold'
  if (a.includes('mute') || a.includes('suspend')) return 'ph:speaker-simple-slash-bold'
  if (a.includes('approve') || a.includes('unban')) return 'ph:check-bold'
  return 'ph:shield-bold'
}

const formatAuditDetails = (d) => {
  if (typeof d === 'string') return d
  if (typeof d === 'object' && d !== null) {
    if (d.reason) return `Motif: ${d.reason}`
    if (d.name) return `Nom: ${d.name}`
    if (d.status) return `Statut: ${d.status}`
    return JSON.stringify(d).replace(/["{}]/g, '')
  }
  return '—'
}

const avatarGradients = [
  ['#6366f1', '#8b5cf6'], ['#06b6d4', '#3b82f6'],
  ['#22c55e', '#14b8a6'], ['#f59e0b', '#ef4444'],
  ['#ec4899', '#a855f7']
]
const userAvatarGradient = (name) => {
  const idx = (name?.charCodeAt(0) || 0) % avatarGradients.length
  return `linear-gradient(135deg, ${avatarGradients[idx][0]}, ${avatarGradients[idx][1]})`
}

// ══════════════════════════════════════════════════════════════
// PHASE 5 & 6: SYSTEM SUPERVISION & AI CAMPUS ASSISTANT
// ══════════════════════════════════════════════════════════════
const systemHealthData = ref(null)
const systemLogsData = ref(null)
const systemLoading = ref(false)
const systemLogsLoading = ref(false)
const maintenanceRunning = ref(null)
const logFilterLevel = ref('all')
const logSearch = ref('')
const logDetailModal = ref({
  visible: false,
  log: null,
  copied: false
})

const campusAuditData = ref(null)
const campusAuditLoading = ref(false)
const campusDigestData = ref(null)
const campusDigestLoading = ref(false)
const copiedDigest = ref(false)

const loadSystemHealth = async () => {
  systemLoading.value = true
  try {
    const res = await adminService.getSystemHealth()
    systemHealthData.value = res
    showToast('Santé système actualisée avec succès')
  } catch (e) {
    showToast(e.message || 'Erreur lors de la vérification système', 'error')
  } finally {
    systemLoading.value = false
  }
}

const loadSystemLogs = async () => {
  systemLogsLoading.value = true
  try {
    const params = {}
    if (logFilterLevel.value && logFilterLevel.value !== 'all') params.level = logFilterLevel.value
    if (logSearch.value.trim()) params.search = logSearch.value.trim()
    const res = await adminService.getSystemLogs(params)
    systemLogsData.value = res
  } catch (e) {
    showToast('Erreur lors de la lecture des logs', 'error')
  } finally {
    systemLogsLoading.value = false
  }
}

const handleClearCache = async () => {
  maintenanceRunning.value = 'cache'
  try {
    const res = await adminService.clearSystemCache()
    showToast(res.message || 'Cache vidé avec succès !')
    await loadSystemHealth()
  } catch (e) {
    showToast(e.message || 'Erreur lors du vidage de cache', 'error')
  } finally {
    maintenanceRunning.value = null
  }
}

const handleOptimizeSystem = async () => {
  maintenanceRunning.value = 'optimize'
  try {
    const res = await adminService.optimizeSystem()
    showToast(res.message || 'Optimisation système terminée !')
    await loadSystemHealth()
  } catch (e) {
    showToast(e.message || 'Erreur d\'optimisation', 'error')
  } finally {
    maintenanceRunning.value = null
  }
}

const handleClearLogs = () => {
  showModal({
    title: 'Purger les logs système',
    body: 'Êtes-vous sûr de vouloir vider le fichier journal "storage/logs/laravel.log" ? Cette action est irréversible.',
    icon: 'ph:trash-bold',
    type: 'danger',
    confirmLabel: 'Purger les logs',
    confirm: async () => {
      modal.value.visible = false
      maintenanceRunning.value = 'logs'
      try {
        const res = await adminService.clearSystemLogs()
        showToast(res.message || 'Logs purgés avec succès.')
        await loadSystemLogs()
        await loadSystemHealth()
      } catch (e) {
        showToast(e.message || 'Erreur lors de la purge des logs', 'error')
      } finally {
        maintenanceRunning.value = null
      }
    }
  })
}

const openLogDetail = (log) => {
  logDetailModal.value = {
    visible: true,
    log,
    copied: false
  }
}

const copyLogStackTrace = async () => {
  if (!logDetailModal.value.log?.stack_trace) return
  try {
    await navigator.clipboard.writeText(logDetailModal.value.log.stack_trace)
    logDetailModal.value.copied = true
    showToast('Trace d\'exécution copiée dans le presse-papier')
    setTimeout(() => { logDetailModal.value.copied = false }, 2500)
  } catch (e) {
    showToast('Impossible de copier dans le presse-papier', 'error')
  }
}

// ─── AI CAMPUS ASSISTANT (PHASE 6) ─────────────
const runCampusAudit = async () => {
  campusAuditLoading.value = true
  try {
    const res = await adminService.getCampusAudit()
    campusAuditData.value = res
    showToast(`Audit terminé ! Score : ${res.quality_score}/100`)
  } catch (e) {
    showToast('Erreur lors de l\'audit campus', 'error')
  } finally {
    campusAuditLoading.value = false
  }
}

const generateWeeklyDigest = async () => {
  campusDigestLoading.value = true
  try {
    const res = await adminService.generateCampusDigest()
    campusDigestData.value = res
    showToast('✨ Bulletin campus généré avec succès !')
  } catch (e) {
    showToast('Erreur lors de la génération du bulletin', 'error')
  } finally {
    campusDigestLoading.value = false
  }
}

const copyDigestToClipboard = async () => {
  if (!campusDigestData.value?.digest) return
  try {
    await navigator.clipboard.writeText(campusDigestData.value.digest)
    copiedDigest.value = true
    showToast('Bulletin copié dans le presse-papier !')
    setTimeout(() => { copiedDigest.value = false }, 2500)
  } catch (e) {
    showToast('Impossible de copier dans le presse-papier', 'error')
  }
}

const regeneratePlaceDescriptionDirect = async (placeId) => {
  try {
    const res = await adminService.regeneratePlaceDescription(placeId, true)
    showToast(`✨ Description IA générée et enregistrée pour le lieu #${placeId} !`)
    await runCampusAudit()
    await loadData()
  } catch (e) {
    showToast('Erreur lors de l\'enrichissement IA', 'error')
  }
}

const getScoreColor = (score) => {
  if (score >= 80) return '#22c55e'
  if (score >= 50) return '#f59e0b'
  return '#ef4444'
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
.text-red { color: var(--red); }
.text-blue { color: var(--blue); }
.text-orange { color: var(--orange); }

.flex-items-center { display: flex; align-items: center; }
.gap-1 { gap: 4px; }
.gap-1\.5 { gap: 6px; }
.gap-2 { gap: 8px; }

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
  flex-shrink: 0;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 40;
}
.sidebar-logo {
  padding: 24px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border);
}
.sidebar-logo-icon {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #fff;
  box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}
.sidebar-logo-text { font-size: 18px; font-weight: 800; letter-spacing: -0.5px; }
.sidebar-logo-sub { font-size: 11px; color: var(--text-muted); font-weight: 500; }

.sidebar-nav { padding: 20px 12px; flex: 1; overflow-y: auto; }
.nav-section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.8px;
  color: var(--text-muted);
  text-transform: uppercase;
  padding: 0 8px 8px;
}
.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text-secondary);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 4px;
}
.nav-item:hover { background: rgba(99,102,241,0.08); color: var(--text-primary); }
.nav-item--active {
  background: linear-gradient(90deg, rgba(99,102,241,0.18), rgba(99,102,241,0.06));
  color: var(--indigo-light);
  border-left: 3px solid var(--indigo);
}
.nav-item-left { display: flex; align-items: center; gap: 10px; }
.nav-item-icon-wrap { font-size: 18px; display: flex; }
.nav-item-badge {
  font-size: 11px;
  font-weight: 700;
  background: rgba(99,102,241,0.25);
  color: var(--indigo-light);
  padding: 2px 7px;
  border-radius: 20px;
}
.badge-red { background: rgba(239,68,68,0.25) !important; color: #f87171 !important; }
.badge-blue { background: rgba(59,130,246,0.25) !important; color: #60a5fa !important; }

.sidebar-bottom {
  padding: 16px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.sidebar-admin-info { display: flex; align-items: center; gap: 10px; }
.admin-avatar { font-size: 32px; color: var(--indigo); display: flex; }
.admin-name { font-size: 13px; font-weight: 700; }
.admin-status { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--green); }
.status-dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; box-shadow: 0 0 6px var(--green); }
.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.2);
  color: #f87171;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.logout-btn:hover { background: rgba(239,68,68,0.2); }

/* ══════════════════════════════════════════
   MAIN & TOPBAR
══════════════════════════════════════════ */
.admin-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar {
  height: 70px;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 30;
}
.topbar-left { display: flex; align-items: center; gap: 16px; }
.hamburger { display: none; background: transparent; border: none; color: var(--text-primary); font-size: 22px; cursor: pointer; }
.topbar-title { font-size: 17px; font-weight: 800; letter-spacing: -0.3px; margin: 0; }
.topbar-sub { font-size: 11px; color: var(--text-muted); margin: 0; }
.topbar-right { display: flex; align-items: center; gap: 14px; }
.topbar-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.2);
  color: var(--green);
  font-size: 11.5px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 20px;
}
.pulse-dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
.topbar-refresh {
  width: 34px; height: 34px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-secondary);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.topbar-refresh:hover { color: var(--indigo-light); border-color: var(--indigo); }
.is-refreshing { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.topbar-time { font-size: 12px; color: var(--text-muted); font-weight: 600; }

.content-area { flex: 1; padding: 28px; position: relative; }
.content-inner { max-width: 1300px; margin: 0 auto; }

/* ══════════════════════════════════════════
   STATS GRID
══════════════════════════════════════════ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s, border-color 0.2s;
}
.stat-card:hover { transform: translateY(-2px); border-color: var(--border-hover); }
.stat-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
.stat-icon-wrap { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.card-blue .stat-icon-wrap { background: rgba(59,130,246,0.15); color: #60a5fa; }
.card-green .stat-icon-wrap { background: rgba(34,197,94,0.15); color: #4ade80; }
.card-orange .stat-icon-wrap { background: rgba(245,158,11,0.15); color: #fbbf24; }
.card-purple .stat-icon-wrap { background: rgba(168,85,247,0.15); color: #c084fc; }
.card-red .stat-icon-wrap { background: rgba(239,68,68,0.15); color: #f87171; }
.card-gray .stat-icon-wrap { background: rgba(148,163,184,0.15); color: #94a3b8; }

.stat-trend { font-size: 11px; font-weight: 700; color: var(--green); display: flex; align-items: center; gap: 3px; }
.stat-alert-badge { font-size: 10.5px; font-weight: 700; background: rgba(239,68,68,0.2); color: #f87171; padding: 2px 7px; border-radius: 12px; display: flex; align-items: center; gap: 3px; }
.stat-card-value { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
.stat-card-label { font-size: 12px; color: var(--text-secondary); font-weight: 500; margin-bottom: 12px; }
.stat-card-bar { height: 4px; background: rgba(255,255,255,0.06); border-radius: 2px; overflow: hidden; }
.stat-bar-fill { height: 100%; border-radius: 2px; }
.card-blue .stat-bar-fill { background: #3b82f6; }
.card-green .stat-bar-fill { background: #22c55e; }
.card-orange .stat-bar-fill { background: #f59e0b; }
.card-purple .stat-bar-fill { background: #a855f7; }
.card-red .stat-bar-fill { background: #ef4444; }
.card-gray .stat-bar-fill { background: #64748b; }

/* ══════════════════════════════════════════
   DASH GRID (PIE & ACTIONS)
══════════════════════════════════════════ */
.dash-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 16px; margin-bottom: 24px; }
.dash-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 22px; }
.dash-card-head { margin-bottom: 18px; }
.dash-card-title { font-size: 14.5px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0; }
.title-icon { color: var(--indigo); font-size: 18px; }

.category-list { display: flex; flex-direction: column; gap: 12px; }
.category-item { display: flex; flex-direction: column; gap: 4px; }
.cat-info { display: flex; align-items: center; font-size: 12px; font-weight: 600; }
.cat-dot { width: 8px; height: 8px; border-radius: 50%; margin-right: 8px; }
.cat-name { flex: 1; }
.cat-count { color: var(--text-secondary); }
.cat-bar-bg { height: 5px; background: rgba(255,255,255,0.06); border-radius: 3px; overflow: hidden; }
.cat-bar-fill { height: 100%; border-radius: 3px; }

.dash-actions-card {
  background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.08));
  border: 1px solid rgba(99,102,241,0.25);
  border-radius: 16px;
  padding: 22px;
  position: relative;
}
.dash-actions-hero { font-size: 28px; color: var(--indigo-light); margin-bottom: 10px; }
.dash-actions-title { font-size: 16px; font-weight: 800; margin: 0 0 4px; }
.dash-actions-sub { font-size: 11.5px; color: var(--text-secondary); margin: 0 0 16px; }
.quick-actions { display: flex; flex-direction: column; gap: 8px; }
.quick-action {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--text-primary);
}
.quick-action:hover { border-color: var(--indigo); transform: translateX(3px); }
.qa-left { display: flex; align-items: center; gap: 12px; text-align: left; }
.qa-icon { width: 32px; height: 32px; background: rgba(99,102,241,0.15); color: var(--indigo-light); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.qa-icon--red { background: rgba(239,68,68,0.15); color: #f87171; }
.qa-icon--green { background: rgba(34,197,94,0.15); color: #4ade80; }
.qa-icon--purple { background: rgba(168,85,247,0.15); color: #c084fc; }
.qa-label { font-size: 13px; font-weight: 700; margin: 0; }
.qa-desc { font-size: 10.5px; color: var(--text-muted); margin: 0; }
.qa-badge { font-size: 10.5px; font-weight: 700; background: rgba(99,102,241,0.2); color: var(--indigo-light); padding: 2px 7px; border-radius: 10px; }
.qa-badge--red { background: rgba(239,68,68,0.25); color: #f87171; }
.qa-arrow { font-size: 14px; color: var(--text-muted); }

.activity-list { display: flex; flex-direction: column; gap: 10px; }
.activity-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: var(--surface-2); border-radius: 10px; font-size: 12.5px; }
.activity-dot { width: 8px; height: 8px; border-radius: 50%; }
.activity-dot.blue { background: #3b82f6; }
.activity-dot.orange { background: #f59e0b; }
.activity-dot.red { background: #ef4444; }
.activity-content { flex: 1; }
.activity-text { margin: 0; }
.activity-time { font-size: 11px; color: var(--text-muted); }
.activity-action { background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: var(--indigo-light); border-radius: 6px; padding: 4px 8px; font-size: 11px; font-weight: 700; cursor: pointer; }
.activity-action--red { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #f87171; }

/* ══════════════════════════════════════════
   MODERATION & REPORTS ENRICHIE
══════════════════════════════════════════ */
.moderation-filters {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 12px 16px;
  margin-bottom: 20px;
}
.filter-group { display: flex; align-items: center; gap: 10px; }
.filter-label { font-size: 12px; font-weight: 700; color: var(--text-muted); }
.filter-selects { display: flex; align-items: center; gap: 10px; }
.select-wrap { position: relative; display: flex; align-items: center; }
.select-icon { position: absolute; left: 10px; font-size: 14px; color: var(--text-muted); pointer-events: none; }
.custom-select {
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-primary);
  border-radius: 8px;
  padding: 6px 12px 6px 28px;
  font-size: 12px;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}
.custom-select.full-width { width: 100%; padding-left: 12px; }

.reports-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 16px; }
.report-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  transition: all 0.2s;
  position: relative;
}
.report-card:hover { border-color: var(--border-hover); transform: translateY(-2px); }
.report-priority--urgent { border-left: 4px solid var(--red); }
.report-priority--high { border-left: 4px solid var(--orange); }
.report-priority--medium { border-left: 4px solid var(--blue); }
.report-priority--low { border-left: 4px solid #64748b; }

.report-card-top { display: flex; align-items: center; justify-content: space-between; }
.report-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.priority-chip { font-size: 10.5px; font-weight: 800; padding: 2px 7px; border-radius: 6px; display: flex; align-items: center; gap: 3px; }
.priority-urgent { background: rgba(239,68,68,0.25); color: #f87171; animation: pulse 2s infinite; }
.priority-high { background: rgba(245,158,11,0.2); color: #fbbf24; }
.priority-medium { background: rgba(59,130,246,0.2); color: #60a5fa; }
.priority-low { background: rgba(148,163,184,0.2); color: #94a3b8; }

.type-chip { font-size: 10.5px; font-weight: 600; background: var(--surface-2); color: var(--text-secondary); padding: 2px 7px; border-radius: 6px; display: flex; align-items: center; gap: 3px; }
.chip-report-pending { background: rgba(245,158,11,0.15); color: #fbbf24; }
.chip-report-in_progress { background: rgba(59,130,246,0.15); color: #60a5fa; }
.chip-report-resolved { background: rgba(34,197,94,0.15); color: #4ade80; }
.chip-report-dismissed { background: rgba(148,163,184,0.15); color: #94a3b8; }

.report-card-date { font-size: 11px; color: var(--text-muted); }

.report-users {
  background: var(--surface-2);
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 12px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.report-user { display: flex; flex-direction: column; }
.report-user--target { text-align: right; }
.report-user-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.report-user-name { font-size: 12.5px; font-weight: 700; }
.report-user-name--red { color: #f87171; }
.report-user-email { font-size: 10.5px; color: var(--text-muted); }
.report-arrow { font-size: 14px; color: var(--text-muted); }

.mini-tag { font-size: 9px; font-weight: 800; padding: 1px 5px; border-radius: 4px; text-transform: uppercase; margin-left: 4px; }
.tag-banned { background: rgba(239,68,68,0.3); color: #fca5a5; }
.tag-suspended { background: rgba(245,158,11,0.3); color: #fde047; }
.tag-muted { background: rgba(168,85,247,0.3); color: #d8b4fe; }

.report-reason { background: rgba(0,0,0,0.25); border-radius: 10px; padding: 10px 12px; font-size: 12px; color: var(--text-secondary); display: flex; gap: 8px; }
.quote-icon { font-size: 16px; color: var(--indigo); flex-shrink: 0; margin-top: 2px; }
.reason-text { margin: 0; line-height: 1.4; }

.report-admin-notes { font-size: 11.5px; color: var(--indigo-light); background: rgba(99,102,241,0.1); border-left: 3px solid var(--indigo); padding: 8px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }

.report-actions-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: auto;
  padding-top: 8px;
  border-top: 1px solid rgba(255,255,255,0.05);
}
.rep-action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border-radius: 8px;
  font-size: 11.5px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.rep-btn--context { background: rgba(99,102,241,0.15); color: var(--indigo-light); }
.rep-btn--context:hover { background: rgba(99,102,241,0.3); }
.rep-btn--sanction { background: rgba(239,68,68,0.15); color: #f87171; }
.rep-btn--sanction:hover { background: rgba(239,68,68,0.3); }
.rep-btn--profile { background: var(--surface-2); color: var(--text-secondary); flex: 0 0 auto; width: 34px; height: 34px; padding: 0; }
.rep-btn--profile:hover { color: var(--text-primary); border: 1px solid var(--border); }

.status-quick-select {
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  border-radius: 8px;
  padding: 7px 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
}

/* ══════════════════════════════════════════
   USERS TABLE & MODERATION STATUS
══════════════════════════════════════════ */
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.section-title { font-size: 18px; font-weight: 800; margin: 0 0 2px; }
.section-sub { font-size: 12px; color: var(--text-muted); margin: 0; }

.filters-bar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; flex-wrap: wrap; }
.search-wrap { position: relative; width: 300px; display: flex; align-items: center; }
.search-icon { position: absolute; left: 12px; font-size: 16px; color: var(--text-muted); }
.search-input {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 8px 34px 8px 36px;
  font-size: 12.5px;
  color: var(--text-primary);
  outline: none;
}
.search-input:focus { border-color: var(--indigo); }
.search-clear { position: absolute; right: 10px; background: transparent; border: none; color: var(--text-muted); font-size: 16px; cursor: pointer; }

.filter-tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 3px; }
.filter-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}
.filter-tab:hover { color: var(--text-primary); }
.filter-tab--active { background: rgba(99,102,241,0.2); color: var(--indigo-light); }
.filter-count { font-size: 10px; font-weight: 700; background: var(--surface-2); padding: 1px 6px; border-radius: 8px; }

.table-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; text-align: left; }
.data-table th { background: rgba(255,255,255,0.02); padding: 12px 18px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
.th-right { text-align: right; }
.table-row { border-bottom: 1px solid rgba(255,255,255,0.03); transition: background 0.15s; }
.table-row:hover { background: rgba(255,255,255,0.02); }
.data-table td { padding: 12px 18px; font-size: 12.5px; }

.user-cell { display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #fff; flex-shrink: 0; }
.user-avatar--lg { width: 56px; height: 56px; font-size: 22px; border-radius: 16px; }
.user-name { font-weight: 700; margin: 0; }
.user-name--banned { color: #f87171; text-decoration: line-through; }
.user-name--restricted { color: #fbbf24; }
.user-id { font-size: 10.5px; color: var(--text-muted); margin: 0; }
.td-email { color: var(--text-secondary); }
.td-date { font-size: 11.5px; color: var(--text-muted); }
.td-bold { font-weight: 700; }
.td-muted { color: var(--text-muted); font-size: 11.5px; }
.td-truncate { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-secondary); }

.status-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}
.chip-active { background: rgba(34,197,94,0.15); color: #4ade80; }
.chip-restricted { background: rgba(245,158,11,0.15); color: #fbbf24; }
.chip-muted { background: rgba(168,85,247,0.15); color: #c084fc; }
.chip-suspended { background: rgba(245,158,11,0.25); color: #fde047; }
.chip-banned { background: rgba(239,68,68,0.25); color: #f87171; }
.chip-read { background: rgba(59,130,246,0.15); color: #60a5fa; }
.chip-unread { background: rgba(148,163,184,0.15); color: #94a3b8; }

.reports-count-badge { font-size: 11px; font-weight: 700; color: var(--text-muted); }
.reports-count-badge.has-reports { color: #f87171; background: rgba(239,68,68,0.15); padding: 2px 7px; border-radius: 10px; }

.td-actions { text-align: right; white-space: nowrap; }
.action-btn {
  width: 32px;
  height: 32px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-secondary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  margin-left: 6px;
  transition: all 0.2s;
}
.action-btn:hover { color: var(--text-primary); border-color: var(--indigo); }
.btn-profile:hover { background: rgba(99,102,241,0.2); color: var(--indigo-light); }
.btn-gavel:hover { background: rgba(245,158,11,0.2); color: #fbbf24; }
.btn-unlock { background: rgba(34,197,94,0.15); border-color: rgba(34,197,94,0.3); color: #4ade80; }
.btn-delete:hover { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.4); color: #f87171; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 14px; border-top: 1px solid var(--border); }
.page-btn {
  min-width: 32px;
  height: 32px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.page-btn--active { background: var(--indigo); border-color: var(--indigo); color: #fff; }
.page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

/* ══════════════════════════════════════════
   AUDIT LOGS
══════════════════════════════════════════ */
.refresh-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.refresh-btn:hover { color: var(--indigo-light); border-color: var(--indigo); }

.audit-action-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 6px;
  background: var(--surface-2);
}
.audit-type-ban_user, .audit-type-delete_message, .audit-type-delete_user { background: rgba(239,68,68,0.2); color: #f87171; }
.audit-type-warn_user, .audit-type-mute_user, .audit-type-suspend_user { background: rgba(245,158,11,0.2); color: #fbbf24; }
.audit-type-unban_user, .audit-type-approve_place { background: rgba(34,197,94,0.2); color: #4ade80; }
.target-chip { font-size: 11px; background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px; }
.td-details { max-width: 320px; font-size: 11.5px; color: var(--text-secondary); }

/* ══════════════════════════════════════════
   MODALS (CONTEXT, SANCTION, PROFILE)
══════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 100;
}
.modal-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.6);
  padding: 24px;
}
.modal-card--lg { max-width: 720px; }

.modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 14px; }
.modal-head-icon { width: 40px; height: 40px; border-radius: 12px; background: rgba(99,102,241,0.15); color: var(--indigo-light); display: flex; align-items: center; justify-content: center; font-size: 20px; }
.modal-head-icon--danger { background: rgba(239,68,68,0.15); color: #f87171; }
.modal-title { font-size: 16px; font-weight: 800; margin: 0; }
.modal-sub { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0; }
.modal-close-btn { background: transparent; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; }
.modal-close-btn:hover { color: var(--text-primary); }

.context-reason-box {
  background: rgba(245,158,11,0.1);
  border: 1px solid rgba(245,158,11,0.25);
  border-radius: 12px;
  padding: 12px 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 16px;
}
.reason-box-icon { color: #f59e0b; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.reason-box-label { font-size: 11px; font-weight: 700; color: #fbbf24; margin: 0 0 2px; }
.reason-box-text { font-size: 12.5px; color: var(--text-primary); margin: 0; }

.context-chat-container {
  max-height: 380px;
  overflow-y: auto;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 18px;
}
.context-loading, .context-empty { text-align: center; padding: 40px 20px; color: var(--text-muted); font-size: 13px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.spinner-animate { animation: spin 1s linear infinite; }

.context-messages-list { display: flex; flex-direction: column; gap: 12px; }
.context-msg-item {
  padding: 10px 14px;
  border-radius: 12px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
}
.context-msg-item.is-target-msg {
  background: rgba(239,68,68,0.12);
  border: 2px solid var(--red);
  box-shadow: 0 0 16px rgba(239,68,68,0.2);
}
.context-msg-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.context-msg-author { font-size: 11.5px; font-weight: 700; color: var(--indigo-light); }
.context-msg-author.author-target { color: #f87171; }
.context-msg-time { font-size: 10.5px; color: var(--text-muted); }
.target-flag-badge { font-size: 10px; font-weight: 800; background: var(--red); color: #fff; padding: 2px 6px; border-radius: 4px; display: flex; align-items: center; gap: 3px; }
.context-msg-bubble { font-size: 13px; color: var(--text-primary); line-height: 1.4; word-break: break-word; }
.context-msg-actions { margin-top: 8px; padding-top: 6px; border-top: 1px solid rgba(239,68,68,0.2); display: flex; justify-content: flex-end; }
.del-msg-btn {
  background: rgba(239,68,68,0.2);
  border: 1px solid rgba(239,68,68,0.4);
  color: #f87171;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}
.del-msg-btn:hover { background: var(--red); color: #fff; }

/* Sanction modal */
.modal-form-body { display: flex; flex-direction: column; gap: 14px; margin-bottom: 18px; }
.form-label { font-size: 12px; font-weight: 700; color: var(--text-secondary); }
.sanction-types-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.sanction-type-card {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  transition: all 0.2s;
}
.sanction-type-card:hover { border-color: var(--indigo); }
.sanction-type-card.is-selected { background: rgba(99,102,241,0.15); border-color: var(--indigo); box-shadow: 0 0 10px rgba(99,102,241,0.2); }
.hidden-radio { display: none; }
.st-icon-wrap { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.st-color-orange { background: rgba(245,158,11,0.2); color: #fbbf24; }
.st-color-purple { background: rgba(168,85,247,0.2); color: #c084fc; }
.st-color-red { background: rgba(239,68,68,0.2); color: #f87171; }
.st-color-black { background: rgba(0,0,0,0.4); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
.st-title { font-size: 12.5px; font-weight: 800; margin: 0 0 2px; }
.st-desc { font-size: 10px; color: var(--text-muted); margin: 0; line-height: 1.3; }

.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-textarea {
  width: 100%;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 12.5px;
  color: var(--text-primary);
  outline: none;
  font-family: inherit;
  resize: vertical;
}
.form-textarea:focus { border-color: var(--indigo); }
.form-checkbox-row { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-secondary); cursor: pointer; }

/* Profile modal */
.profile-modal-content { max-height: 520px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; }
.profile-header-card {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 16px;
}
.profile-header-info { flex: 1; }
.profile-name { font-size: 16px; font-weight: 800; margin: 0 0 2px; }
.profile-email { font-size: 12px; color: var(--text-secondary); margin: 0 0 6px; }
.profile-tags { display: flex; gap: 8px; font-size: 10.5px; color: var(--text-muted); }

.profile-counters-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.counter-box { background: var(--surface-2); border: 1px solid var(--border); border-radius: 12px; padding: 12px; text-align: center; }
.counter-num { font-size: 20px; font-weight: 800; display: block; }
.counter-lbl { font-size: 10.5px; color: var(--text-muted); font-weight: 600; }

.internal-note-box {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 14px;
}
.internal-note-head { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--indigo-light); margin-bottom: 8px; }
.save-note-btn {
  background: var(--indigo);
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

.sanctions-timeline-section { display: flex; flex-direction: column; gap: 10px; }
.section-subtitle { font-size: 13px; font-weight: 800; display: flex; align-items: center; gap: 6px; margin: 0; }
.sanctions-timeline { display: flex; flex-direction: column; gap: 10px; border-left: 2px solid var(--border); margin-left: 8px; padding-left: 14px; }
.timeline-item { position: relative; }
.timeline-dot { position: absolute; left: -19px; top: 12px; width: 8px; height: 8px; border-radius: 50%; }
.dot-warning { background: var(--orange); }
.dot-mute { background: var(--purple); }
.dot-suspension { background: var(--red); }
.dot-ban { background: #000; border: 2px solid var(--red); }

.timeline-card { background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; }
.timeline-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px; }
.timeline-type { font-size: 11.5px; font-weight: 800; }
.type-warning { color: #fbbf24; }
.type-mute { color: #c084fc; }
.type-suspension { color: #f87171; }
.type-ban { color: #ef4444; }
.timeline-date { font-size: 10.5px; color: var(--text-muted); }
.timeline-reason { font-size: 12px; color: var(--text-primary); margin: 0 0 6px; }
.timeline-meta { font-size: 10.5px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; }
.active-badge { background: rgba(239,68,68,0.2); color: #f87171; font-weight: 700; padding: 1px 5px; border-radius: 4px; }
.expired-badge { background: rgba(148,163,184,0.2); color: #94a3b8; font-weight: 700; padding: 1px 5px; border-radius: 4px; }
.no-sanctions-msg { font-size: 12px; color: var(--text-muted); font-style: italic; }

.modal-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }
.modal-actions--split { justify-content: space-between; }
.modal-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.modal-btn--cancel { background: var(--surface-2); color: var(--text-secondary); border: 1px solid var(--border); }
.modal-btn--cancel:hover { color: var(--text-primary); }
.modal-btn--danger { background: var(--red); color: #fff; }
.modal-btn--danger:hover { background: #dc2626; }
.modal-btn--warning { background: var(--orange); color: #000; }
.modal-btn--unlock { background: rgba(34,197,94,0.2); border: 1px solid rgba(34,197,94,0.4); color: #4ade80; }

/* ══════════════════════════════════════════
   TOAST & OVERLAYS
══════════════════════════════════════════ */
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 12px 18px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 200;
  box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}
.toast--success { background: #166534; color: #bbf7d0; border: 1px solid #22c55e; }
.toast--error { background: #991b1b; color: #fecaca; border: 1px solid #ef4444; }

.loading-overlay {
  position: absolute;
  inset: 0;
  background: rgba(5,10,20,0.8);
  backdrop-filter: blur(4px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.loading-spinner { position: relative; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; }
.spinner-ring { position: absolute; inset: 0; border: 3px solid transparent; border-top-color: var(--indigo); border-radius: 50%; animation: spin 1s linear infinite; }
.spinner-ring--2 { inset: 6px; border-top-color: #8b5cf6; animation: spin 1.5s linear infinite reverse; }
.spinner-icon { font-size: 22px; color: var(--indigo-light); }
.loading-text { margin-top: 14px; font-size: 13px; color: var(--text-secondary); font-weight: 600; }

.empty-state { text-align: center; padding: 48px 20px; color: var(--text-muted); }
.empty-icon { font-size: 42px; margin-bottom: 10px; display: inline-block; }
.empty-reset { margin-top: 12px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); color: var(--indigo-light); border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; cursor: pointer; }
.empty-state-small { padding: 24px; text-align: center; font-size: 12px; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; gap: 6px; }

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }

/* ══════════════════════════════════════════
   PHASE 2: PLACES, DUPLICATES & AI STYLES
══════════════════════════════════════════ */
.header-actions { display: flex; align-items: center; gap: 10px; }
.secondary-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(99, 102, 241, 0.12);
  border: 1px solid rgba(99, 102, 241, 0.3);
  color: var(--indigo-light);
  padding: 8px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.secondary-btn:hover {
  background: rgba(99, 102, 241, 0.25);
  border-color: var(--indigo);
  transform: translateY(-1px);
}
.btn-pulsing {
  animation: pulse-border 2s infinite;
}
@keyframes pulse-border {
  0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); border-color: #ef4444; }
  70% { box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); border-color: #ef4444; }
  100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.pending-action-btn {
  background: rgba(245, 158, 11, 0.2);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #fbbf24;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.pending-action-btn:hover {
  background: #f59e0b;
  color: #000;
}

.filter-select-wrap {
  min-width: 190px;
}
.count-alert {
  background: rgba(239, 68, 68, 0.25) !important;
  color: #f87171 !important;
  font-weight: 700;
}

.place-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.place-thumb {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  overflow: hidden;
  background: var(--surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid var(--border);
}
.place-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.place-thumb-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}
.place-cell-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-width: 260px;
}
.place-name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.place-desc-preview {
  font-size: 11.5px;
  color: var(--text-muted);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.coords-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(148, 163, 184, 0.08);
  border: 1px solid rgba(148, 163, 184, 0.15);
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11.5px;
  color: var(--text-secondary);
  font-family: monospace;
}

.chip-approved {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #4ade80;
}
.chip-hidden {
  background: rgba(148, 163, 184, 0.15);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #94a3b8;
}
.chip-pending {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.35);
  color: #fbbf24;
}

.btn-warning-soft {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #fbbf24;
}
.btn-warning-soft:hover {
  background: #f59e0b;
  color: #000;
}

/* ─── DUPLICATES VIEW ─── */
.duplicates-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.duplicates-header-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.05));
  border: 1px solid rgba(99, 102, 241, 0.25);
  padding: 16px 20px;
  border-radius: 14px;
}
.dup-header-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: var(--indigo-light);
  flex-shrink: 0;
}
.dup-header-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.dup-header-sub {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0;
}
.refresh-dup-btn {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.refresh-dup-btn:hover:not(:disabled) {
  background: var(--surface);
  color: var(--text-primary);
  border-color: var(--indigo);
}

.duplicates-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.dup-pair-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  transition: border-color 0.2s;
}
.dup-pair-card:hover {
  border-color: var(--border-hover);
}
.dup-pair-banner {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  padding: 10px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}
.dup-metrics {
  display: flex;
  align-items: center;
  gap: 8px;
}
.dup-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 700;
}
.dup-badge--dist {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}
.dup-badge--sim {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #fbbf24;
}
.dup-reason {
  font-size: 12px;
  color: var(--text-muted);
  font-style: italic;
}

.dup-comparison-grid {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  padding: 18px;
  gap: 16px;
  align-items: stretch;
}
.dup-place-box {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.dup-place-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.dup-label {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--indigo-light);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.dup-place-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}
.dup-place-cat {
  font-size: 12px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 5px;
  margin: 0;
}
.dup-place-desc {
  font-size: 12.5px;
  color: var(--text-muted);
  line-height: 1.5;
  margin: 0;
  flex: 1;
}
.dup-place-coords {
  font-size: 11px;
  font-family: monospace;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 5px;
}
.dup-box-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 6px;
}
.place-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.place-btn--edit {
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.3);
  color: var(--indigo-light);
}
.place-btn--edit:hover {
  background: var(--indigo);
  color: #fff;
}
.place-btn--approve {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #4ade80;
}
.place-btn--approve:hover {
  background: #22c55e;
  color: #000;
}
.dup-vs-divider {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: var(--text-muted);
  background: var(--surface);
  border: 1px solid var(--border);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  align-self: center;
}

/* ─── EDIT PLACE MODAL & AI ─── */
.edit-place-card {
  max-width: 620px;
  width: 100%;
}
.edit-place-body {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-height: 70vh;
  overflow-y: auto;
}
.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.form-label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}
.ai-generate-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(168, 85, 247, 0.25));
  border: 1px solid rgba(168, 85, 247, 0.4);
  color: #d8b4fe;
  padding: 4px 10px;
  border-radius: 7px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.ai-generate-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #6366f1, #a855f7);
  color: #fff;
  transform: translateY(-1px);
}
.ai-preview-label {
  font-size: 11.5px;
  font-weight: 700;
  color: #fbbf24;
  margin: 8px 0 4px;
  display: flex;
  align-items: center;
  gap: 5px;
}
.ai-preview-box {
  background: rgba(245, 158, 11, 0.08);
  border: 1px dashed rgba(245, 158, 11, 0.35);
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 12px;
  color: #fde68a;
  line-height: 1.4;
  cursor: pointer;
  transition: all 0.2s;
}
.ai-preview-box:hover {
  background: rgba(245, 158, 11, 0.16);
  border-color: #f59e0b;
}

.modal-btn--primary {
  background: var(--indigo);
  color: #fff;
}
.modal-btn--primary:hover:not(:disabled) {
  background: #4f46e5;
}

.full-width {
  width: 100%;
}

/* ══════════════════════════════════════════
   PHASE 3: USERS, ROLES & DOSSIER STYLES
══════════════════════════════════════════ */
.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 9px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 700;
  text-transform: capitalize;
}
.role-chip--superadmin {
  background: linear-gradient(135deg, rgba(236,72,153,0.2), rgba(168,85,247,0.2));
  border: 1px solid rgba(236,72,153,0.4);
  color: #f472b6;
}
.role-chip--admin {
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.35);
  color: var(--indigo-light);
}
.role-chip--moderator {
  background: rgba(6,182,212,0.15);
  border: 1px solid rgba(6,182,212,0.35);
  color: #22d3ee;
}
.role-chip--user {
  background: rgba(148,163,184,0.1);
  border: 1px solid rgba(148,163,184,0.25);
  color: #cbd5e1;
}

.student-id-badge {
  font-family: monospace;
  font-size: 11px;
  color: var(--indigo-light);
  font-weight: 600;
}
.faculty-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.faculty-name {
  font-size: 12.5px;
  color: var(--text-primary);
  margin: 0;
}
.study-level-badge {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--text-muted);
}

.btn-key {
  background: rgba(245,158,11,0.12);
  border: 1px solid rgba(245,158,11,0.3);
  color: #fbbf24;
}
.btn-key:hover {
  background: #f59e0b;
  color: #000;
}
.btn-logout-force {
  background: rgba(239,68,68,0.12);
  border: 1px solid rgba(239,68,68,0.3);
  color: #f87171;
}
.btn-logout-force:hover {
  background: #ef4444;
  color: #fff;
}

/* ─── DOSSIER MODAL ENHANCEMENTS ─── */
.dossier-modal-card {
  max-width: 780px;
  width: 100%;
}
.student-id-tag, .user-faculty-tag {
  background: rgba(99,102,241,0.12);
  border: 1px solid rgba(99,102,241,0.25);
  color: var(--indigo-light);
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.role-management-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  padding: 12px 18px;
  border-radius: 12px;
  flex-wrap: wrap;
}
.role-mgmt-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.role-mgmt-icon {
  font-size: 26px;
  color: var(--indigo-light);
}
.role-mgmt-title {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.role-mgmt-sub {
  font-size: 11.5px;
  color: var(--text-muted);
  margin: 0;
}
.role-mgmt-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}
.role-select {
  min-width: 220px;
  padding: 7px 12px;
  font-size: 12px;
}
.role-save-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--indigo);
  color: #fff;
  border: none;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.role-save-btn:hover:not(:disabled) {
  background: #4f46e5;
}
.role-save-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.counter-box {
  cursor: pointer;
  transition: all 0.2s;
}
.counter-box:hover, .counter-box--active {
  border-color: var(--indigo);
  background: rgba(99,102,241,0.08);
}

.dossier-tabs-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid var(--border);
  padding-bottom: 8px;
}
.dossier-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-size: 12.5px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}
.dossier-tab-btn:hover {
  color: var(--text-primary);
  background: rgba(255,255,255,0.05);
}
.dossier-tab-btn--active {
  background: rgba(99,102,241,0.15) !important;
  color: var(--indigo-light) !important;
}

.dossier-tab-pane {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.dossier-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.dossier-item-card {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.dossier-item-left {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}
.dossier-item-icon {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}
.dossier-item-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.dossier-item-sub {
  font-size: 11px;
  color: var(--text-muted);
  margin: 0;
}
.dossier-msg-text {
  font-size: 12.5px;
  color: var(--text-primary);
  margin: 0 0 2px;
}
.dossier-report-reason {
  font-size: 12px;
  color: var(--text-secondary);
  margin: 4px 0 0;
  line-height: 1.4;
}

.reports-split-view {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.reports-col {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.modal-btn--secondary {
  background: var(--surface-2);
  border: 1px solid var(--border);
  color: var(--text-secondary);
}
.modal-btn--secondary:hover {
  background: var(--surface);
  color: var(--text-primary);
  border-color: var(--indigo);
}

/* ─── RESET PASSWORD MODAL ─── */
.reset-pwd-card {
  max-width: 480px;
  width: 100%;
}
.modal-body-content {
  padding: 18px 22px;
}
.reset-desc {
  font-size: 12.5px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin: 0;
}
.pwd-result-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pwd-result-head {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
}
.pwd-result-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.3);
  padding: 10px 14px;
  border-radius: 10px;
}
.pwd-code {
  font-family: monospace;
  font-size: 16px;
  font-weight: 800;
  color: #4ade80;
  letter-spacing: 1px;
}
.pwd-copy-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #22c55e;
  color: #000;
  border: none;
  padding: 6px 12px;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.pwd-copy-btn:hover {
  background: #16a34a;
  color: #fff;
}
.pwd-hint {
  font-size: 11.5px;
  color: var(--text-muted);
  line-height: 1.4;
  margin: 0;
}

/* ══════════════════════════════════════════
   PHASE 4: ANALYTICS & CHARTS STYLES
══════════════════════════════════════════ */
.period-switcher {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--surface-2);
  border: 1px solid var(--border);
  padding: 4px;
  border-radius: 10px;
}
.period-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 7px;
  cursor: pointer;
  transition: all 0.2s;
}
.period-btn:hover {
  color: var(--text-primary);
}
.period-btn--active {
  background: var(--indigo);
  color: #fff !important;
}

.analytics-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.analytics-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}
.kpi-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.kpi-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.kpi-icon {
  font-size: 20px;
}
.kpi-val {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 4px;
}
.kpi-sub {
  font-size: 11.5px;
  color: var(--text-muted);
  margin: 0;
}
.kpi-card--green .kpi-icon { color: #4ade80; }
.kpi-card--blue .kpi-icon { color: #60a5fa; }
.kpi-card--purple .kpi-icon { color: #c084fc; }
.kpi-card--orange .kpi-icon { color: #fbbf24; }

.analytics-charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
}
.analytics-bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.analytics-chart-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px 22px;
  display: flex;
  flex-direction: column;
}
.chart-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}
.chart-title {
  font-size: 14.5px;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 2px;
}
.chart-sub {
  font-size: 11.5px;
  color: var(--text-muted);
  margin: 0;
}
.chart-legend {
  display: flex;
  align-items: center;
  gap: 14px;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11.5px;
  color: var(--text-secondary);
  font-weight: 600;
}
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
}
.dot-users { background: var(--indigo); }
.dot-messages { background: #06b6d4; }

/* ─── HISTOGRAM CHART ─── */
.chart-bars-container {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 200px;
  padding-top: 20px;
  border-bottom: 1px solid var(--border);
  overflow-x: auto;
}
.chart-bar-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 24px;
  height: 100%;
  justify-content: flex-end;
}
.bar-pair {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 100%;
  width: 100%;
  justify-content: center;
}
.bar-col {
  width: 10px;
  border-radius: 4px 4px 0 0;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}
.bar-col:hover {
  filter: brightness(1.25);
}
.bar-users { background: linear-gradient(180deg, #818cf8, #6366f1); }
.bar-messages { background: linear-gradient(180deg, #22d3ee, #06b6d4); }
.bar-tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(5,10,20,0.9);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 4px;
  border-radius: 4px;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s;
}
.bar-col:hover .bar-tooltip {
  opacity: 1;
}
.bar-label {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 6px;
  font-family: monospace;
}

/* ─── BREAKDOWN BARS ─── */
.breakdown-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.breakdown-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.breakdown-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-secondary);
  text-transform: uppercase;
  margin: 0;
}
.breakdown-bars {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.breakdown-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.breakdown-name {
  font-size: 11.5px;
  color: var(--text-secondary);
  width: 110px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.breakdown-track {
  flex: 1;
  height: 8px;
  background: var(--surface-2);
  border-radius: 4px;
  overflow: hidden;
}
.breakdown-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.4s ease;
}
.fill-priority-urgent { background: #ef4444; }
.fill-priority-high { background: #f97316; }
.fill-priority-medium { background: #3b82f6; }
.fill-priority-low { background: #94a3b8; }
.fill-sanction { background: linear-gradient(90deg, #f59e0b, #ef4444); }
.breakdown-count {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--text-primary);
  width: 28px;
  text-align: right;
}

/* ─── FACULTY DISTRIBUTION ─── */
.faculty-distribution-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.fac-dist-item {
  display: flex;
  align-items: center;
  gap: 10px;
}
.fac-dist-left {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 180px;
}
.fac-dist-rank {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  background: var(--surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
}
.fac-dist-name {
  font-size: 12px;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.fac-dist-bar-wrap {
  flex: 1;
  height: 8px;
  background: var(--surface-2);
  border-radius: 4px;
  overflow: hidden;
}
.fac-dist-bar {
  height: 100%;
  border-radius: 4px;
  transition: width 0.4s ease;
}
.fac-dist-val {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-primary);
  width: 32px;
  text-align: right;
}

/* ─── STATUS PILLS ─── */
.places-status-pills {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.status-stat-box {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.status-stat-box .stat-box-num {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
}
.status-stat-box .stat-box-lbl {
  font-size: 10.5px;
  color: var(--text-muted);
  text-transform: uppercase;
}
.box-approved { border-left: 4px solid #22c55e; }
.box-approved svg { color: #22c55e; font-size: 20px; }
.box-pending { border-left: 4px solid #f59e0b; }
.box-pending svg { color: #f59e0b; font-size: 20px; }
.box-hidden { border-left: 4px solid #94a3b8; }
.box-hidden svg { color: #94a3b8; font-size: 20px; }

/* ══════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════ */
@media (max-width: 900px) {
  .sidebar { position: fixed; transform: translateX(-100%); }
  .sidebar-open { transform: translateX(0); }
  .sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 39; }
  .hamburger { display: block; }
  .dash-grid { grid-template-columns: 1fr; }
  .sanction-types-grid { grid-template-columns: 1fr; }
  .profile-counters-row { grid-template-columns: 1fr; }
  .dup-comparison-grid { grid-template-columns: 1fr; }
  .dup-vs-divider { display: none; }
  .form-row-2 { grid-template-columns: 1fr; }
  .reports-split-view { grid-template-columns: 1fr; }
  .role-management-card { flex-direction: column; align-items: flex-start; }
  .role-mgmt-controls { width: 100%; }
  .role-select { flex: 1; }
  .analytics-kpi-grid { grid-template-columns: 1fr 1fr; }
  .analytics-charts-grid { grid-template-columns: 1fr; }
  .analytics-bottom-grid { grid-template-columns: 1fr; }
  .places-status-pills { grid-template-columns: 1fr; }
  .system-health-grid { grid-template-columns: 1fr; }
  .ai-campus-top-grid { grid-template-columns: 1fr; }
  .maintenance-box { flex-direction: column; align-items: flex-start; }
  .maintenance-actions { width: 100%; flex-direction: column; }
  .maint-btn { width: 100%; justify-content: center; }
  .audit-issues-grid { grid-template-columns: 1fr; }
}

/* ══════════════════════════════════════════
   PHASE 5: SYSTEM HEALTH & LOGS STYLES
══════════════════════════════════════════ */
.system-health-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.health-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}
.health-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.health-card--good { border-color: rgba(34,197,94,0.3); }
.health-card--warning { border-color: rgba(245,158,11,0.3); }
.health-card--error { border-color: rgba(239,68,68,0.4); }

.health-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.health-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}
.db-icon { background: rgba(34,197,94,0.15); color: #4ade80; }
.cache-icon { background: rgba(59,130,246,0.15); color: #60a5fa; }
.ai-icon { background: rgba(168,85,247,0.15); color: #c084fc; }
.srv-icon { background: rgba(245,158,11,0.15); color: #fbbf24; }

.health-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.badge-good { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
.badge-warn { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
.badge-error { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }

.status-indicator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  box-shadow: 0 0 8px currentColor;
  animation: pulse-health 2s infinite ease-in-out;
}
@keyframes pulse-health {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.85); }
}

.health-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 2px;
}
.health-desc {
  font-size: 11.5px;
  color: var(--text-muted);
  margin-bottom: 14px;
}
.health-metrics-row {
  display: flex;
  justify-content: space-between;
  padding-top: 10px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.health-metric {
  display: flex;
  flex-direction: column;
}
.metric-val {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--text-primary);
}
.metric-lbl {
  font-size: 10px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.health-err-text {
  font-size: 11px;
  color: #f87171;
  margin-top: 8px;
  word-break: break-word;
}

/* Maintenance Toolkit */
.maintenance-box {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}
.maintenance-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.maintenance-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(99,102,241,0.15);
  color: var(--indigo-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}
.maintenance-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}
.maintenance-sub {
  font-size: 12px;
  color: var(--text-muted);
}
.maintenance-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.maint-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
}
.maint-btn--cache {
  background: rgba(59,130,246,0.12);
  color: #60a5fa;
  border-color: rgba(59,130,246,0.25);
}
.maint-btn--cache:hover:not(:disabled) {
  background: rgba(59,130,246,0.2);
  border-color: rgba(59,130,246,0.4);
}
.maint-btn--optimize {
  background: rgba(99,102,241,0.12);
  color: var(--indigo-light);
  border-color: rgba(99,102,241,0.25);
}
.maint-btn--optimize:hover:not(:disabled) {
  background: rgba(99,102,241,0.2);
  border-color: rgba(99,102,241,0.4);
}
.maint-btn--danger {
  background: rgba(239,68,68,0.12);
  color: #f87171;
  border-color: rgba(239,68,68,0.25);
}
.maint-btn--danger:hover:not(:disabled) {
  background: rgba(239,68,68,0.2);
  border-color: rgba(239,68,68,0.4);
}

/* Log Explorer */
.log-explorer-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
}
.log-explorer-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
}
.log-icon-badge {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(99,102,241,0.15);
  color: var(--indigo-light);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}
.log-card-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}
.log-card-sub {
  font-size: 11.5px;
  color: var(--text-muted);
}
.log-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.log-select {
  font-size: 12px;
  padding: 6px 12px;
}
.log-search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.log-search-icon {
  position: absolute;
  left: 10px;
  color: var(--text-muted);
  font-size: 14px;
}
.log-search-input {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 6px 12px 6px 30px;
  font-size: 12px;
  color: var(--text-primary);
  outline: none;
  min-width: 180px;
}
.refresh-btn-small {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 6px 10px;
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.refresh-btn-small:hover { color: var(--text-primary); border-color: var(--indigo); }

.logs-container {
  max-height: 480px;
  overflow-y: auto;
}
.log-entries-list {
  display: flex;
  flex-direction: column;
}
.log-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 18px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  cursor: pointer;
  transition: background 0.15s;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 12px;
}
.log-row:hover {
  background: rgba(99,102,241,0.05);
}
.log-row-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.log-level-chip {
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}
.chip-level-error, .chip-level-critical, .chip-level-emergency, .chip-level-alert {
  background: rgba(239,68,68,0.2);
  color: #f87171;
  border: 1px solid rgba(239,68,68,0.3);
}
.chip-level-warning {
  background: rgba(245,158,11,0.2);
  color: #fbbf24;
  border: 1px solid rgba(245,158,11,0.3);
}
.chip-level-info, .chip-level-notice {
  background: rgba(59,130,246,0.2);
  color: #60a5fa;
  border: 1px solid rgba(59,130,246,0.3);
}
.chip-level-debug {
  background: rgba(148,163,184,0.15);
  color: #94a3b8;
  border: 1px solid rgba(148,163,184,0.25);
}

.log-timestamp { color: var(--text-muted); font-size: 11px; }
.log-env { color: var(--text-muted); font-size: 10px; }
.log-row-msg {
  flex: 1;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-family: inherit;
}
.log-row-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.trace-indicator {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 6px;
  background: rgba(168,85,247,0.15);
  color: #c084fc;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
}
.log-view-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}
.log-view-btn:hover { color: var(--indigo-light); background: rgba(99,102,241,0.1); }

/* Log Detail Modal */
.log-detail-modal-card {
  max-width: 780px;
}
.log-modal-icon--error { background: rgba(239,68,68,0.2); color: #f87171; }
.log-modal-icon--warning { background: rgba(245,158,11,0.2); color: #fbbf24; }
.log-modal-icon--info { background: rgba(59,130,246,0.2); color: #60a5fa; }
.log-msg-fullbox {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 12px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 12.5px;
  color: var(--text-primary);
  line-height: 1.5;
  word-break: break-word;
}
.log-stack-pre {
  background: #020617;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 8px;
  padding: 12px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  color: #94a3b8;
  max-height: 300px;
  overflow: auto;
  line-height: 1.6;
}
.copy-trace-btn {
  background: rgba(99,102,241,0.15);
  color: var(--indigo-light);
  border: 1px solid rgba(99,102,241,0.3);
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* ══════════════════════════════════════════
   PHASE 6: AI CAMPUS STYLES
══════════════════════════════════════════ */
.ai-campus-top-grid {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 20px;
  margin-bottom: 24px;
}
.ai-score-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.score-card-left {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}
.score-circle {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  border: 4px solid #22c55e;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 0 16px rgba(34,197,94,0.2);
}
.score-num {
  font-size: 22px;
  font-weight: 800;
  line-height: 1;
}
.score-max {
  font-size: 10px;
  color: var(--text-muted);
}
.score-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 4px;
}
.score-desc {
  font-size: 11.5px;
  color: var(--text-muted);
}
.score-breakdown {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 14px;
}
.score-stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
}
.stat-badge {
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}
.stat-lbl {
  font-size: 11px;
  color: var(--text-muted);
}

/* Weekly Digest Generator */
.digest-generator-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  display: flex;
  flex-direction: column;
}
.digest-gen-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  flex-wrap: wrap;
  gap: 10px;
}
.digest-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(168,85,247,0.15);
  color: #c084fc;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}
.digest-title { font-size: 14.5px; font-weight: 700; color: var(--text-primary); }
.digest-sub { font-size: 11.5px; color: var(--text-muted); }

.generate-digest-btn {
  background: linear-gradient(135deg, #6366f1, #a855f7);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 8px 14px;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 14px rgba(99,102,241,0.3);
  transition: all 0.2s;
}
.generate-digest-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(99,102,241,0.45);
}

.digest-preview-content {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  max-height: 240px;
  overflow-y: auto;
}
.digest-meta-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.source-tag { font-size: 11px; color: var(--indigo-light); font-weight: 600; }
.digest-date { font-size: 11px; color: var(--text-muted); }
.copy-digest-btn {
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.3);
  color: var(--indigo-light);
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.digest-pre {
  white-space: pre-wrap;
  font-family: inherit;
  font-size: 12.5px;
  color: var(--text-primary);
  line-height: 1.6;
}
.digest-placeholder {
  background: var(--surface-2);
  border: 1px dashed var(--border);
  border-radius: 12px;
  padding: 30px 20px;
  text-align: center;
  color: var(--text-muted);
  font-size: 12px;
}
.placeholder-icon {
  font-size: 28px;
  color: var(--indigo-light);
  margin-bottom: 8px;
}

/* Audit Issues Grid */
.audit-issues-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
}
.audit-issue-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 12px;
}
.issue-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.issue-place-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}
.issue-place-cat {
  font-size: 11px;
  color: var(--text-muted);
}
.issue-badges-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.issue-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}
.pill-severity-critical {
  background: rgba(239,68,68,0.15);
  color: #f87171;
  border: 1px solid rgba(239,68,68,0.25);
}
.pill-severity-warning {
  background: rgba(245,158,11,0.15);
  color: #fbbf24;
  border: 1px solid rgba(245,158,11,0.25);
}
.pill-severity-info {
  background: rgba(59,130,246,0.15);
  color: #60a5fa;
  border: 1px solid rgba(59,130,246,0.25);
}
.issue-card-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 10px;
}
.action-btn-pill {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.2s;
}
.btn-edit-pill {
  background: var(--surface-2);
  border-color: var(--border);
  color: var(--text-secondary);
}
.btn-edit-pill:hover { color: var(--text-primary); border-color: var(--indigo); }
.btn-ai-pill {
  background: rgba(168,85,247,0.15);
  border-color: rgba(168,85,247,0.3);
  color: #c084fc;
}
.btn-ai-pill:hover { background: rgba(168,85,247,0.25); }
</style>
