<script setup>
import { ref, onMounted } from 'vue';
import { useRequests } from '../composables/useRequests';
import { Shield, Database, Clock, Eye, EyeOff, Lock, Trash2, Scissors, AlertTriangle, CheckCircle } from 'lucide-vue-next';

const { fetchConfig, triggerPrune, triggerClear, loading } = useRequests();

const config = ref({});
const confirmClear = ref(false);
const confirmPrune = ref(false);
const pruneDays = ref(30);
const toast = ref(null);

const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => (toast.value = null), 3000);
};

const loadConfig = async () => { config.value = await fetchConfig(); };

const handlePrune = async () => {
  if (!confirmPrune.value) { confirmPrune.value = true; return; }
  try {
    await triggerPrune(pruneDays.value);
    showToast('Requests pruned successfully.');
    confirmPrune.value = false;
  } catch (e) { showToast('Error: ' + e.message, 'error'); }
};

const handleClear = async () => {
  if (!confirmClear.value) { confirmClear.value = true; return; }
  try {
    await triggerClear();
    showToast('All requests cleared.');
    confirmClear.value = false;
  } catch (e) { showToast('Error: ' + e.message, 'error'); }
};

onMounted(loadConfig);
</script>

<template>
  <div class="space-y-6 animate-[fade-in_0.3s_ease]">
    <!-- Toast -->
    <transition name="slide-up">
      <div v-if="toast" :class="['fixed top-5 right-5 z-50 flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium shadow-lg', toast.type === 'error' ? 'bg-danger text-white' : 'bg-success text-text-inverted']">
        <component :is="toast.type === 'error' ? AlertTriangle : CheckCircle" class="w-4 h-4" />
        {{ toast.message }}
      </div>
    </transition>

    <div>
      <h1 class="text-2xl font-bold text-text-primary">Settings</h1>
      <p class="text-sm text-text-muted mt-0.5">Configuration and data management</p>
    </div>

    <!-- Config -->
    <div class="glass-surface overflow-hidden">
      <div class="px-5 py-4 border-b border-border-subtle">
        <h3 class="text-sm font-semibold text-text-primary">Configuration</h3>
        <p class="text-xs text-text-muted mt-0.5">Loaded from config/api-watcher.php</p>
      </div>
      <div class="divide-y divide-border-subtle">
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-success-soft flex items-center justify-center"><Shield class="w-4 h-4 text-success" /></div>
            <span class="text-sm text-text-primary">Enabled</span>
          </div>
          <span :class="[config.enabled ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger', 'badge text-xs']">{{ config.enabled ? 'Yes' : 'No' }}</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-info-soft flex items-center justify-center">
              <component :is="config.record_requests ? Eye : EyeOff" class="w-4 h-4 text-info" />
            </div>
            <span class="text-sm text-text-primary">Record Requests</span>
          </div>
          <div class="flex items-center gap-2">
            <span :class="[config.record_requests ? 'bg-success-soft text-success' : 'bg-surface-elevated text-text-muted', 'badge text-xs']">{{ config.record_requests ? 'All' : 'None' }}</span>
            <span v-if="config.failed_only" class="badge bg-warning-soft text-warning text-xs">Failed Only</span>
          </div>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-accent-soft flex items-center justify-center"><Clock class="w-4 h-4 text-accent" /></div>
            <span class="text-sm text-text-primary">Pruning</span>
          </div>
          <span class="text-sm text-text-secondary">{{ config.pruning_days }} days</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-accent-soft flex items-center justify-center"><Database class="w-4 h-4 text-accent" /></div>
            <span class="text-sm text-text-primary">Storage Driver</span>
          </div>
          <span class="text-sm text-text-secondary capitalize">{{ config.storage_driver }}</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-warning-soft flex items-center justify-center"><Lock class="w-4 h-4 text-warning" /></div>
            <span class="text-sm text-text-primary">Sensitive Fields</span>
          </div>
          <span class="text-sm text-text-muted font-mono">{{ Array.isArray(config.sensitive_fields) ? config.sensitive_fields.join(', ') : '—' }}</span>
        </div>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="glass-surface overflow-hidden border-danger/20">
      <div class="px-5 py-4 border-b border-danger/20 bg-danger-soft">
        <h3 class="text-sm font-semibold text-danger">Danger Zone</h3>
        <p class="text-xs text-danger/70 mt-0.5">Irreversible actions</p>
      </div>
      <div class="divide-y divide-border-subtle">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4">
          <div>
            <h4 class="text-sm font-medium text-text-primary flex items-center gap-2"><Scissors class="w-4 h-4 text-warning" /> Prune Old Requests</h4>
            <p class="text-xs text-text-muted mt-0.5">Delete requests older than the specified days.</p>
          </div>
          <div class="flex items-center gap-2">
            <input type="number" v-model="pruneDays" class="w-20 text-center" min="1" />
            <button
              @click="handlePrune"
              :disabled="loading"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-[10px] transition-all whitespace-nowrap',
                confirmPrune ? 'bg-danger text-white hover:bg-red-600' : 'glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover'
              ]"
            >
              {{ confirmPrune ? 'Confirm Prune' : 'Prune Now' }}
            </button>
          </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4">
          <div>
            <h4 class="text-sm font-medium text-text-primary flex items-center gap-2"><Trash2 class="w-4 h-4 text-danger" /> Clear All Requests</h4>
            <p class="text-xs text-text-muted mt-0.5">Permanently delete ALL recorded data.</p>
          </div>
          <button
            @click="handleClear"
            :disabled="loading"
            :class="[
              'px-4 py-2 text-sm font-medium rounded-[10px] transition-all whitespace-nowrap',
              confirmClear ? 'bg-danger text-white hover:bg-red-600' : 'glass-surface text-danger hover:bg-danger-soft'
            ]"
          >
            {{ confirmClear ? 'Confirm Clear All' : 'Clear All Data' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
