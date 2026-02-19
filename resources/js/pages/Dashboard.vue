<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRequests } from '../composables/useRequests';
import { formatDistanceToNow } from 'date-fns';
import {
  Activity,
  AlertTriangle,
  Clock,
  Users,
  TrendingUp,
  TrendingDown,
  ArrowRight
} from 'lucide-vue-next';

const { stats, loading, fetchStats, fetchAnalytics, fetchRequests, requests } = useRequests();

const analyticsData = ref(null);
const analyticsLoading = ref(true);
const timeRange = ref(30);

const chartBaseOptions = {
  chart: {
    toolbar: { show: false },
    sparkline: { enabled: false },
    background: 'transparent',
    fontFamily: 'Inter, system-ui, sans-serif',
  },
  theme: { mode: 'dark' },
  grid: {
    borderColor: 'rgba(255,255,255,0.06)',
    strokeDashArray: 4,
    padding: { left: 8, right: 8 },
  },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: {
    labels: { style: { colors: '#555', fontSize: '11px' } },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: { style: { colors: '#555', fontSize: '11px' } },
  },
  tooltip: { theme: 'dark' },
};

const requestsChartOptions = computed(() => {
  if (!analyticsData.value) return {};
  const dates = Object.keys(analyticsData.value.requests_per_day);
  return {
    ...chartBaseOptions,
    chart: { ...chartBaseOptions.chart, type: 'area', height: 280 },
    colors: ['#00e5ff'],
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0.02, stops: [0, 100] },
    },
    xaxis: {
      ...chartBaseOptions.xaxis,
      categories: dates,
      labels: {
        ...chartBaseOptions.xaxis.labels,
        formatter: (val) => {
          if (!val) return '';
          const d = new Date(val);
          return `${d.getDate()}/${d.getMonth() + 1}`;
        },
      },
    },
    dataLabels: { enabled: false },
  };
});

const requestsChartSeries = computed(() => {
  if (!analyticsData.value) return [];
  return [{ name: 'Requests', data: Object.values(analyticsData.value.requests_per_day) }];
});

const errorChartOptions = computed(() => {
  if (!analyticsData.value) return {};
  const dates = Object.keys(analyticsData.value.error_rate_trend);
  return {
    ...chartBaseOptions,
    chart: { ...chartBaseOptions.chart, type: 'area', height: 280 },
    colors: ['#ff1744'],
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.02, stops: [0, 100] },
    },
    xaxis: {
      ...chartBaseOptions.xaxis,
      categories: dates,
      labels: {
        ...chartBaseOptions.xaxis.labels,
        formatter: (val) => {
          if (!val) return '';
          const d = new Date(val);
          return `${d.getDate()}/${d.getMonth() + 1}`;
        },
      },
    },
    yaxis: {
      ...chartBaseOptions.yaxis,
      labels: {
        ...chartBaseOptions.yaxis.labels,
        formatter: (val) => val?.toFixed(1) + '%',
      },
    },
    dataLabels: { enabled: false },
  };
});

const errorChartSeries = computed(() => {
  if (!analyticsData.value) return [];
  return [{ name: 'Error Rate', data: Object.values(analyticsData.value.error_rate_trend) }];
});

const statusChartOptions = computed(() => {
  if (!analyticsData.value) return {};
  const statuses = Object.keys(analyticsData.value.status_code_distribution);
  const colors = statuses.map(code => {
    if (code >= 500) return '#ff1744';
    if (code >= 400) return '#ffab00';
    if (code >= 300) return '#2979ff';
    return '#00e676';
  });
  return {
    chart: { type: 'donut', background: 'transparent', fontFamily: 'Inter, system-ui, sans-serif' },
    theme: { mode: 'dark' },
    colors,
    labels: statuses.map(s => `${s}`),
    legend: { position: 'bottom', labels: { colors: '#8a8a8a' } },
    stroke: { show: false },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Total',
              color: '#8a8a8a',
              formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
            },
            value: { color: '#f0f0f0', fontSize: '20px', fontWeight: 700 },
          },
        },
      },
    },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
  };
});

const statusChartSeries = computed(() => {
  if (!analyticsData.value) return [];
  return Object.values(analyticsData.value.status_code_distribution);
});

