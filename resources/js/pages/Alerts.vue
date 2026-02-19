<script setup>
import { ref, onMounted } from 'vue';
import { useRequests } from '../composables/useRequests';
import { Bell, Clock, Zap, AlertTriangle, Hash, Send, Loader2, CheckCircle } from 'lucide-vue-next';

const { fetchConfig, loading } = useRequests();

const config = ref({});
const testLoading = ref(false);
const toast = ref(null);

const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => (toast.value = null), 3000);
};

const loadConfig = async () => { config.value = await fetchConfig(); };

const handleTestAlert = async () => {
  testLoading.value = true;
  try {
    const response = await fetch('/api-watcher/api/actions/test-alert', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    });
    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to send test alert');
    }
    showToast('Test alert sent! Check your channels.');
  } catch (e) {
    showToast('Error: ' + e.message, 'error');
  } finally {
    testLoading.value = false;
  }
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
      <h1 class="text-2xl font-bold text-text-primary">Alerts & Monitoring</h1>
      <p class="text-sm text-text-muted mt-0.5">Alert configuration and notifications</p>
    </div>

    <!-- Status -->
    <div class="glass-surface p-5">
      <div class="flex items-center gap-3">
        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', config.alerts_enabled ? 'bg-success-soft' : 'bg-surface-elevated']">
          <Bell :class="['w-5 h-5', config.alerts_enabled ? 'text-success' : 'text-text-muted']" />
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-text-primary">Monitoring</span>
            <span :class="[config.alerts_enabled ? 'bg-success-soft text-success' : 'bg-surface-elevated text-text-muted', 'badge text-[11px]']">
              <span :class="['inline-block w-1.5 h-1.5 rounded-full mr-1.5', config.alerts_enabled ? 'bg-success animate-pulse' : 'bg-text-muted']" />
              {{ config.alerts_enabled ? 'Active' : 'Disabled' }}
            </span>
          </div>
          <p v-if="!config.alerts_enabled" class="text-xs text-text-muted mt-0.5">Enable in .env with API_WATCHER_ALERTS_ENABLED=true</p>
        </div>
      </div>
    </div>

    <!-- Configuration -->
    <div class="glass-surface overflow-hidden">
      <div class="px-5 py-4 border-b border-border-subtle">
        <h3 class="text-sm font-semibold text-text-primary">Configuration</h3>
        <p class="text-xs text-text-muted mt-0.5">Current alert thresholds</p>
      </div>
      <div class="divide-y divide-border-subtle">
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-accent-soft flex items-center justify-center"><Clock class="w-4 h-4 text-accent" /></div>
            <span class="text-sm text-text-primary">Check Interval</span>
          </div>
          <span class="text-sm text-text-secondary">{{ config.alerts_interval }} min</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-danger-soft flex items-center justify-center"><AlertTriangle class="w-4 h-4 text-danger" /></div>
            <span class="text-sm text-text-primary">Error Rate Threshold</span>
          </div>
          <span class="text-sm text-danger font-medium">&gt; {{ config.alerts_threshold_error }}%</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-warning-soft flex items-center justify-center"><Zap class="w-4 h-4 text-warning" /></div>
            <span class="text-sm text-text-primary">Latency Threshold</span>
          </div>
          <span class="text-sm text-warning font-medium">&gt; {{ config.alerts_threshold_latency }}ms</span>
        </div>
        <div class="flex items-center justify-between px-5 py-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-info-soft flex items-center justify-center"><Hash class="w-4 h-4 text-info" /></div>
            <span class="text-sm text-text-primary">Channels</span>
          </div>
          <div class="flex gap-1.5">
            <span v-for="ch in config.alerts_channels" :key="ch" class="badge bg-accent-soft text-accent text-[11px] uppercase">{{ ch }}</span>
            <span v-if="!config.alerts_channels?.length" class="text-sm text-text-muted italic">None</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Test -->
    <div class="glass-surface p-5">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h3 class="text-sm font-semibold text-text-primary">Test Notification</h3>
          <p class="text-xs text-text-muted mt-0.5">Send a test alert to verify your channels.</p>
        </div>
        <button
          @click="handleTestAlert"
          :disabled="testLoading || !config.alerts_enabled"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-[10px] transition-all whitespace-nowrap',
            !config.alerts_enabled
              ? 'bg-surface-elevated text-text-muted cursor-not-allowed'
              : 'bg-accent text-text-inverted hover:bg-accent-hover'
          ]"
        >
          <Loader2 v-if="testLoading" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          {{ testLoading ? 'Sending...' : 'Send Test' }}
        </button>
      </div>
      <p v-if="!config.alerts_enabled" class="text-xs text-danger mt-3">Enable alerts in configuration first.</p>
    </div>
  </div>
</template>
