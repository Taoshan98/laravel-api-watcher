<script setup>
import { ref, watch } from 'vue';
import { X, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  filters: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'apply', 'reset']);

const cloneFilters = (f) => ({
  method: Array.isArray(f?.method) ? [...f.method] : [],
  status_code: Array.isArray(f?.status_code) ? [...f.status_code] : [],
  url: f?.url || '',
  ip_address: f?.ip_address || '',
  user_id: f?.user_id || '',
  date_from: f?.date_from || '',
  date_to: f?.date_to || '',
  duration_min: f?.duration_min !== undefined ? f.duration_min : '',
  duration_max: f?.duration_max !== undefined ? f.duration_max : '',
});

const localFilters = ref(cloneFilters(props.filters));

watch(() => props.filters, (n) => {
  localFilters.value = cloneFilters(n);
}, { deep: true });

const methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
const statusGroups = [
  { label: '2xx Success', value: '2xx', color: 'bg-success-soft text-success border-success/30' },
  { label: '3xx Redirect', value: '3xx', color: 'bg-info-soft text-info border-info/30' },
  { label: '4xx Client Error', value: '4xx', color: 'bg-warning-soft text-warning border-warning/30' },
  { label: '5xx Server Error', value: '5xx', color: 'bg-danger-soft text-danger border-danger/30' },
];

const methodColors = {
  GET: 'bg-info-soft text-info border-info/30',
  POST: 'bg-success-soft text-success border-success/30',
  PUT: 'bg-warning-soft text-warning border-warning/30',
  DELETE: 'bg-danger-soft text-danger border-danger/30',
  PATCH: 'bg-accent-soft text-accent border-accent/30',
  OPTIONS: 'bg-surface-elevated text-text-secondary border-border-default',
  HEAD: 'bg-surface-elevated text-text-secondary border-border-default',
};

const toggleMethod = (m) => {
  if (!Array.isArray(localFilters.value.method)) {
    localFilters.value.method = [];
  }
  const idx = localFilters.value.method.indexOf(m);
  if (idx >= 0) {
    localFilters.value.method.splice(idx, 1);
  } else {
    localFilters.value.method.push(m);
  }
};

const toggleStatus = (s) => {
  if (!Array.isArray(localFilters.value.status_code)) {
    localFilters.value.status_code = [];
  }
  const idx = localFilters.value.status_code.indexOf(s);
  if (idx >= 0) {
    localFilters.value.status_code.splice(idx, 1);
  } else {
    localFilters.value.status_code.push(s);
  }
};

const setQuickDate = (hours) => {
  const now = new Date();
  const past = new Date(now.getTime() - hours * 60 * 60 * 1000);
  const pad = (n) => String(n).padStart(2, '0');
  const formatLocal = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  
  localFilters.value.date_from = formatLocal(past);
  localFilters.value.date_to = formatLocal(now);
};

const applyFilters = () => {
  emit('apply', cloneFilters(localFilters.value));
};

const resetFilters = () => {
  localFilters.value = cloneFilters({});
  emit('reset');
};
</script>