const slowestChartOptions = computed(() => {
  if (!analyticsData.value) return {};
  const routes = analyticsData.value.top_slowest_routes.slice(0, 8);
  const labels = routes.map(r => {
    try {
      const u = new URL(r.url);
      return u.pathname.length > 30 ? u.pathname.slice(0, 30) + '…' : u.pathname;
    } catch { return r.url?.slice(0, 30) || 'unknown'; }
  });
  return {
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif' },
    theme: { mode: 'dark' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
    colors: ['#ffab00'],
    xaxis: {
      categories: labels,
      labels: { style: { colors: '#555', fontSize: '11px' }, formatter: (val) => val + 'ms' },
      axisBorder: { show: false },
    },
    yaxis: { labels: { style: { colors: '#8a8a8a', fontSize: '11px' } } },
    grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4 },
    dataLabels: { enabled: true, style: { fontSize: '11px' }, formatter: (val) => val + 'ms' },
    tooltip: { theme: 'dark', y: { formatter: (val) => val + 'ms' } },
  };
});

const slowestChartSeries = computed(() => {
  if (!analyticsData.value) return [];
  const routes = analyticsData.value.top_slowest_routes.slice(0, 8);
  return [{ name: 'Avg Duration', data: routes.map(r => r.avg_duration) }];
});

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

const formatPath = (url) => {
  try { return new URL(url).pathname; } catch { return url; }
};

const loadData = async () => {
  analyticsLoading.value = true;
  await Promise.all([
    fetchStats(),
    fetchAnalytics(timeRange.value).then(d => { analyticsData.value = d; }),
    fetchRequests({ limit: 8 }),
  ]);
  analyticsLoading.value = false;
};

const changeTimeRange = (days) => {
  timeRange.value = days;
  analyticsLoading.value = true;
  fetchAnalytics(days).then(d => {
    analyticsData.value = d;
    analyticsLoading.value = false;
  });
};

onMounted(loadData);
</script>

