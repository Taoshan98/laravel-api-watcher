<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRequests } from '../composables/useRequests';
import { formatDistanceToNow } from 'date-fns';
import FilterSidebar from '../components/FilterSidebar.vue';
import {
  SlidersHorizontal,
  X,
  Search,
  ChevronLeft,
  ChevronRight,
  RotateCcw,
  Globe,
  Clock,
  AlertTriangle,
  Activity,
  RefreshCw
} from 'lucide-vue-next';

const { requests, loading, fetchRequests, pagination, changePage, activeFilters, stats, fetchStats } = useRequests();

const isFilterOpen = ref(false);
const searchInput = ref('');
let searchTimeout = null;

// Debounced server-side search
watch(searchInput, (val) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchRequests({ search: val, page: 1 });
  }, 350);
});

const clearSearch = () => {
  searchInput.value = '';
  fetchRequests({ search: '', page: 1 });
};

const applyFilters = (filters) => {
  fetchRequests({ ...filters, page: 1 });
  isFilterOpen.value = false;
};

const resetFilters = () => {
  searchInput.value = '';
  fetchRequests({
    search: '',
    method: [],
    status_code: [],
    url: '',
    ip_address: '',
    user_id: '',
    date_from: '',
    date_to: '',
    duration_min: '',
    duration_max: '',
    page: 1
  });
  isFilterOpen.value = false;
};

const setQuickMethod = (m) => {
  let current = Array.isArray(activeFilters.value.method) ? [...activeFilters.value.method] : [];
  if (!m) {
    current = [];
  } else if (current.includes(m)) {
    current = current.filter(x => x !== m);
  } else {
    current = [m];
  }
  fetchRequests({ method: current, page: 1 });
};

const setQuickStatus = (s) => {
  let current = Array.isArray(activeFilters.value.status_code) ? [...activeFilters.value.status_code] : [];
  if (!s) {
    current = [];
  } else if (current.includes(s)) {
    current = current.filter(x => x !== s);
  } else {
    current = [s];
  }
  fetchRequests({ status_code: current, page: 1 });
};

const removeMethodFilter = (m) => {
  const updated = activeFilters.value.method.filter(x => x !== m);
  fetchRequests({ method: updated, page: 1 });
};

const removeStatusFilter = (s) => {
  const updated = activeFilters.value.status_code.filter(x => x !== s);
  fetchRequests({ status_code: updated, page: 1 });
};

const removeSingleFilter = (key) => {
  fetchRequests({ [key]: '', page: 1 });
};

const hasActiveFilters = () => {
  const f = activeFilters.value;
  return (
    (f.search && f.search.trim() !== '') ||
    (f.method && f.method.length > 0) ||
    (f.status_code && f.status_code.length > 0) ||
    Boolean(f.url) ||
    Boolean(f.ip_address) ||
    Boolean(f.user_id) ||
    Boolean(f.date_from) ||
    Boolean(f.date_to) ||
    (f.duration_min !== '' && f.duration_min !== null && f.duration_min !== undefined) ||
    (f.duration_max !== '' && f.duration_max !== null && f.duration_max !== undefined)
  );
};

const activeFilterCount = () => {
  const f = activeFilters.value;
  let count = 0;
  if (f.search && f.search.trim() !== '') count++;
  if (f.method && f.method.length > 0) count += f.method.length;
  if (f.status_code && f.status_code.length > 0) count += f.status_code.length;
  if (f.url) count++;
  if (f.ip_address) count++;
  if (f.user_id) count++;
  if (f.date_from || f.date_to) count++;
  if (f.duration_min !== '' && f.duration_min !== null && f.duration_min !== undefined) count++;
  if (f.duration_max !== '' && f.duration_max !== null && f.duration_max !== undefined) count++;
  return count;
};

const changePerPage = (event) => {
  const perPage = parseInt(event.target.value, 10);
  fetchRequests({ per_page: perPage, page: 1 });
};

const statusColor = (code) => {
  if (!code || code === 0) return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
  if (code >= 200 && code < 300) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
  if (code >= 300 && code < 400) return 'bg-sky-500/10 text-sky-400 border-sky-500/30';
  if (code >= 400 && code < 500) return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
  return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
};

const methodColor = (method) => {
  const map = {
    GET: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    POST: 'bg-sky-500/10 text-sky-400 border-sky-500/30',
    PUT: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    PATCH: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    DELETE: 'bg-rose-500/10 text-rose-400 border-rose-500/30'
  };
  return map[method] || 'bg-surface-elevated text-text-secondary border-border-default';
};

const durationColor = (ms) => {
  if (ms > 500) return 'text-danger';
  if (ms > 200) return 'text-warning';
  return 'text-success';
};

const formatPath = (url) => {
  try {
    const parsed = new URL(url);
    return parsed.pathname + parsed.search;
  } catch {
    return url;
  }
};

