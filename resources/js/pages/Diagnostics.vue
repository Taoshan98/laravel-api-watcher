<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  ShieldAlert,
  TrendingUp,
  AlertOctagon,
  CheckCircle2,
  Clock,
  Activity,
  Search,
  X,
  Copy,
  CheckCheck,
  ExternalLink,
  ChevronDown,
  ChevronUp,
  Maximize2,
  Minimize2,
  RefreshCw,
  SlidersHorizontal,
  AlertTriangle,
  Zap,
  Globe
} from 'lucide-vue-next';

const router = useRouter();

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
const searchIp = ref('');
const ipFilterType = ref('all'); // 'all' | 'suspicious' | 'high_volume'
const copiedField = ref(null);

const collapsed = ref({
  trend: false,
  abuse: false
});

const toggleSection = (key) => {
  collapsed.value[key] = !collapsed.value[key];
};

const allExpanded = computed(() => Object.values(collapsed.value).every(v => !v));

const toggleAllSections = () => {
  const target = allExpanded.value;
  Object.keys(collapsed.value).forEach((key) => {
    collapsed.value[key] = target;
  });
};

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

const copyToClipboard = (text, fieldName) => {
  if (!text) return;
  navigator.clipboard.writeText(String(text));
  copiedField.value = fieldName;
  setTimeout(() => {
    if (copiedField.value === fieldName) copiedField.value = null;
  }, 2000);
};

const filteredIps = computed(() => {
  let list = diagnostics.value?.suspicious_ips || [];
  if (ipFilterType.value === 'suspicious') {
    list = list.filter(item => item.is_suspicious);
  } else if (ipFilterType.value === 'high_volume') {
    list = list.filter(item => !item.is_suspicious);
  }

  if (searchIp.value.trim()) {
    const q = searchIp.value.trim().toLowerCase();
    list = list.filter(item => item.ip_address.toLowerCase().includes(q));
  }
  return list;
});

const suspiciousCount = computed(() => {
  return (diagnostics.value?.suspicious_ips || []).filter(item => item.is_suspicious).length;
});

const highVolumeCount = computed(() => {
  return (diagnostics.value?.suspicious_ips || []).filter(item => !item.is_suspicious).length;
});

const investigateIp = (ip) => {
  router.push(`/requests?q=${encodeURIComponent(ip)}`);
};

onMounted(() => {
  fetchDiagnostics();
});
</script>

