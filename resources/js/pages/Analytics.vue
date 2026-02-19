<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRequests } from '../composables/useRequests';

const { fetchAnalytics, loading, error } = useRequests();

const analyticsData = ref(null);
const timeRange = ref(30);

const chartBase = {
  chart: { toolbar: { show: true, tools: { download: true, zoom: true, pan: false, reset: true } }, background: 'transparent', fontFamily: 'Inter, system-ui, sans-serif' },
  theme: { mode: 'dark' },
  grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4, padding: { left: 8, right: 8 } },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: { labels: { style: { colors: '#555', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { labels: { style: { colors: '#555', fontSize: '11px' } } },
  tooltip: { theme: 'dark' },
  dataLabels: { enabled: false },
};

const volumeOptions = computed(() => {
  if (!analyticsData.value) return {};
  return {
    ...chartBase,
    chart: { ...chartBase.chart, type: 'area', height: 340 },
    colors: ['#00e5ff'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 100] } },
    xaxis: { ...chartBase.xaxis, categories: Object.keys(analyticsData.value.requests_per_day), labels: { ...chartBase.xaxis.labels, formatter: (v) => { if (!v) return ''; const d = new Date(v); return `${d.getDate()}/${d.getMonth()+1}`; } } },
  };
});
const volumeSeries = computed(() => analyticsData.value ? [{ name: 'Requests', data: Object.values(analyticsData.value.requests_per_day) }] : []);

const errorOptions = computed(() => {
  if (!analyticsData.value) return {};
  return {
    ...chartBase,
    chart: { ...chartBase.chart, type: 'area', height: 340 },
    colors: ['#ff1744'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.2, opacityTo: 0.02, stops: [0, 100] } },
    xaxis: { ...chartBase.xaxis, categories: Object.keys(analyticsData.value.error_rate_trend), labels: { ...chartBase.xaxis.labels, formatter: (v) => { if (!v) return ''; const d = new Date(v); return `${d.getDate()}/${d.getMonth()+1}`; } } },
    yaxis: { ...chartBase.yaxis, labels: { ...chartBase.yaxis.labels, formatter: (v) => v?.toFixed(1) + '%' } },
    annotations: {
      yaxis: [{ y: 5, borderColor: '#ff1744', strokeDashArray: 4, label: { text: 'Threshold 5%', style: { color: '#ff1744', background: 'transparent' }, position: 'front' } }],
    },
  };
});
const errorSeries = computed(() => analyticsData.value ? [{ name: 'Error Rate', data: Object.values(analyticsData.value.error_rate_trend) }] : []);

const statusOptions = computed(() => {
  if (!analyticsData.value) return {};
  const statuses = Object.keys(analyticsData.value.status_code_distribution);
  const colors = statuses.map(c => { if (c >= 500) return '#ff1744'; if (c >= 400) return '#ffab00'; if (c >= 300) return '#2979ff'; return '#00e676'; });
  return {
    chart: { type: 'donut', background: 'transparent', fontFamily: 'Inter, system-ui, sans-serif' },
    theme: { mode: 'dark' },
    colors,
    labels: statuses.map(s => `HTTP ${s}`),
    legend: { position: 'bottom', labels: { colors: '#8a8a8a' } },
    stroke: { show: false },
    plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: { show: true, label: 'Total', color: '#8a8a8a', formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString() }, value: { color: '#f0f0f0', fontSize: '22px', fontWeight: 700 } } } } },
    dataLabels: { enabled: false },
    tooltip: { theme: 'dark' },
  };
});
const statusSeries = computed(() => analyticsData.value ? Object.values(analyticsData.value.status_code_distribution) : []);

const slowOptions = computed(() => {
  if (!analyticsData.value) return {};
  const routes = analyticsData.value.top_slowest_routes.slice(0, 10);
  const labels = routes.map(r => { try { const u = new URL(r.url); return u.pathname.length > 35 ? u.pathname.slice(0, 35) + '…' : u.pathname; } catch { return r.url?.slice(0, 35) || 'unknown'; } });
  return {
    chart: { type: 'bar', background: 'transparent', toolbar: { show: false }, fontFamily: 'Inter, system-ui, sans-serif' },
    theme: { mode: 'dark' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
    colors: ['#ffab00'],
    xaxis: { categories: labels, labels: { style: { colors: '#555', fontSize: '11px' }, formatter: (v) => v + 'ms' }, axisBorder: { show: false } },
    yaxis: { labels: { style: { colors: '#8a8a8a', fontSize: '11px' }, maxWidth: 200 } },
    grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4 },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: ['#f0f0f0'] }, formatter: (v) => v + 'ms', offsetX: 4 },
    tooltip: { theme: 'dark', y: { formatter: (v) => v + 'ms' } },
  };
});
const slowSeries = computed(() => {
  if (!analyticsData.value) return [];
  return [{ name: 'Avg Duration', data: analyticsData.value.top_slowest_routes.slice(0, 10).map(r => r.avg_duration) }];
});

const loadData = async () => {
  try {
    analyticsData.value = await fetchAnalytics(timeRange.value);
  } catch (e) { console.error(e); }
};

onMounted(loadData);
</script>

<template>
  <div class="space-y-6 animate-[fade-in_0.3s_ease]">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">Analytics</h1>
        <p class="text-sm text-text-muted mt-0.5">Deep dive into your API performance</p>
      </div>
      <select
        v-model="timeRange"
        @change="loadData"
        class="w-auto"
      >
        <option :value="7">Last 7 Days</option>
        <option :value="30">Last 30 Days</option>
        <option :value="90">Last 90 Days</option>
      </select>
    </div>

    <div v-if="loading && !analyticsData" class="space-y-4">
      <div class="skeleton h-[340px]" />
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="skeleton h-[340px]" />
        <div class="skeleton h-[340px]" />
      </div>
    </div>

    <div v-if="error" class="glass-surface p-4 border-l-4 border-danger">
      <p class="text-danger text-sm">{{ error }}</p>
    </div>

    <div v-if="analyticsData" class="space-y-4">
      <!-- Request Volume (full width) -->
      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Request Volume</h3>
        <apexchart type="area" height="340" :options="volumeOptions" :series="volumeSeries" />
      </div>

      <!-- Error Rate + Status Distribution -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="glass-surface p-4 md:p-5">
          <h3 class="text-sm font-semibold text-text-primary mb-4">Error Rate Trend</h3>
          <apexchart type="area" height="320" :options="errorOptions" :series="errorSeries" />
        </div>

        <div class="glass-surface p-4 md:p-5">
          <h3 class="text-sm font-semibold text-text-primary mb-4">Status Code Distribution</h3>
          <template v-if="statusSeries.length">
            <apexchart type="donut" height="320" :options="statusOptions" :series="statusSeries" />
          </template>
          <div v-else class="h-[320px] flex items-center justify-center text-text-muted text-sm">No data available</div>
        </div>
      </div>

      <!-- Slowest Routes -->
      <div class="glass-surface p-4 md:p-5">
        <h3 class="text-sm font-semibold text-text-primary mb-4">Top Slowest Routes</h3>
        <template v-if="analyticsData.top_slowest_routes?.length">
          <apexchart type="bar" height="380" :options="slowOptions" :series="slowSeries" />
        </template>
        <div v-else class="h-[200px] flex items-center justify-center text-text-muted text-sm">No data available</div>
      </div>
    </div>
  </div>
</template>