const reloadAll = () => {
  fetchRequests();
  fetchStats();
};

onMounted(() => {
  fetchRequests();
  fetchStats();
});
</script>

<template>
  <div class="space-y-6 animate-[fade-in_0.3s_ease]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">API Requests</h1>
        <p class="text-sm text-text-muted mt-0.5">Real-time log of captured incoming application API requests</p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="reloadAll"
          class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
          title="Refresh Data"
        >
          <RefreshCw :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Bento KPI Strip for Ingress Requests (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- KPI 1: Total Ingress Calls -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Total Requests</span>
          <div class="p-2 rounded-xl bg-accent-soft text-accent">
            <Globe class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
            {{ stats.total_requests ? stats.total_requests.toLocaleString() : pagination.total.toLocaleString() }}
          </span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted flex items-center gap-1">
          <span class="w-2 h-2 rounded-full bg-accent inline-block animate-pulse" />
          <span>Captured incoming requests</span>
        </div>
      </div>

      <!-- KPI 2: Average Response Time -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-amber-400/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Average Latency</span>
          <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
            <Clock class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-amber-300">
            {{ stats.avg_latency || 0 }}
          </span>
          <span class="text-xs font-mono text-text-muted">ms</span>
        </div>
        <div class="mt-2 flex items-center gap-1.5">
          <span :class="[
            'text-[10px] font-bold px-2 py-0.5 rounded-full border',
            (stats.avg_latency || 0) < 200
              ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
              : (stats.avg_latency || 0) < 500
              ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
              : 'bg-rose-500/10 text-rose-400 border-rose-500/30'
          ]">
            {{ (stats.avg_latency || 0) < 200 ? 'Fast (<200ms)' : (stats.avg_latency || 0) < 500 ? 'Moderate (<500ms)' : 'Slow (>=500ms)' }}
          </span>
        </div>
      </div>

      <!-- KPI 3: Ingress Error Rate -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-rose-500/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Error Rate</span>
          <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400">
            <AlertTriangle class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-rose-400">
            {{ stats.error_rate || 0 }}%
          </span>
        </div>
        <div class="mt-2 flex items-center gap-1.5">
          <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', (stats.error_rate || 0) > 5 ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30']">
            {{ (stats.error_rate || 0) > 5 ? 'Elevated Errors' : 'Healthy Status' }}
          </span>
        </div>
      </div>

      <!-- KPI 4: P95 Latency / Active Users -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">P95 Latency Benchmark</span>
          <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
            <Activity class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
            {{ stats.p95_latency || 0 }}
          </span>
          <span class="text-xs font-mono text-text-muted">ms</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Active Authenticated Users: {{ stats.active_users || 0 }}
        </div>
      </div>
    </div>

    <!-- Toolbar: Search, Quick Filters & Advanced Filters Toggle -->
    <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle space-y-3">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Server Search Bar -->
        <div class="relative flex-1">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
          <input
            v-model="searchInput"
            type="search"
            placeholder="Search URL, method, IP address, request/response body..."
            class="w-full pl-10 pr-8 py-2 text-xs bg-surface-elevated border border-border-default rounded-xl text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
          />
          <button
            v-if="searchInput"
            @click="clearSearch"
            type="button"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Quick Method Selector Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
          <button
            type="button"
            @click="setQuickMethod('')"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold font-mono transition-all', !activeFilters.method || activeFilters.method.length === 0 ? 'bg-accent text-text-inverted shadow-xs' : 'bg-surface-elevated text-text-secondary hover:text-text-primary border border-border-subtle']"
          >
            ALL
          </button>
          <button
            v-for="m in ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']"
            :key="m"
            type="button"
            @click="setQuickMethod(m)"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold font-mono transition-all', activeFilters.method?.includes(m) ? 'bg-accent text-text-inverted shadow-xs' : 'bg-surface-elevated text-text-secondary hover:text-text-primary border border-border-subtle']"
          >
            {{ m }}
          </button>
        </div>

        <!-- Quick Status Selector Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
          <button
            type="button"
            @click="setQuickStatus('')"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', !activeFilters.status_code || activeFilters.status_code.length === 0 ? 'bg-surface-raised text-text-primary border border-border-default' : 'text-text-muted hover:text-text-primary']"
          >
            Any Status
          </button>
          <button
            type="button"
            @click="setQuickStatus('2xx')"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeFilters.status_code?.includes('2xx') ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-text-muted hover:text-emerald-400']"
          >
            2xx Success
          </button>
          <button
            type="button"
            @click="setQuickStatus('4xx')"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeFilters.status_code?.includes('4xx') ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'text-text-muted hover:text-amber-400']"
          >
            4xx Error
          </button>
          <button
            type="button"
            @click="setQuickStatus('5xx')"
            :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeFilters.status_code?.includes('5xx') ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'text-text-muted hover:text-rose-400']"
          >
            5xx Crash
          </button>
        </div>

        <!-- Advanced Filter Drawer Button -->
        <button
          @click="isFilterOpen = true"
          :class="[
            'flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-xl transition-all duration-200 border whitespace-nowrap',
            hasActiveFilters()
              ? 'bg-accent text-text-inverted border-accent shadow-sm'
              : 'glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover border-border-subtle'
          ]"
        >
          <SlidersHorizontal class="w-4 h-4" />
          <span>Filters</span>
          <span v-if="activeFilterCount() > 0" class="w-4 h-4 rounded-full bg-accent-soft text-accent text-[10px] font-bold flex items-center justify-center">
            {{ activeFilterCount() }}
          </span>
        </button>
      </div>
    </div>

    <!-- Active Filters Bar -->
    <div v-if="hasActiveFilters()" class="flex flex-wrap items-center gap-1.5 p-2 rounded-xl bg-surface-raised border border-border-subtle">
      <span class="text-xs font-semibold text-text-muted mr-1">Active Filters:</span>

      <!-- Search badge -->
      <span v-if="activeFilters.search" class="badge bg-accent-soft text-accent border border-accent/20 flex items-center gap-1">
        Search: "{{ activeFilters.search }}"
        <button type="button" @click="clearSearch" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Methods badges -->
      <span v-for="m in activeFilters.method" :key="'m-' + m" class="badge bg-info-soft text-info border border-info/20 flex items-center gap-1">
        {{ m }}
        <button type="button" @click="removeMethodFilter(m)" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Status codes badges -->
      <span v-for="s in activeFilters.status_code" :key="'s-' + s" class="badge bg-success-soft text-success border border-success/20 flex items-center gap-1">
        Status: {{ s }}
        <button type="button" @click="removeStatusFilter(s)" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- URL badge -->
      <span v-if="activeFilters.url" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        URL: {{ activeFilters.url }}
        <button type="button" @click="removeSingleFilter('url')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- IP badge -->
      <span v-if="activeFilters.ip_address" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        IP: {{ activeFilters.ip_address }}
        <button type="button" @click="removeSingleFilter('ip_address')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- User ID badge -->
      <span v-if="activeFilters.user_id" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        User: {{ activeFilters.user_id }}
        <button type="button" @click="removeSingleFilter('user_id')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Date From badge -->
      <span v-if="activeFilters.date_from" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        From: {{ activeFilters.date_from }}
        <button type="button" @click="removeSingleFilter('date_from')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Date To badge -->
      <span v-if="activeFilters.date_to" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        To: {{ activeFilters.date_to }}
        <button type="button" @click="removeSingleFilter('date_to')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Duration Min badge -->
      <span v-if="activeFilters.duration_min !== '' && activeFilters.duration_min !== null && activeFilters.duration_min !== undefined" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        &ge; {{ activeFilters.duration_min }}ms
        <button type="button" @click="removeSingleFilter('duration_min')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Duration Max badge -->
      <span v-if="activeFilters.duration_max !== '' && activeFilters.duration_max !== null && activeFilters.duration_max !== undefined" class="badge bg-surface-elevated text-text-secondary border border-border-default flex items-center gap-1">
        &le; {{ activeFilters.duration_max }}ms
        <button type="button" @click="removeSingleFilter('duration_max')" class="hover:text-text-primary"><X class="w-3 h-3" /></button>
      </span>

      <!-- Clear all button -->
      <button
        type="button"
        @click="resetFilters"
        class="ml-auto flex items-center gap-1 text-xs text-rose-400 hover:text-rose-300 font-medium px-2 py-0.5 rounded transition-colors"
      >
        <RotateCcw class="w-3 h-3" /> Clear all
      </button>
    </div>

    <!-- Filter Sidebar Drawer -->
    <FilterSidebar
      :is-open="isFilterOpen"
      :filters="activeFilters"
      @close="isFilterOpen = false"
      @apply="applyFilters"
      @reset="resetFilters"
    />

    <!-- Ingress Requests Table Card -->
    <div class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
      <div class="px-5 py-4 bg-surface-raised border-b border-border-subtle flex items-center justify-between">
        <div class="flex items-center gap-2">
          <h2 class="text-sm font-bold text-text-primary">Ingress Request Logs</h2>
          <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-surface-elevated text-text-muted border border-border-subtle">
            {{ pagination.total.toLocaleString() }} total
          </span>
        </div>
        <div class="text-xs text-text-muted">
          Click any row to inspect full Bento request details
        </div>
      </div>

      <div v-if="loading" class="p-12 text-center text-text-muted space-y-2">
        <RefreshCw class="w-6 h-6 animate-spin mx-auto text-accent" />
        <p class="text-xs font-medium">Loading incoming HTTP logs...</p>
      </div>

      <div v-else-if="requests.length === 0" class="p-12 text-center text-text-muted space-y-2">
        <Globe class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
        <p class="text-sm font-bold text-text-primary">No incoming API requests found</p>
        <p class="text-xs text-text-secondary">
          {{ hasActiveFilters() ? 'No ingress requests match your active filters.' : 'Your application has not captured any incoming HTTP requests yet.' }}
        </p>
        <button v-if="hasActiveFilters()" @click="resetFilters" class="btn btn-secondary text-xs mt-3">
          Reset Active Filters
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase text-[10px] font-semibold tracking-wider">
            <tr>
              <th class="px-4 py-3">Method</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Path & Route Endpoint</th>
              <th class="px-4 py-3">Client & Auth Context</th>
              <th class="px-4 py-3">Duration</th>
              <th class="px-4 py-3">Timestamp</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-subtle font-mono">
            <tr
              v-for="req in requests"
              :key="req.id"
              @click="$router.push('/requests/' + req.id)"
              class="hover:bg-surface-hover cursor-pointer transition-colors group"
            >
              <!-- Method -->
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border uppercase', methodColor(req.method)]">
                  {{ req.method }}
                </span>
              </td>

              <!-- Status Code -->
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border', statusColor(req.status_code)]">
                  {{ req.status_code }}
                </span>
              </td>

              <!-- Path & Route Endpoint -->
              <td class="px-4 py-3 max-w-md truncate" :title="req.url">
                <div class="flex items-center gap-1.5 truncate">
                  <span class="font-bold text-accent font-mono">{{ formatPath(req.url) }}</span>
                  <span v-if="req.route_name" class="text-[10px] text-text-muted bg-surface-raised px-1.5 py-0.5 rounded border border-border-subtle truncate">
                    {{ req.route_name }}
                  </span>
                </div>
              </td>

              <!-- Client & Auth Context -->
              <td class="px-4 py-3 text-text-muted">
                <div class="flex items-center gap-1.5 text-xs">
                  <span class="text-text-secondary font-mono">{{ req.ip_address || '—' }}</span>
                  <span v-if="req.user_id" class="text-[10px] px-1.5 py-0.2 rounded bg-accent-soft text-accent font-semibold font-mono">
                    UID: {{ req.user_id }}
                  </span>
                </div>
              </td>

              <!-- Duration -->
              <td class="px-4 py-3 font-bold text-text-primary">
                <span :class="req.duration_ms > 800 ? 'text-rose-400' : req.duration_ms > 300 ? 'text-amber-400' : 'text-emerald-400'">
                  {{ req.duration_ms }} ms
                </span>
              </td>

              <!-- Timestamp -->
              <td class="px-4 py-3 text-text-muted font-sans text-xs">
                <div class="font-medium text-text-secondary">{{ formatDistanceToNow(new Date(req.created_at), { addSuffix: true }) }}</div>
                <div class="text-[10px] text-text-muted font-mono">{{ new Date(req.created_at).toISOString().replace('T', ' ').substring(0, 19) }}</div>
              </td>

              <!-- Action -->
              <td class="px-4 py-3 text-right" @click.stop="$router.push('/requests/' + req.id)">
                <button class="btn btn-secondary text-xs px-2.5 py-1 rounded-lg border border-border-subtle group-hover:border-accent transition-all">
                  Inspect
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div class="p-4 border-t border-border-subtle bg-surface-raised flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
          <span class="text-text-muted">Rows per page:</span>
          <select
            :value="pagination.per_page"
            @change="changePerPage"
            class="bg-surface-elevated border border-border-subtle rounded-lg px-2 py-1 text-text-primary focus:border-accent focus:outline-none"
          >
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span class="text-text-muted">
            Showing page {{ pagination.page }} of {{ Math.max(1, pagination.last_page) }} ({{ pagination.total.toLocaleString() }} total records)
          </span>
        </div>

        <div class="flex items-center gap-1.5">
          <button
            @click="changePage(pagination.page - 1)"
            :disabled="pagination.page <= 1 || loading"
            class="p-1.5 rounded-lg border border-border-subtle bg-surface-elevated hover:bg-surface-hover disabled:opacity-40 disabled:cursor-not-allowed text-text-secondary hover:text-text-primary transition-all"
            title="Previous Page"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button
            @click="changePage(pagination.page + 1)"
            :disabled="pagination.page >= pagination.last_page || loading"
            class="p-1.5 rounded-lg border border-border-subtle bg-surface-elevated hover:bg-surface-hover disabled:opacity-40 disabled:cursor-not-allowed text-text-secondary hover:text-text-primary transition-all"
            title="Next Page"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
