<script setup>
import { ref, onMounted } from 'vue';
import { ShieldAlert, TrendingUp, AlertOctagon, CheckCircle2 } from 'lucide-vue-next';

const diagnostics = ref({
  latency_trend: {
    has_degradation: false,
    delta_percentage: 0,
    recent_avg_ms: 0,
    baseline_avg_ms: 0
  },
  suspicious_ips: []
});
const loading = ref(true);

const fetchDiagnostics = async () => {
  loading.value = true;
  try {
    const res = await fetch('/api-watcher/api/diagnostics').then(r => r.json());
    diagnostics.value = res;
  } catch (err) {
    console.error('Failed to fetch diagnostics', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchDiagnostics();
});
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
          <ShieldAlert class="w-6 h-6 text-accent" /> API Diagnostics & Abuse Monitor
        </h1>
        <p class="text-sm text-text-secondary">Predictive latency trend detection & suspicious traffic rate-limit analysis</p>
      </div>
      <button @click="fetchDiagnostics" class="btn btn-secondary text-xs">Refresh</button>
    </div>

    <!-- Latency Degradation Card -->
    <div class="card p-6">
      <h2 class="text-sm font-semibold text-text-primary flex items-center gap-2 mb-4">
        <TrendingUp class="w-5 h-5 text-accent" /> Latency Degradation Trend (24h vs 7-Day Baseline)
      </h2>

      <div v-if="loading" class="text-xs text-text-muted">Analyzing latency trends...</div>
      <div v-else>
        <div v-if="diagnostics.latency_trend.has_degradation" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-start gap-3">
          <AlertOctagon class="w-6 h-6 flex-shrink-0 mt-0.5" />
          <div>
            <div class="font-bold text-sm">Latency Degradation Warning (+{{ diagnostics.latency_trend.delta_percentage }}%)</div>
            <div class="text-xs mt-1">Average latency in the last 24h ({{ diagnostics.latency_trend.recent_avg_ms }} ms) is significantly higher than the 7-day baseline ({{ diagnostics.latency_trend.baseline_avg_ms }} ms).</div>
          </div>
        </div>
        <div v-else class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center gap-3">
          <CheckCircle2 class="w-6 h-6 flex-shrink-0" />
          <div>
            <div class="font-bold text-sm">Latency Trend Normal</div>
            <div class="text-xs mt-0.5">Recent average latency ({{ diagnostics.latency_trend.recent_avg_ms }} ms) is within healthy bounds of baseline ({{ diagnostics.latency_trend.baseline_avg_ms }} ms).</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Suspicious IP Table -->
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-border-subtle">
        <h2 class="text-sm font-semibold text-text-primary">Bot & Rate Limit Abuse Monitor (High Volume / Error IPs)</h2>
      </div>

      <div v-if="loading" class="p-6 text-center text-xs text-text-muted">Checking IP patterns...</div>
      <div v-else-if="diagnostics.suspicious_ips.length === 0" class="p-6 text-center text-xs text-text-muted">No suspicious IP behavior detected in the last 60 minutes.</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase font-medium">
            <tr>
              <th class="px-4 py-3">IP Address</th>
              <th class="px-4 py-3">Total Requests (60m)</th>
              <th class="px-4 py-3">401/429/403 Errors</th>
              <th class="px-4 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-subtle">
            <tr v-for="ip in diagnostics.suspicious_ips" :key="ip.ip_address" class="hover:bg-surface-hover">
              <td class="px-4 py-3 font-mono font-bold text-accent">{{ ip.ip_address }}</td>
              <td class="px-4 py-3 font-mono text-text-primary">{{ ip.total_requests }}</td>
              <td class="px-4 py-3 font-mono text-rose-400 font-bold">{{ ip.error_count }}</td>
              <td class="px-4 py-3">
                <span v-if="ip.is_suspicious" class="px-2 py-0.5 text-[11px] font-bold rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">
                  SUSPICIOUS ABUSE
                </span>
                <span v-else class="px-2 py-0.5 text-[11px] font-bold rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                  HIGH VOLUME
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
