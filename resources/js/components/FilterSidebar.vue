<script setup>
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({ isOpen: Boolean, filters: Object });
const emit = defineEmits(['close', 'apply', 'reset']);

const localFilters = ref({ ...props.filters });

watch(() => props.filters, (n) => { localFilters.value = { ...n }; }, { deep: true });

const methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
const statusGroups = [
  { label: '2xx Success', value: '2xx', color: 'bg-success-soft text-success border-success/20' },
  { label: '3xx Redirect', value: '3xx', color: 'bg-info-soft text-info border-info/20' },
  { label: '4xx Client', value: '4xx', color: 'bg-warning-soft text-warning border-warning/20' },
  { label: '5xx Server', value: '5xx', color: 'bg-danger-soft text-danger border-danger/20' },
];

const methodColors = {
  GET: 'bg-info-soft text-info border-info/20',
  POST: 'bg-success-soft text-success border-success/20',
  PUT: 'bg-warning-soft text-warning border-warning/20',
  DELETE: 'bg-danger-soft text-danger border-danger/20',
  PATCH: 'bg-accent-soft text-accent border-accent/20',
  OPTIONS: 'bg-surface-elevated text-text-secondary border-border-default',
  HEAD: 'bg-surface-elevated text-text-secondary border-border-default',
};

const toggleMethod = (m) => {
  const idx = localFilters.value.method.indexOf(m);
  if (idx >= 0) localFilters.value.method.splice(idx, 1);
  else localFilters.value.method.push(m);
};

const toggleStatus = (s) => {
  const idx = localFilters.value.status_code.indexOf(s);
  if (idx >= 0) localFilters.value.status_code.splice(idx, 1);
  else localFilters.value.status_code.push(s);
};

const applyFilters = () => emit('apply', localFilters.value);
const resetFilters = () => {
  localFilters.value = { method: [], status_code: [], url: '', ip_address: '', user_id: '', date_from: '', date_to: '', duration_min: '', duration_max: '' };
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
              <h2 class="text-lg font-semibold text-text-primary">Filters</h2>
              <button @click="emit('close')" class="p-1.5 rounded-lg hover:bg-surface-hover transition-colors text-text-muted hover:text-text-primary">
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-6">
              <!-- Methods -->
              <div>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Methods</h3>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="m in methods"
                    :key="m"
                    @click="toggleMethod(m)"
                    :class="[
                      'px-3 py-1.5 text-xs font-semibold rounded-full border transition-all duration-200',
                      localFilters.method.includes(m)
                        ? methodColors[m]
                        : 'bg-transparent border-border-default text-text-muted hover:text-text-primary hover:border-border-strong'
                    ]"
                  >
                    {{ m }}
                  </button>
                </div>
              </div>

              <!-- Status -->
              <div>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Status Codes</h3>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="s in statusGroups"
                    :key="s.value"
                    @click="toggleStatus(s.value)"
                    :class="[
                      'px-3 py-1.5 text-xs font-semibold rounded-full border transition-all duration-200',
                      localFilters.status_code.includes(s.value)
                        ? s.color
                        : 'bg-transparent border-border-default text-text-muted hover:text-text-primary hover:border-border-strong'
                    ]"
                  >
                    {{ s.label }}
                  </button>
                </div>
              </div>

              <div class="space-y-3">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[15px] text-text-muted px-1">URL/PATH</label>
                    <input type="text" v-model="localFilters.url" placeholder="/api/users" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[15px] text-text-muted px-1">IP ADDRESS</label>
                    <input type="text" v-model="localFilters.ip_address" placeholder="127.0.0.1" />
                  </div>
                   <div class="flex flex-col gap-1.5">
                    <label class="text-[15px] text-text-muted px-1">USER ID</label>
                    <input type="text" v-model="localFilters.user_id" placeholder="1" />
                  </div>
                </div>

              <!-- Date Range -->
              <div>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Date Range</h3>
                <div class="space-y-3">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] text-text-muted px-1">From</label>
                    <input type="datetime-local" v-model="localFilters.date_from" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] text-text-muted px-1">To</label>
                    <input type="datetime-local" v-model="localFilters.date_to" />
                  </div>
                </div>
              </div>

              <!-- Duration -->
              <div>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Duration</h3>
                <div class="grid grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] text-text-muted px-1">Min (ms)</label>
                    <input type="number" v-model="localFilters.duration_min" placeholder="0" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[11px] text-text-muted px-1">Max (ms)</label>
                    <input type="number" v-model="localFilters.duration_max" placeholder="max" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4 border-t border-border-subtle flex gap-3">
              <button @click="resetFilters" class="flex-1 py-2.5 text-sm font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover rounded-[10px] transition-all">
                Reset
              </button>
              <button @click="applyFilters" class="flex-1 py-2.5 text-sm font-medium bg-accent text-text-inverted rounded-[10px] hover:bg-accent-hover transition-all">
                Apply Filters
              </button>
            </div>
          </div>
        </transition>
      </div>
    </transition>
  </teleport>
</template>
