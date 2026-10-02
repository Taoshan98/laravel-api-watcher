<script setup>
import { ref, watch } from 'vue';
import { X, RotateCcw, SlidersHorizontal, Globe, Clock, Layers } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  filters: {
    type: Object,
    default: () => ({})
  },
  availableDomains: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'apply', 'reset']);

const cloneFilters = (f) => ({
  search: f?.search || '',
  domain: f?.domain || '',
  url: f?.url || '',
  parent_request_id: f?.parent_request_id || '',
  method: Array.isArray(f?.method) ? [...f.method] : (f?.method ? [f.method] : []),
  status_code: Array.isArray(f?.status_code) ? [...f.status_code] : (f?.status_code ? [f.status_code] : []),
  status_group: f?.status_group || '',
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
  { label: '2xx Success', value: '2xx', color: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' },
  { label: '4xx Client Error', value: '4xx', color: 'bg-amber-500/10 text-amber-400 border-amber-500/30' },
  { label: '5xx Server Error', value: '5xx', color: 'bg-rose-500/10 text-rose-400 border-rose-500/30' },
  { label: '0 Failed / Network Error', value: 'failed', color: 'bg-rose-500/20 text-rose-300 border-rose-500/40' },
];

const methodColors = {
  GET: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
  POST: 'bg-sky-500/10 text-sky-400 border-sky-500/30',
  PUT: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
  DELETE: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
  PATCH: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
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

        <!-- Drawer Content -->
        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-md bg-surface-overlay border-l border-border-default shadow-2xl flex flex-col">
            <!-- Header -->
            <div class="p-5 border-b border-border-subtle flex items-center justify-between">
              <div class="flex items-center gap-2">
                <SlidersHorizontal class="w-5 h-5 text-accent" />
                <h2 class="text-base font-bold text-text-primary">Advanced Egress Filters</h2>
              </div>
              <button
                @click="emit('close')"
                class="p-1 rounded-lg text-text-muted hover:text-text-primary hover:bg-surface-hover transition-colors"
                title="Close sidebar"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Filter Fields (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-5 space-y-6 text-xs">
              <!-- Target Domain -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  Target Domain
                </label>
                <input
                  v-model="localFilters.domain"
                  type="text"
                  placeholder="e.g. api.stripe.com, api.openai.com"
                  class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                />
                <div v-if="availableDomains && availableDomains.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                  <button
                    v-for="d in availableDomains.slice(0, 5)"
                    :key="d"
                    type="button"
                    @click="localFilters.domain = d"
                    :class="['px-2 py-0.5 rounded text-[10px] font-mono transition-colors border', localFilters.domain === d ? 'bg-accent text-text-inverted border-accent' : 'bg-surface-elevated text-text-secondary border-border-subtle hover:text-text-primary']"
                  >
                    {{ d }}
                  </button>
                </div>
              </div>

              <!-- HTTP Methods -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  HTTP Methods
                </label>
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="m in methods"
                    :key="m"
                    type="button"
                    @click="toggleMethod(m)"
                    :class="[
                      'px-2.5 py-1 rounded-lg font-mono font-bold text-xs border transition-all',
                      localFilters.method.includes(m)
                        ? methodColors[m] + ' ring-1 ring-accent'
                        : 'bg-surface-elevated text-text-muted border-border-subtle hover:text-text-primary'
                    ]"
                  >
                    {{ m }}
                  </button>
                </div>
              </div>

              <!-- HTTP Status Codes / Groups -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  Status Code Groups
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <button
                    v-for="sg in statusGroups"
                    :key="sg.value"
                    type="button"
                    @click="toggleStatus(sg.value)"
                    :class="[
                      'p-2 rounded-xl text-left border text-xs font-semibold transition-all',
                      localFilters.status_code.includes(sg.value)
                        ? sg.color + ' ring-1 ring-accent shadow-xs'
                        : 'bg-surface-elevated text-text-muted border-border-subtle hover:text-text-primary'
                    ]"
                  >
                    {{ sg.label }}
                  </button>
                </div>
              </div>

              <!-- Target URL Path -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  Target URL Pattern
                </label>
                <input
                  v-model="localFilters.url"
                  type="text"
                  placeholder="e.g. /v1/charges, /chat/completions"
                  class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                />
              </div>

              <!-- Parent Ingress Correlation ID -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  Parent Ingress Request ID (UUID)
                </label>
                <input
                  v-model="localFilters.parent_request_id"
                  type="text"
                  placeholder="Search by parent request correlation ID..."
                  class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                />
              </div>

              <!-- Response Duration Range -->
              <div class="space-y-2">
                <label class="font-bold uppercase tracking-wider text-text-muted text-[11px] block">
                  Egress Response Duration (ms)
                </label>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <span class="text-[10px] text-text-muted block mb-1">Min Duration (ms)</span>
                    <input
                      v-model="localFilters.duration_min"
                      type="number"
                      min="0"
                      placeholder="0"
                      class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                    />
                  </div>
                  <div>
                    <span class="text-[10px] text-text-muted block mb-1">Max Duration (ms)</span>
                    <input
                      v-model="localFilters.duration_max"
                      type="number"
                      min="0"
                      placeholder="5000"
                      class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                    />
                  </div>
                </div>
              </div>

              <!-- Date / Time Window -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label class="font-bold uppercase tracking-wider text-text-muted text-[11px]">
                    Date & Time Range
                  </label>
                  <div class="flex items-center gap-1">
                    <button type="button" @click="setQuickDate(1)" class="text-[10px] text-accent hover:underline">1h</button>
                    <span class="text-text-muted text-[10px]">|</span>
                    <button type="button" @click="setQuickDate(24)" class="text-[10px] text-accent hover:underline">24h</button>
                    <span class="text-text-muted text-[10px]">|</span>
                    <button type="button" @click="setQuickDate(168)" class="text-[10px] text-accent hover:underline">7d</button>
                  </div>
                </div>
                <div class="space-y-2">
                  <div>
                    <span class="text-[10px] text-text-muted block mb-1">From:</span>
                    <input
                      v-model="localFilters.date_from"
                      type="datetime-local"
                      class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                    />
                  </div>
                  <div>
                    <span class="text-[10px] text-text-muted block mb-1">To:</span>
                    <input
                      v-model="localFilters.date_to"
                      type="datetime-local"
                      class="w-full px-3 py-2 bg-surface-elevated border border-border-default rounded-xl text-text-primary focus:outline-none focus:border-accent font-mono text-xs"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Actions -->
            <div class="p-5 border-t border-border-subtle bg-surface-raised flex items-center justify-between gap-3">
              <button
                type="button"
                @click="resetFilters"
                class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
              >
                <RotateCcw class="w-3.5 h-3.5 text-text-muted" />
                <span>Reset</span>
              </button>

              <button
                type="button"
                @click="applyFilters"
                class="btn btn-primary text-xs px-5 py-2 rounded-xl font-bold flex-1"
              >
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