<template>
  <div class="space-y-6 pb-16 animate-[fade-in_0.3s_ease]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
          <ShieldAlert class="w-6 h-6 text-accent" /> API Diagnostics & Abuse Monitor
        </h1>
        <p class="text-sm text-text-secondary mt-0.5">
          Predictive latency trend detection & automated bot / rate limit abuse diagnostics
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="toggleAllSections"
          class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
          :title="allExpanded ? 'Collapse all Bento cards' : 'Expand all Bento cards'"
        >
          <component :is="allExpanded ? Minimize2 : Maximize2" class="w-3.5 h-3.5 text-text-muted" />
          <span class="hidden sm:inline">{{ allExpanded ? 'Collapse All' : 'Expand All' }}</span>
        </button>

        <button
          @click="fetchDiagnostics"
          class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
          title="Refresh Diagnostics"
        >
          <RefreshCw :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Master Bento KPI Strip (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- KPI 1: Latency Drift Delta -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Latency Drift</span>
          <div class="p-2 rounded-xl bg-accent-soft text-accent">
            <TrendingUp class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span :class="['text-2xl font-black font-mono tracking-tight', diagnostics.latency_trend?.has_degradation ? 'text-rose-400' : 'text-emerald-400']">
            {{ (diagnostics.latency_trend?.delta_percentage ?? 0) > 0 ? '+' : '' }}{{ diagnostics.latency_trend?.delta_percentage ?? 0 }}%
          </span>
        </div>
        <div class="mt-2 flex items-center gap-1.5">
          <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', diagnostics.latency_trend?.has_degradation ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30']">
            {{ diagnostics.latency_trend?.has_degradation ? 'Degradation Detected' : 'Healthy Trend' }}
          </span>
        </div>
      </div>

      <!-- KPI 2: 24h Moving Average -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-sky-400/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Recent 24h Average</span>
          <div class="p-2 rounded-xl bg-sky-500/10 text-sky-400">
            <Clock class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
            {{ diagnostics.latency_trend?.recent_avg_ms ?? 0 }}
          </span>
          <span class="text-xs font-mono text-text-muted">ms</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Active rolling 24h window
        </div>
      </div>

      <!-- KPI 3: 7-Day Baseline Average -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">7-Day Baseline</span>
          <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
            <Activity class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
            {{ diagnostics.latency_trend?.baseline_avg_ms ?? 0 }}
          </span>
          <span class="text-xs font-mono text-text-muted">ms</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Historical reference baseline
        </div>
      </div>

      <!-- KPI 4: Threat Vectors / Suspicious IPs -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-rose-500/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Flagged IP Callers</span>
          <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400">
            <ShieldAlert class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span :class="['text-2xl font-black font-mono tracking-tight', suspiciousCount > 0 ? 'text-rose-400' : 'text-text-primary']">
            {{ (diagnostics.suspicious_ips || []).length }}
          </span>
          <span class="text-xs font-mono text-text-muted">IPs</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted flex items-center justify-between">
          <span>{{ suspiciousCount }} active abuse</span>
          <span>{{ highVolumeCount }} high volume</span>
        </div>
      </div>
    </div>

    <!-- Bento Card 1: Predictive Latency Degradation Engine -->
    <div class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
      <div class="p-5 bg-surface-raised border-b border-border-subtle flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-accent-soft text-accent">
            <TrendingUp class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-text-primary">Predictive Latency Trend Analysis</h2>
            <p class="text-xs text-text-muted">Evaluates 24-hour moving average against a 7-day baseline to detect performance regression</p>
          </div>
        </div>

        <button
          @click="toggleSection('trend')"
          class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
        >
          <component :is="collapsed.trend ? ChevronDown : ChevronUp" class="w-4 h-4" />
        </button>
      </div>

      <div v-show="!collapsed.trend" class="p-6 space-y-6">
        <!-- Status Banner -->
        <div v-if="diagnostics.latency_trend?.has_degradation" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex items-start gap-3">
          <AlertOctagon class="w-5 h-5 flex-shrink-0 text-rose-400 mt-0.5" />
          <div class="space-y-1">
            <div class="font-bold text-sm text-rose-200">
              Significant Latency Degradation Detected (+{{ diagnostics.latency_trend?.delta_percentage }}%)
            </div>
            <div class="text-xs leading-relaxed text-rose-300">
              The 24-hour average latency ({{ diagnostics.latency_trend?.recent_avg_ms }} ms) has increased by
              <strong class="text-rose-100">+{{ diagnostics.latency_trend?.delta_percentage }}%</strong>
              over the 7-day baseline average ({{ diagnostics.latency_trend?.baseline_avg_ms }} ms), exceeding the +25% degradation alert threshold.
            </div>
          </div>
        </div>

        <div v-else class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-start gap-3">
          <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-400 mt-0.5" />
          <div class="space-y-1">
            <div class="font-bold text-sm text-emerald-200">
              Latency Trends Within Normal Parameters
            </div>
            <div class="text-xs leading-relaxed text-emerald-300">
              The 24-hour average latency ({{ diagnostics.latency_trend?.recent_avg_ms }} ms) is aligned with the 7-day baseline ({{ diagnostics.latency_trend?.baseline_avg_ms }} ms). No performance regression detected.
            </div>
          </div>
        </div>

        <!-- Comparative Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="p-4 rounded-xl bg-surface-elevated border border-border-subtle space-y-2">
            <div class="text-xs font-semibold text-text-muted uppercase tracking-wider">Recent 24-Hour Window</div>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black font-mono text-text-primary">{{ diagnostics.latency_trend?.recent_avg_ms ?? 0 }}</span>
              <span class="text-xs font-mono text-text-muted">ms avg</span>
            </div>
            <p class="text-[11px] text-text-muted">Calculated from all requests in the last 24h</p>
          </div>

          <div class="p-4 rounded-xl bg-surface-elevated border border-border-subtle space-y-2">
            <div class="text-xs font-semibold text-text-muted uppercase tracking-wider">7-Day Reference Baseline</div>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black font-mono text-purple-300">{{ diagnostics.latency_trend?.baseline_avg_ms ?? 0 }}</span>
              <span class="text-xs font-mono text-text-muted">ms avg</span>
            </div>
            <p class="text-[11px] text-text-muted">Established baseline between day -7 and day -1</p>
          </div>

          <div class="p-4 rounded-xl bg-surface-elevated border border-border-subtle space-y-2">
            <div class="text-xs font-semibold text-text-muted uppercase tracking-wider">Calculated Drift Variance</div>
            <div class="flex items-baseline gap-2">
              <span :class="['text-3xl font-black font-mono', diagnostics.latency_trend?.has_degradation ? 'text-rose-400' : 'text-emerald-400']">
                {{ (diagnostics.latency_trend?.delta_percentage ?? 0) > 0 ? '+' : '' }}{{ diagnostics.latency_trend?.delta_percentage ?? 0 }}%
              </span>
            </div>
            <p class="text-[11px] text-text-muted">Alert threshold configured at &ge; +25.0%</p>
          </div>
        </div>

        <!-- Algorithmic Explanation Callout -->
        <div class="p-4 rounded-xl bg-surface-base border border-border-subtle text-xs text-text-secondary space-y-1">
          <div class="font-semibold text-text-primary flex items-center gap-1.5">
            <Activity class="w-3.5 h-3.5 text-accent" />
            <span>Detection Algorithm: Moving Baseline Variance Formula</span>
          </div>
          <p class="font-mono text-[11px] text-accent">
            Delta% = ((Recent_24h_Avg - Baseline_7d_Avg) / Baseline_7d_Avg) * 100
          </p>
          <p class="text-[11px] text-text-muted pt-1">
            This predictive analysis ignores individual transient spikes and identifies systemic latency regressions caused by database bottlenecks, external API delays, or unoptimized application releases.
          </p>
        </div>
      </div>
    </div>

    <!-- Bento Card 2: Bot & Rate Limit Abuse Traffic Monitor -->
    <div class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
      <div class="p-5 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400">
            <ShieldAlert class="w-4 h-4" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-text-primary">Bot & Rate Limit Abuse Monitor</h2>
            <p class="text-xs text-text-muted">Analyzes suspicious traffic clusters, high-volume callers, and 401/403/429 authentication error spikes in the last 60 minutes</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- In-card Search Bar -->
          <div class="relative">
            <Search class="w-3.5 h-3.5 text-text-muted absolute left-2.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchIp"
              type="text"
              placeholder="Search IP address..."
              class="pl-8 pr-2.5 py-1 text-xs bg-surface-elevated border border-border-subtle rounded-lg text-text-primary focus:border-accent w-44"
            />
          </div>

          <!-- Filter Pills -->
          <div class="flex items-center bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-xs">
            <button
              type="button"
              @click="ipFilterType = 'all'"
              :class="['px-2 py-0.5 rounded-md font-medium transition-colors', ipFilterType === 'all' ? 'bg-surface-raised text-text-primary font-bold shadow-xs' : 'text-text-muted hover:text-text-primary']"
            >
              All ({{ (diagnostics.suspicious_ips || []).length }})
            </button>
            <button
              type="button"
              @click="ipFilterType = 'suspicious'"
              :class="['px-2 py-0.5 rounded-md font-medium transition-colors', ipFilterType === 'suspicious' ? 'bg-rose-500/20 text-rose-300 font-bold shadow-xs' : 'text-text-muted hover:text-rose-400']"
            >
              Abuse ({{ suspiciousCount }})
            </button>
            <button
              type="button"
              @click="ipFilterType = 'high_volume'"
              :class="['px-2 py-0.5 rounded-md font-medium transition-colors', ipFilterType === 'high_volume' ? 'bg-amber-500/20 text-amber-300 font-bold shadow-xs' : 'text-text-muted hover:text-amber-400']"
            >
              High Volume ({{ highVolumeCount }})
            </button>
          </div>

          <button
            @click="toggleSection('abuse')"
            class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
          >
            <component :is="collapsed.abuse ? ChevronDown : ChevronUp" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-show="!collapsed.abuse">
        <div v-if="loading" class="p-10 text-center text-text-muted space-y-2">
          <RefreshCw class="w-6 h-6 animate-spin mx-auto text-accent" />
          <p class="text-xs font-medium">Analyzing IP traffic vectors...</p>
        </div>

        <div v-else-if="filteredIps.length === 0" class="p-12 text-center text-text-muted space-y-2">
          <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-2" />
          <p class="text-sm font-bold text-text-primary">No Suspicious IP Behavior Detected</p>
          <p class="text-xs text-text-secondary">
            All client IP addresses in the past 60 minutes are operating within normal traffic rates and error margins.
          </p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase text-[10px] font-semibold tracking-wider">
              <tr>
                <th class="px-4 py-3">IP Address</th>
                <th class="px-4 py-3">Total Requests (60m)</th>
                <th class="px-4 py-3">Security Errors (401/403/429)</th>
                <th class="px-4 py-3">Error Proportion</th>
                <th class="px-4 py-3">Threat Classification</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle font-mono">
              <tr v-for="ip in filteredIps" :key="ip.ip_address" class="hover:bg-surface-hover transition-colors">
                <!-- IP Address -->
                <td class="px-4 py-3 font-bold text-accent">
                  <div class="flex items-center gap-1.5">
                    <span>{{ ip.ip_address }}</span>
                    <button
                      @click="copyToClipboard(ip.ip_address, ip.ip_address)"
                      class="text-text-muted hover:text-accent p-0.5"
                      title="Copy IP Address"
                    >
                      <component :is="copiedField === ip.ip_address ? CheckCheck : Copy" class="w-3 h-3" />
                    </button>
                  </div>
                </td>

                <!-- Total Requests -->
                <td class="px-4 py-3 font-bold text-text-primary">
                  {{ ip.total_requests }} reqs
                </td>

                <!-- Security Error Count -->
                <td class="px-4 py-3">
                  <span :class="['font-bold', ip.error_count > 0 ? 'text-rose-400' : 'text-text-muted']">
                    {{ ip.error_count }} errors
                  </span>
                </td>

                <!-- Error Proportion -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-16 h-1.5 rounded-full bg-surface-elevated overflow-hidden border border-border-subtle">
                      <div
                        class="h-full bg-rose-500 rounded-full"
                        :style="{ width: Math.min(100, Math.round((ip.error_count / Math.max(ip.total_requests, 1)) * 100)) + '%' }"
                      />
                    </div>
                    <span class="text-[11px] text-text-muted">
                      {{ Math.round((ip.error_count / Math.max(ip.total_requests, 1)) * 100) }}%
                    </span>
                  </div>
                </td>

                <!-- Threat Classification Badge -->
                <td class="px-4 py-3">
                  <span
                    v-if="ip.is_suspicious"
                    class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg bg-rose-500/10 text-rose-300 border border-rose-500/30"
                  >
                    SUSPICIOUS ABUSE
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg bg-amber-500/10 text-amber-300 border border-amber-500/30"
                  >
                    HIGH VOLUME CALLER
                  </span>
                </td>

                <!-- Action Button -->
                <td class="px-4 py-3 text-right font-sans">
                  <button
                    @click="investigateIp(ip.ip_address)"
                    class="btn btn-secondary text-xs px-2.5 py-1 rounded-lg border border-border-subtle hover:border-accent inline-flex items-center gap-1"
                    title="Investigate requests from this IP"
                  >
                    <span>Inspect Calls</span>
                    <ExternalLink class="w-3 h-3 text-text-muted" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
