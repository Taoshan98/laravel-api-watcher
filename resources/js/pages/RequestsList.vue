<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRequests } from '../composables/useRequests';
import { formatDistanceToNow } from 'date-fns';
import FilterSidebar from '../components/FilterSidebar.vue';
import { SlidersHorizontal, X, Search } from 'lucide-vue-next';

const { requests, loading, fetchRequests, activeFilters } = useRequests();

const isFilterOpen = ref(false);
const searchQuery = ref('');

const filteredRequests = computed(() => {
  if (!searchQuery.value) return requests.value;
  const q = searchQuery.value.toLowerCase();
  return requests.value.filter(r =>
    r.url?.toLowerCase().includes(q) ||
    r.method?.toLowerCase().includes(q) ||
    String(r.status_code).includes(q)
  );
});

const applyFilters = (filters) => {
  fetchRequests(filters);
  isFilterOpen.value = false;
};

const resetFilters = () => {
  fetchRequests({ method: [], status_code: [], url: '', ip_address: '', user_id: '', date_from: '', date_to: '', duration_min: '', duration_max: '' });
  isFilterOpen.value = false;
};

const hasActiveFilters = () => {
  const f = activeFilters.value;
  return f.method.length > 0 || f.status_code.length > 0 || f.url || f.ip_address || f.user_id || f.date_from || f.date_to || f.duration_min || f.duration_max;
};

const statusColor = (code) => {
  if (code >= 500) return 'bg-danger-soft text-danger';
  if (code >= 400) return 'bg-warning-soft text-warning';
  if (code >= 300) return 'bg-info-soft text-info';
  return 'bg-success-soft text-success';
};

const methodColor = (method) => {
  const map = {
    GET: 'bg-info-soft text-info',
    POST: 'bg-success-soft text-success',
    PUT: 'bg-warning-soft text-warning',
    DELETE: 'bg-danger-soft text-danger',
    PATCH: 'bg-accent-soft text-accent',
  };
  return map[method] || 'bg-surface-elevated text-text-secondary';
};

const durationColor = (ms) => {
  if (ms > 500) return 'text-danger';
  if (ms > 200) return 'text-warning';
  return 'text-success';
};

const formatPath = (url) => {
  try { return new URL(url).pathname + new URL(url).search; } catch { return url; }
};

onMounted(() => fetchRequests());
</script>

<template>
  <div class="space-y-4 animate-[fade-in_0.3s_ease]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Requests</h1>
        <p class="text-sm text-text-muted mt-0.5">API request log</p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Search -->
        <div class="relative flex-1 sm:w-64">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search requests..."
            class="w-full pl-9 pr-3 py-2 text-sm"
          />
        </div>
        <!-- Filter button -->
        <button
          @click="isFilterOpen = true"
          :class="[
            'flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-[10px] transition-all duration-200',
            hasActiveFilters()
              ? 'bg-accent text-text-inverted'
              : 'glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover'
          ]"
        >
          <SlidersHorizontal class="w-4 h-4" />
          <span class="hidden sm:inline">Filter</span>
        </button>
      </div>
    </div>

    <!-- Active filters -->
    <div v-if="hasActiveFilters()" class="flex flex-wrap gap-1.5">
      <span v-for="m in activeFilters.method" :key="'m-'+m" class="badge bg-info-soft text-info">
        {{ m }}
        <button @click="activeFilters.method = activeFilters.method.filter(x => x !== m); fetchRequests()" class="ml-1"><X class="w-3 h-3" /></button>
      </span>
      <span v-for="s in activeFilters.status_code" :key="'s-'+s" class="badge bg-success-soft text-success">
        {{ s }}
      </span>
      <span v-if="activeFilters.url" class="badge bg-surface-elevated text-text-secondary">URL: {{ activeFilters.url }}</span>
      <button @click="resetFilters" class="text-xs text-accent hover:text-accent-hover transition-colors">Clear all</button>
    </div>

    <FilterSidebar
      :is-open="isFilterOpen"
      :filters="activeFilters"
      @close="isFilterOpen = false"
      @apply="applyFilters"
      @reset="resetFilters"
    />

    <!-- Table (desktop) -->
    <div class="glass-surface overflow-hidden">
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-border-subtle">
              <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider px-5 py-3">Method</th>
              <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider px-5 py-3">Path</th>
              <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider px-5 py-3">Status</th>
              <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider px-5 py-3">Duration</th>
              <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider px-5 py-3">Time</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-5 py-8 text-center text-text-muted text-sm">Loading...</td>
            </tr>
            <tr v-else-if="filteredRequests.length === 0">
              <td colspan="5" class="px-5 py-12 text-center text-text-muted text-sm">
                <div class="space-y-2">
                  <Search class="w-8 h-8 mx-auto text-text-muted opacity-50" />
                  <p>No requests found.</p>
                </div>
              </td>
            </tr>
            <tr
              v-for="req in filteredRequests"
              :key="req.id"
              @click="$router.push('/requests/' + req.id)"
              class="border-b border-border-subtle last:border-0 hover:bg-surface-hover transition-colors cursor-pointer"
            >
              <td class="px-5 py-3.5">
                <span :class="[methodColor(req.method), 'badge text-[11px]']">{{ req.method }}</span>
              </td>
              <td class="px-5 py-3.5 font-mono text-sm text-text-secondary truncate max-w-[400px]" :title="req.url">
                {{ formatPath(req.url) }}
              </td>
              <td class="px-5 py-3.5">
                <span :class="[statusColor(req.status_code), 'badge text-[11px]']">{{ req.status_code }}</span>
              </td>
              <td class="px-5 py-3.5">
                <span :class="['text-sm font-medium', durationColor(req.duration_ms)]">{{ req.duration_ms }}ms</span>
              </td>
              <td class="px-5 py-3.5 text-sm text-text-muted whitespace-nowrap">
                {{ formatDistanceToNow(new Date(req.created_at), { addSuffix: true }) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="md:hidden divide-y divide-border-subtle">
        <div v-if="loading" class="p-5 text-center text-text-muted text-sm">Loading...</div>
        <div v-else-if="filteredRequests.length === 0" class="p-8 text-center text-text-muted text-sm">No requests found.</div>
        <router-link
          v-for="req in filteredRequests"
          :key="req.id"
          :to="'/requests/' + req.id"
          class="block p-4 hover:bg-surface-hover transition-colors"
        >
          <div class="flex items-center justify-between mb-1.5">
            <div class="flex items-center gap-2">
              <span :class="[methodColor(req.method), 'badge text-[10px]']">{{ req.method }}</span>
              <span :class="[statusColor(req.status_code), 'badge text-[10px]']">{{ req.status_code }}</span>
            </div>
            <span :class="['text-xs font-medium', durationColor(req.duration_ms)]">{{ req.duration_ms }}ms</span>
          </div>
          <p class="font-mono text-xs text-text-secondary truncate">{{ formatPath(req.url) }}</p>
          <p class="text-[11px] text-text-muted mt-1">{{ formatDistanceToNow(new Date(req.created_at), { addSuffix: true }) }}</p>
        </router-link>
      </div>
    </div>
  </div>
</template>