<template>
  <div class="space-y-6 animate-[fade-in_0.3s_ease]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Dashboard</h1>
        <p class="text-sm text-text-muted mt-0.5">API monitoring overview</p>
      </div>
      <div class="flex gap-1 p-1 glass-surface rounded-full">
        <button
          v-for="opt in [{ label: '7D', value: 7 }, { label: '30D', value: 30 }, { label: '90D', value: 90 }]"
          :key="opt.value"
          @click="changeTimeRange(opt.value)"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-full transition-all duration-200',
            timeRange === opt.value
              ? 'bg-accent text-text-inverted'
              : 'text-text-muted hover:text-text-primary'
          ]"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
      <!-- Total Requests -->
      <div class="glass-surface card-interactive p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-text-muted uppercase tracking-wider">Requests</span>
          <div class="w-8 h-8 rounded-lg bg-accent-soft flex items-center justify-center">
            <Activity class="w-4 h-4 text-accent" />
          </div>
        </div>
        <template v-if="loading">
          <div class="skeleton h-8 w-24 mb-1" />
        </template>
        <template v-else>
          <p class="text-2xl md:text-3xl font-bold text-text-primary">
            {{ stats.total_requests?.toLocaleString() }}
          </p>
        </template>
      </div>

      <!-- Error Rate -->
      <div class="glass-surface card-interactive p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-text-muted uppercase tracking-wider">Error Rate</span>
          <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', stats.error_rate > 5 ? 'bg-danger-soft' : 'bg-success-soft']">
            <AlertTriangle :class="['w-4 h-4', stats.error_rate > 5 ? 'text-danger' : 'text-success']" />
          </div>
        </div>
        <template v-if="loading">
          <div class="skeleton h-8 w-20 mb-1" />
        </template>
        <template v-else>
          <p :class="['text-2xl md:text-3xl font-bold', stats.error_rate > 5 ? 'text-danger' : 'text-success']">
            {{ stats.error_rate }}%
          </p>
        </template>
      </div>

      <!-- Avg Latency -->
      <div class="glass-surface card-interactive p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-text-muted uppercase tracking-wider">Avg Latency</span>
          <div class="w-8 h-8 rounded-lg bg-warning-soft flex items-center justify-center">
            <Clock class="w-4 h-4 text-warning" />
          </div>
        </div>
        <template v-if="loading">
          <div class="skeleton h-8 w-20 mb-1" />
        </template>
        <template v-else>
          <p class="text-2xl md:text-3xl font-bold text-warning">
            {{ stats.avg_latency }}<span class="text-sm font-normal text-text-muted ml-0.5">ms</span>
          </p>
        </template>
      </div>

      <!-- Active Users -->
      <div class="glass-surface card-interactive p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-medium text-text-muted uppercase tracking-wider">Users</span>
          <div class="w-8 h-8 rounded-lg bg-info-soft flex items-center justify-center">
            <Users class="w-4 h-4 text-info" />
          </div>
        </div>
        <template v-if="loading">
          <div class="skeleton h-8 w-16 mb-1" />
        </template>
        <template v-else>
          <p class="text-2xl md:text-3xl font-bold text-info">
            {{ stats.active_users }}
          </p>
        </template>
      </div>
    </div>

    <!-- Charts Row 1: Volume + Error Rate -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Request Volume</h3>
        <template v-if="analyticsLoading">
          <div class="skeleton h-[280px]" />
        </template>
        <template v-else-if="analyticsData">
          <apexchart
            type="area"
            height="280"
            :options="requestsChartOptions"
            :series="requestsChartSeries"
          />
        </template>
      </div>

      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Error Rate Trend</h3>
        <template v-if="analyticsLoading">
          <div class="skeleton h-[280px]" />
        </template>
        <template v-else-if="analyticsData">
          <apexchart
            type="area"
            height="280"
            :options="errorChartOptions"
            :series="errorChartSeries"
          />
        </template>
      </div>
    </div>

    <!-- Charts Row 2: Status Distribution + Slowest Routes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Status Distribution</h3>
        <template v-if="analyticsLoading">
          <div class="skeleton h-[280px]" />
        </template>
        <template v-else-if="analyticsData && statusChartSeries.length">
          <apexchart
            type="donut"
            height="280"
            :options="statusChartOptions"
            :series="statusChartSeries"
          />
        </template>
        <div v-else class="h-[280px] flex items-center justify-center text-text-muted text-sm">
          No data available
        </div>
      </div>

      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Slowest Routes</h3>
        <template v-if="analyticsLoading">
          <div class="skeleton h-[280px]" />
        </template>
        <template v-else-if="analyticsData && analyticsData.top_slowest_routes?.length">
          <apexchart
            type="bar"
            height="280"
            :options="slowestChartOptions"
            :series="slowestChartSeries"
          />
        </template>
        <div v-else class="h-[280px] flex items-center justify-center text-text-muted text-sm">
          No data available
        </div>
      </div>
    </div>

    <!-- Recent Requests -->
    <div class="glass-surface p-4 md:p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-text-primary">Recent Requests</h3>
        <router-link
          to="/requests"
          class="flex items-center gap-1 text-xs font-medium text-accent hover:text-accent-hover transition-colors"
        >
          View all <ArrowRight class="w-3.5 h-3.5" />
        </router-link>
      </div>

      <template v-if="loading">
        <div class="space-y-3">
          <div v-for="i in 5" :key="i" class="skeleton h-12" />
        </div>
      </template>

      <template v-else-if="requests.length">
        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-border-subtle">
                <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider pb-3 pr-4">Method</th>
                <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider pb-3 pr-4">Path</th>
                <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider pb-3 pr-4">Status</th>
                <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider pb-3 pr-4">Duration</th>
                <th class="text-left text-[11px] font-medium text-text-muted uppercase tracking-wider pb-3">Time</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="req in requests.slice(0, 8)"
                :key="req.id"
                class="border-b border-border-subtle last:border-0 hover:bg-surface-hover transition-colors cursor-pointer"
                @click="$router.push('/requests/' + req.id)"
              >
                <td class="py-3 pr-4">
                  <span :class="[methodColor(req.method), 'badge text-[11px]']">{{ req.method }}</span>
                </td>
                <td class="py-3 pr-4 font-mono text-sm text-text-secondary truncate max-w-[300px]" :title="req.url">
                  {{ formatPath(req.url) }}
                </td>
                <td class="py-3 pr-4">
                  <span :class="[statusColor(req.status_code), 'badge text-[11px]']">{{ req.status_code }}</span>
                </td>
                <td class="py-3 pr-4 text-sm text-text-secondary">
                  {{ req.duration_ms }}ms
                </td>
                <td class="py-3 text-sm text-text-muted">
                  {{ formatDistanceToNow(new Date(req.created_at), { addSuffix: true }) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden space-y-2">
          <router-link
            v-for="req in requests.slice(0, 6)"
            :key="req.id"
            :to="'/requests/' + req.id"
            class="block p-3 rounded-xl bg-surface-elevated hover:bg-surface-hover transition-colors"
          >
            <div class="flex items-center justify-between mb-1.5">
              <div class="flex items-center gap-2">
                <span :class="[methodColor(req.method), 'badge text-[10px]']">{{ req.method }}</span>
                <span :class="[statusColor(req.status_code), 'badge text-[10px]']">{{ req.status_code }}</span>
              </div>
              <span class="text-xs text-text-muted">{{ req.duration_ms }}ms</span>
            </div>
            <p class="font-mono text-xs text-text-secondary truncate">{{ formatPath(req.url) }}</p>
          </router-link>
        </div>
      </template>

      <div v-else class="text-center py-8 text-text-muted text-sm">
        No requests recorded yet.
      </div>
    </div>
  </div>
</template>
