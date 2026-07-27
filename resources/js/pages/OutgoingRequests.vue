<script setup>
import { ref, onMounted } from 'vue';
import { ExternalLink, Globe, Clock, AlertTriangle, ArrowLeftRight } from 'lucide-vue-next';

const outgoingRequests = ref([]);
const stats = ref({
  total_outgoing_requests: 0,
  error_rate: 0,
  avg_latency: 0,
  top_domains: []
});
const loading = ref(true);

const fetchOutgoingData = async () => {
  loading.value = true;
  try {
    const [reqRes, statsRes] = await Promise.all([
      fetch('/api-watcher/api/outgoing-requests').then(r => r.json()),
      fetch('/api-watcher/api/outgoing-requests/stats').then(r => r.json())
    ]);
    outgoingRequests.value = reqRes;
    stats.value = statsRes;
  } catch (err) {
    console.error('Failed to fetch outgoing requests data', err);
  } finally {
    loading.value = false;
  }
};

const getStatusBadgeClass = (code) => {
  if (code >= 200 && code < 300) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
  if (code >= 400 || code === 0) return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
  return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
};

onMounted(() => {
  fetchOutgoingData();
});
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
          <ExternalLink class="w-6 h-6 text-accent" /> Outgoing API Observability (Egress)
        </h1>
        <p class="text-sm text-text-secondary">Track third-party HTTP calls made by your application (Stripe, OpenAI, Twilio, etc.)</p>
      </div>
      <button @click="fetchOutgoingData" class="btn btn-secondary text-xs">Refresh</button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card p-4 flex items-center gap-4">
        <div class="p-3 rounded-xl bg-accent-soft text-accent">
          <Globe class="w-6 h-6" />
        </div>
        <div>
          <div class="text-xs font-medium text-text-secondary">Total Outgoing Calls</div>
          <div class="text-2xl font-bold text-text-primary">{{ stats.total_outgoing_requests }}</div>
        </div>
      </div>

      <div class="card p-4 flex items-center gap-4">
        <div class="p-3 rounded-xl bg-amber-500/10 text-amber-400">
          <Clock class="w-6 h-6" />
        </div>
        <div>
          <div class="text-xs font-medium text-text-secondary">Average Egress Latency</div>
          <div class="text-2xl font-bold text-text-primary">{{ stats.avg_latency }} ms</div>
        </div>
      </div>

      <div class="card p-4 flex items-center gap-4">
        <div class="p-3 rounded-xl bg-rose-500/10 text-rose-400">
          <AlertTriangle class="w-6 h-6" />
        </div>
        <div>
          <div class="text-xs font-medium text-text-secondary">Third-Party Error Rate</div>
          <div class="text-2xl font-bold text-text-primary">{{ stats.error_rate }}%</div>
        </div>
      </div>
    </div>

    <!-- Top External Domains -->
    <div v-if="stats.top_domains && stats.top_domains.length > 0" class="card p-5">
      <h2 class="text-sm font-semibold text-text-primary mb-3">Top External Domains</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="d in stats.top_domains" :key="d.domain" class="p-3 rounded-lg bg-surface-hover border border-border-subtle flex flex-col justify-between">
          <div class="font-mono text-xs font-bold text-accent truncate">{{ d.domain }}</div>
          <div class="mt-2 flex items-center justify-between text-xs text-text-secondary">
            <span>{{ d.count }} calls</span>
            <span class="font-semibold text-text-primary">{{ d.avg_duration }} ms avg</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Outgoing Log Table -->
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-border-subtle flex items-center justify-between">
        <h2 class="text-sm font-semibold text-text-primary">Recent Egress HTTP Requests</h2>
      </div>

      <div v-if="loading" class="p-8 text-center text-text-muted">Loading outgoing requests...</div>
      <div v-else-if="outgoingRequests.length === 0" class="p-8 text-center text-text-muted">No outgoing HTTP calls captured yet.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-surface-raised border-b border-border-subtle text-text-muted font-medium uppercase">
            <tr>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Method</th>
              <th class="px-4 py-3">Domain & Target URL</th>
              <th class="px-4 py-3">Duration</th>
              <th class="px-4 py-3">Timestamp</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-subtle">
            <tr v-for="req in outgoingRequests" :key="req.id" class="hover:bg-surface-hover">
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border', getStatusBadgeClass(req.status_code)]">
                  {{ req.status_code === 0 ? 'FAILED' : req.status_code }}
                </span>
              </td>
              <td class="px-4 py-3 font-mono font-bold text-text-primary">{{ req.method }}</td>
              <td class="px-4 py-3 font-mono text-text-secondary max-w-md truncate" :title="req.url">
                <span class="text-accent font-semibold">{{ req.domain }}</span> - {{ req.url }}
              </td>
              <td class="px-4 py-3 font-mono font-medium text-text-primary">{{ req.duration_ms }} ms</td>
              <td class="px-4 py-3 text-text-muted">{{ new Date(req.created_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
