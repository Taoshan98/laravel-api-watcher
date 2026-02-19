<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRequests } from '../composables/useRequests';
import { formatDistanceToNow } from 'date-fns';
import ReplayModal from '../components/ReplayModal.vue';
import { ArrowLeft, Copy, RefreshCw, Clock, HardDrive, Globe, CheckCheck } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const { request, loading, fetchRequest, replayRequest } = useRequests();

const activeTab = ref('payload');
const isReplayModalOpen = ref(false);
const replayLoading = ref(false);
const replayResponse = ref(null);
const replayError = ref(null);
const copied = ref(false);

const handleReplay = async () => {
  isReplayModalOpen.value = true;
  replayLoading.value = true;
  replayResponse.value = null;
  replayError.value = null;
  try {
    replayResponse.value = await replayRequest(request.value);
  } catch (e) {
    replayError.value = e.message;
  } finally {
    replayLoading.value = false;
  }
};

const copyCurl = () => {
  if (!request.value) return;
  const req = request.value;
  let curl = `curl -X ${req.method} "${req.url}"`;
  if (req.request_headers) {
    Object.entries(req.request_headers).forEach(([key, value]) => {
      curl += ` \\\n  -H "${key}: ${value}"`;
    });
  }
  if (req.request_body && req.method !== 'GET') {
    const body = typeof req.request_body === 'string' ? req.request_body : JSON.stringify(req.request_body);
    curl += ` \\\n  -d '${body.replace(/'/g, "'\\''")}'`;
  }
  navigator.clipboard.writeText(curl);
  copied.value = true;
  setTimeout(() => (copied.value = false), 2000);
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

const tabs = [
  { id: 'payload', label: 'Payload' },
  { id: 'headers', label: 'Headers' },
  { id: 'timeline', label: 'Timeline' },
];

const formatJson = (data) => {
  if (!data) return 'No data';
  if (typeof data === 'object') return JSON.stringify(data, null, 2);
  try { return JSON.stringify(JSON.parse(data), null, 2); } catch { return data; }
};

const getExceptionInfo = (req) => {
  if (!req.exception_info) return null;
  return typeof req.exception_info === 'string' ? JSON.parse(req.exception_info) : req.exception_info;
};

onMounted(() => fetchRequest(route.params.id));
</script>

<template>
  <div class="space-y-5 animate-[fade-in_0.3s_ease]">
    <div v-if="loading" class="space-y-4">
      <div class="skeleton h-8 w-48" />
      <div class="skeleton h-6 w-96" />
      <div class="skeleton h-[400px]" />
    </div>

    <div v-else-if="request">
      <!-- Back + Actions -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="space-y-2">
          <button @click="router.push('/requests')" class="flex items-center gap-1.5 text-sm text-text-muted hover:text-text-primary transition-colors mb-2">
            <ArrowLeft class="w-4 h-4" /> Back to requests
          </button>
          <div class="flex items-center gap-3 flex-wrap">
            <span :class="[methodColor(request.method), 'badge text-xs']">{{ request.method }}</span>
            <h1 class="text-lg font-semibold text-text-primary break-all font-mono">{{ request.url }}</h1>
          </div>
          <div class="flex items-center gap-3 flex-wrap text-sm">
            <span :class="[statusColor(request.status_code), 'badge text-xs']">{{ request.status_code }}</span>
            <span class="flex items-center gap-1 text-text-muted"><Clock class="w-3.5 h-3.5" /> {{ request.duration_ms }}ms</span>
            <span class="text-text-muted">{{ formatDistanceToNow(new Date(request.created_at), { addSuffix: true }) }}</span>
            <span v-if="request.ip_address" class="flex items-center gap-1 text-text-muted"><Globe class="w-3.5 h-3.5" /> {{ request.ip_address }}</span>
          </div>
        </div>
        <div class="flex gap-2 flex-shrink-0">
          <button
            @click="copyCurl"
            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all rounded-[10px]"
          >
            <component :is="copied ? CheckCheck : Copy" class="w-4 h-4" />
            {{ copied ? 'Copied!' : 'cURL' }}
          </button>
          <button
            @click="handleReplay"
            class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-accent text-text-inverted rounded-[10px] hover:bg-accent-hover transition-all"
          >
            <RefreshCw class="w-4 h-4" /> Replay
          </button>
        </div>
      </div>

      <!-- Tabs -->
      <div class="glass-surface overflow-hidden mt-2">
        <div class="flex border-b border-border-subtle px-1 pt-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all duration-200',
              activeTab === tab.id
                ? 'bg-surface-elevated text-accent border-b-2 border-accent mb-[-1px]'
                : 'text-text-muted hover:text-text-primary'
            ]"
          >
            {{ tab.label }}
          </button>
          <button
            v-if="request.status_code >= 500 && request.exception_info"
            @click="activeTab = 'exception'"
            :class="[
              'px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all duration-200',
              activeTab === 'exception'
                ? 'bg-danger-soft text-danger border-b-2 border-danger mb-[-1px]'
                : 'text-text-muted hover:text-danger'
            ]"
          >
            Exception
          </button>
        </div>

        <div class="p-5">
          <!-- Payload -->
          <div v-if="activeTab === 'payload'" class="space-y-5">
            <div>
              <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Request Body</h3>
              <div class="bg-surface-elevated rounded-xl p-4 overflow-x-auto">
                <pre class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap">{{ formatJson(request.request_body) }}</pre>
              </div>
            </div>
            <div>
              <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Response Body</h3>
              <div class="bg-surface-elevated rounded-xl p-4 overflow-x-auto">
                <pre class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap">{{ formatJson(request.response_body) }}</pre>
              </div>
            </div>
          </div>

          <!-- Headers -->
          <div v-if="activeTab === 'headers'" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Request Headers</h3>
              <div class="bg-surface-elevated rounded-xl p-4 space-y-1.5 overflow-x-auto">
                <template v-if="request.request_headers">
                  <div v-for="(value, key) in request.request_headers" :key="key" class="text-xs">
                    <span class="font-semibold text-accent">{{ key }}:</span>
                    <span class="text-text-secondary ml-1.5 break-all">{{ value }}</span>
                  </div>
                </template>
                <p v-else class="text-xs text-text-muted">No headers</p>
              </div>
            </div>
            <div>
              <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2">Response Headers</h3>
              <div class="bg-surface-elevated rounded-xl p-4 space-y-1.5 overflow-x-auto">
                <template v-if="request.response_headers">
                  <div v-for="(value, key) in request.response_headers" :key="key" class="text-xs">
                    <span class="font-semibold text-accent">{{ key }}:</span>
                    <span class="text-text-secondary ml-1.5 break-all">{{ value }}</span>
                  </div>
                </template>
                <p v-else class="text-xs text-text-muted">No headers</p>
              </div>
            </div>
          </div>

          <!-- Timeline -->
          <div v-if="activeTab === 'timeline'" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="glass-surface p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-accent-soft flex items-center justify-center flex-shrink-0">
                  <Clock class="w-5 h-5 text-accent" />
                </div>
                <div>
                  <p class="text-xs text-text-muted">Total Duration</p>
                  <p class="text-xl font-bold text-text-primary">{{ request.duration_ms }}<span class="text-sm font-normal text-text-muted ml-0.5">ms</span></p>
                </div>
              </div>
              <div class="glass-surface p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-info-soft flex items-center justify-center flex-shrink-0">
                  <HardDrive class="w-5 h-5 text-info" />
                </div>
                <div>
                  <p class="text-xs text-text-muted">Memory Usage</p>
                  <p class="text-xl font-bold text-text-primary">{{ request.memory_usage_kb }}<span class="text-sm font-normal text-text-muted ml-0.5">KB</span></p>
                </div>
              </div>
            </div>
            <div>
              <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Waterfall</h3>
              <div class="relative h-10 bg-surface-elevated rounded-full overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-accent/30 via-accent/50 to-accent/30 flex items-center justify-center">
                  <span class="text-xs font-semibold text-text-primary">Application Processing ({{ request.duration_ms }}ms)</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Exception -->
          <div v-if="activeTab === 'exception'" class="space-y-4">
            <template v-if="getExceptionInfo(request)">
              <div class="p-4 rounded-xl border border-danger/30 bg-danger-soft">
                <h3 class="text-base font-semibold text-danger mb-1">{{ getExceptionInfo(request).message }}</h3>
                <p class="text-xs text-text-muted font-mono">
                  {{ getExceptionInfo(request).file }}:{{ getExceptionInfo(request).line }}
                </p>
              </div>
              <div class="bg-surface-base rounded-xl p-4 overflow-x-auto border border-border-subtle">
                <pre class="text-xs text-success font-mono leading-relaxed whitespace-pre-wrap">{{ getExceptionInfo(request).trace }}</pre>
              </div>
            </template>
            <p v-else class="text-center text-text-muted text-sm py-8">No exception details available.</p>
          </div>
        </div>
      </div>
    </div>

    <ReplayModal
      :is-open="isReplayModalOpen"
      :loading="replayLoading"
      :response="replayResponse"
      :error="replayError"
      @close="isReplayModalOpen = false"
    />
  </div>
</template>