<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />

        <!-- Panel -->
        <transition name="slide-right">
          <div v-if="isOpen" class="fixed inset-y-0 right-0 w-full max-w-md flex flex-col bg-surface-raised border-l border-border-subtle shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-border-subtle">
              <div>
                <h2 class="text-base font-semibold text-text-primary">Advanced Filters</h2>
                <p class="text-xs text-text-muted">Filter API requests by method, status, time, and performance</p>
              </div>
              <button @click="emit('close')" class="p-1.5 rounded-lg hover:bg-surface-hover transition-colors text-text-muted hover:text-text-primary">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-6">
              <!-- HTTP Methods -->
              <div>
                <div class="flex items-center justify-between mb-2.5">
                  <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider">HTTP Methods</h3>
                  <span v-if="localFilters.method.length" class="text-[11px] text-accent font-medium">{{ localFilters.method.length }} selected</span>
                </div>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="m in methods"
                    :key="m"
                    type="button"
                    @click="toggleMethod(m)"
                    :class="[
                      'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-200',
                      localFilters.method.includes(m)
                        ? methodColors[m] + ' ring-1 ring-accent/30'
                        : 'bg-surface-elevated/40 border-border-default text-text-muted hover:text-text-primary hover:border-border-strong'
                    ]"
                  >
                    {{ m }}
                  </button>
                </div>
              </div>

              <!-- Status Code Groups -->
              <div>
                <div class="flex items-center justify-between mb-2.5">
                  <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Status Code Ranges</h3>
                  <span v-if="localFilters.status_code.length" class="text-[11px] text-accent font-medium">{{ localFilters.status_code.length }} selected</span>
                </div>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="s in statusGroups"
                    :key="s.value"
                    type="button"
                    @click="toggleStatus(s.value)"
                    :class="[
                      'px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-200',
                      localFilters.status_code.includes(s.value)
                        ? s.color + ' ring-1 ring-accent/30'
                        : 'bg-surface-elevated/40 border-border-default text-text-muted hover:text-text-primary hover:border-border-strong'
                    ]"
                  >
                    {{ s.label }}
                  </button>
                </div>
              </div>

              <!-- URL / IP / User ID Inputs -->
              <div class="space-y-3.5">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-text-muted uppercase tracking-wider">URL / Path Substring</label>
                  <input
                    type="text"
                    v-model="localFilters.url"
                    placeholder="e.g. /api/v1/orders"
                    class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-text-muted uppercase tracking-wider">Client IP Address</label>
                  <input
                    type="text"
                    v-model="localFilters.ip_address"
                    placeholder="e.g. 192.168.1.1"
                    class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-semibold text-text-muted uppercase tracking-wider">User ID</label>
                  <input
                    type="text"
                    v-model="localFilters.user_id"
                    placeholder="e.g. 42 or uuid"
                    class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
                  />
                </div>
              </div>

              <!-- Date Range -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Date & Time Range</h3>
                  <div class="flex gap-1.5">
                    <button type="button" @click="setQuickDate(1)" class="text-[10px] px-1.5 py-0.5 rounded bg-surface-elevated hover:bg-surface-hover text-text-secondary border border-border-subtle">Last 1h</button>
                    <button type="button" @click="setQuickDate(24)" class="text-[10px] px-1.5 py-0.5 rounded bg-surface-elevated hover:bg-surface-hover text-text-secondary border border-border-subtle">Last 24h</button>
                    <button type="button" @click="setQuickDate(168)" class="text-[10px] px-1.5 py-0.5 rounded bg-surface-elevated hover:bg-surface-hover text-text-secondary border border-border-subtle">Last 7d</button>
                  </div>
                </div>
                <div class="space-y-2.5">
                  <div class="flex flex-col gap-1">
                    <label class="text-[11px] text-text-muted">From</label>
                    <input
                      type="datetime-local"
                      v-model="localFilters.date_from"
                      class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="text-[11px] text-text-muted">To</label>
                    <input
                      type="datetime-local"
                      v-model="localFilters.date_to"
                      class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                </div>
              </div>

              <!-- Execution Duration (Latency) -->
              <div>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2.5">Execution Duration (ms)</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="flex flex-col gap-1">
                    <label class="text-[11px] text-text-muted">Min Duration (ms)</label>
                    <input
                      type="number"
                      min="0"
                      v-model="localFilters.duration_min"
                      placeholder="0"
                      class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="text-[11px] text-text-muted">Max Duration (ms)</label>
                    <input
                      type="number"
                      min="0"
                      v-model="localFilters.duration_max"
                      placeholder="e.g. 500"
                      class="w-full px-3 py-2 text-sm bg-surface-elevated border border-border-default rounded-lg text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="px-5 py-4 border-t border-border-subtle flex gap-3">
              <button
                type="button"
                @click="resetFilters"
                class="flex items-center justify-center gap-1.5 px-4 py-2.5 text-sm font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover rounded-[10px] transition-all"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                Reset
              </button>
              <button
                type="button"
                @click="applyFilters"
                class="flex-1 py-2.5 text-sm font-medium bg-accent text-text-inverted rounded-[10px] hover:bg-accent-hover transition-all font-semibold shadow-md"
              >
                Apply Filters
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </teleport>
</template>
