<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { formatDistanceToNow } from 'date-fns';
import OutgoingFilterSidebar from '../components/OutgoingFilterSidebar.vue';
import {
  ExternalLink,
  Globe,
  Clock,
  AlertTriangle,
  ArrowLeftRight,
  Search,
  X,
  ChevronLeft,
  ChevronRight,
  RefreshCw,
  ArrowLeft,
  Copy,
  CheckCheck,
  Terminal,
  FileText,
  Download,
  Layers,
  Activity,
  CheckCircle2,
  XCircle,
  ChevronsUpDown,
  Maximize2,
  Minimize2,
  SlidersHorizontal,
  ChevronDown,
  ChevronUp,
  Cpu
} from 'lucide-vue-next';

const router = useRouter();

// List State
const outgoingRequests = ref([]);
const totalCount = ref(0);
const stats = ref({
  total_outgoing_requests: 0,
  error_rate: 0,
  avg_latency: 0,
  top_domains: []
});
const loading = ref(true);

// Filters & Pagination
const isFilterOpen = ref(false);
const searchInput = ref('');
const activeMethod = ref('');
const activeStatusGroup = ref('');
const activeDomain = ref('');
const currentPage = ref(1);
const perPage = ref(25);
let searchTimeout = null;

const advancedFilters = ref({
  search: '',
  domain: '',
  url: '',
  parent_request_id: '',
  method: [],
  status_code: [],
  status_group: '',
  date_from: '',
  date_to: '',
  duration_min: '',
  duration_max: '',
});

const availableDomains = computed(() => {
  return (stats.value.top_domains || []).map(d => d.domain);
});

// Selected Request for Full Bento Detail View
const selectedRequest = ref(null);
const detailLoading = ref(false);

// Detail Bento Interactive State
const collapsed = ref({
  kpis: false,
  failure: false,
  request: false,
  response: false,
  headers: false,
  context: false
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

const copiedField = ref(null);
const searchRequestBody = ref('');
const requestBodyViewMode = ref('formatted');
const searchResponseBody = ref('');
const responseBodyViewMode = ref('formatted');
const searchHeaders = ref('');

// Fetch list and stats
const fetchOutgoingData = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    const query = searchInput.value.trim() || (advancedFilters.value.search ? advancedFilters.value.search.trim() : '');
    if (query) params.append('search', query);

    const domain = advancedFilters.value.domain || activeDomain.value;
    if (domain) params.append('domain', domain);

    if (advancedFilters.value.url) params.append('url', advancedFilters.value.url.trim());
    if (advancedFilters.value.parent_request_id) params.append('parent_request_id', advancedFilters.value.parent_request_id.trim());

    if (Array.isArray(advancedFilters.value.method) && advancedFilters.value.method.length > 0) {
      advancedFilters.value.method.forEach(m => params.append('method[]', m));
    } else if (activeMethod.value) {
      params.append('method', activeMethod.value);
    }

    if (Array.isArray(advancedFilters.value.status_code) && advancedFilters.value.status_code.length > 0) {
      advancedFilters.value.status_code.forEach(s => params.append('status_code[]', s));
    }

    const sg = advancedFilters.value.status_group || activeStatusGroup.value;
    if (sg) params.append('status_group', sg);

    if (advancedFilters.value.duration_min !== '' && advancedFilters.value.duration_min !== null && advancedFilters.value.duration_min !== undefined) {
      params.append('duration_min', String(advancedFilters.value.duration_min));
    }
    if (advancedFilters.value.duration_max !== '' && advancedFilters.value.duration_max !== null && advancedFilters.value.duration_max !== undefined) {
      params.append('duration_max', String(advancedFilters.value.duration_max));
    }

    if (advancedFilters.value.date_from) params.append('date_from', advancedFilters.value.date_from);
    if (advancedFilters.value.date_to) params.append('date_to', advancedFilters.value.date_to);

    params.append('page', String(currentPage.value));
    params.append('limit', String(perPage.value));

    const [reqRes, statsRes] = await Promise.all([
      fetch(`/api-watcher/api/outgoing-requests?${params.toString()}`),
      fetch('/api-watcher/api/outgoing-requests/stats').then(r => r.json())
    ]);

    const totalHeader = reqRes.headers.get('X-Total-Count');
    if (totalHeader !== null) {
      totalCount.value = parseInt(totalHeader, 10);
    } else {
      totalCount.value = statsRes.total_outgoing_requests || 0;
    }

    outgoingRequests.value = await reqRes.json();
    stats.value = statsRes;
  } catch (err) {
    console.error('Failed to fetch outgoing requests data', err);
  } finally {
    loading.value = false;
  }
};

// Debounced Search
watch(searchInput, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    fetchOutgoingData();
  }, 350);
});

const filterByDomain = (domain) => {
  if (activeDomain.value === domain || advancedFilters.value.domain === domain) {
    activeDomain.value = '';
    advancedFilters.value.domain = '';
  } else {
    activeDomain.value = domain;
    advancedFilters.value.domain = domain;
  }
  currentPage.value = 1;
  fetchOutgoingData();
};

const setMethodFilter = (method) => {
  if (activeMethod.value === method) {
    activeMethod.value = '';
    advancedFilters.value.method = [];
  } else {
    activeMethod.value = method;
    advancedFilters.value.method = method ? [method] : [];
  }
  currentPage.value = 1;
  fetchOutgoingData();
};

const setStatusFilter = (status) => {
  if (activeStatusGroup.value === status) {
    activeStatusGroup.value = '';
    advancedFilters.value.status_group = '';
  } else {
    activeStatusGroup.value = status;
    advancedFilters.value.status_group = status;
  }
  currentPage.value = 1;
  fetchOutgoingData();
};

const applyAdvancedFilters = (newFilters) => {
  advancedFilters.value = { ...newFilters };
  if (newFilters.search) {
    searchInput.value = newFilters.search;
  }
  if (Array.isArray(newFilters.method) && newFilters.method.length === 1) {
    activeMethod.value = newFilters.method[0];
  } else if (!newFilters.method || newFilters.method.length === 0) {
    activeMethod.value = '';
  } else {
    activeMethod.value = '';
  }
  activeStatusGroup.value = newFilters.status_group || '';
  activeDomain.value = newFilters.domain || '';
  isFilterOpen.value = false;
  currentPage.value = 1;
  fetchOutgoingData();
};

const resetAdvancedFilters = () => {
  advancedFilters.value = {
    search: '',
    domain: '',
    url: '',
    parent_request_id: '',
    method: [],
    status_code: [],
    status_group: '',
    date_from: '',
    date_to: '',
    duration_min: '',
    duration_max: '',
  };
  searchInput.value = '';
  activeMethod.value = '';
  activeStatusGroup.value = '';
  activeDomain.value = '';
  isFilterOpen.value = false;
  currentPage.value = 1;
  fetchOutgoingData();
};

const clearAllFilters = () => {
  resetAdvancedFilters();
};

const removeMethodFilter = (m) => {
  if (Array.isArray(advancedFilters.value.method)) {
    advancedFilters.value.method = advancedFilters.value.method.filter(x => x !== m);
  }
  if (activeMethod.value === m) activeMethod.value = '';
  currentPage.value = 1;
  fetchOutgoingData();
};

const removeStatusCodeFilter = (s) => {
  if (Array.isArray(advancedFilters.value.status_code)) {
    advancedFilters.value.status_code = advancedFilters.value.status_code.filter(x => x !== s);
  }
  currentPage.value = 1;
  fetchOutgoingData();
};

const removeFilterKey = (key) => {
  if (key === 'search') {
    searchInput.value = '';
    advancedFilters.value.search = '';
  } else if (key === 'domain') {
    activeDomain.value = '';
    advancedFilters.value.domain = '';
  } else if (key === 'status_group') {
    activeStatusGroup.value = '';
    advancedFilters.value.status_group = '';
  } else if (key === 'url') {
    advancedFilters.value.url = '';
  } else if (key === 'parent_request_id') {
    advancedFilters.value.parent_request_id = '';
  } else if (key === 'duration') {
    advancedFilters.value.duration_min = '';
    advancedFilters.value.duration_max = '';
  } else if (key === 'date') {
    advancedFilters.value.date_from = '';
    advancedFilters.value.date_to = '';
  }
  currentPage.value = 1;
  fetchOutgoingData();
};

const hasActiveFilters = computed(() => {
  const f = advancedFilters.value;
  return Boolean(
    searchInput.value ||
    f.search ||
    activeMethod.value ||
    (f.method && f.method.length > 0) ||
    activeStatusGroup.value ||
    f.status_group ||
    activeDomain.value ||
    f.domain ||
    f.url ||
    f.parent_request_id ||
    (f.status_code && f.status_code.length > 0) ||
    f.date_from ||
    f.date_to ||
    (f.duration_min !== '' && f.duration_min !== null && f.duration_min !== undefined) ||
    (f.duration_max !== '' && f.duration_max !== null && f.duration_max !== undefined)
  );
});

const activeFilterCount = computed(() => {
  const f = advancedFilters.value;
  let count = 0;
  if (searchInput.value || f.search) count++;
  if (f.method && f.method.length > 0) count += f.method.length;
  else if (activeMethod.value) count++;
  if (f.status_code && f.status_code.length > 0) count += f.status_code.length;
  if (f.status_group || activeStatusGroup.value) count++;
  if (f.domain || activeDomain.value) count++;
  if (f.url) count++;
  if (f.parent_request_id) count++;
  if (f.date_from || f.date_to) count++;
  if (f.duration_min !== '' && f.duration_min !== null && f.duration_min !== undefined) count++;
  if (f.duration_max !== '' && f.duration_max !== null && f.duration_max !== undefined) count++;
  return count;
});

const totalPages = computed(() => {
  return Math.ceil(totalCount.value / perPage.value) || 1;
});

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return;
  currentPage.value = page;
  fetchOutgoingData();
};

const selectRequest = async (req) => {
  detailLoading.value = true;
  try {
    const res = await fetch(`/api-watcher/api/outgoing-requests/${req.id}`);
    if (res.ok) {
      selectedRequest.value = await res.json();
    } else {
      selectedRequest.value = req;
    }
  } catch {
    selectedRequest.value = req;
  } finally {
    detailLoading.value = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const closeDetail = () => {
  selectedRequest.value = null;
};

// Helpers & Formatting
const statusColor = (code) => {
  if (!code || code === 0) return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
  if (code >= 200 && code < 300) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
  if (code >= 300 && code < 400) return 'bg-sky-500/10 text-sky-400 border-sky-500/30';
  if (code >= 400 && code < 500) return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
  return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
};

const statusText = (code) => {
  if (!code || code === 0) return '0 FAILED';
  const map = {
    200: 'OK',
    201: 'Created',
    202: 'Accepted',
    204: 'No Content',
    301: 'Moved Permanently',
    302: 'Found',
    304: 'Not Modified',
    400: 'Bad Request',
    401: 'Unauthorized',
    403: 'Forbidden',
    404: 'Not Found',
    422: 'Unprocessable Entity',
    429: 'Too Many Requests',
    500: 'Internal Server Error',
    502: 'Bad Gateway',
    503: 'Service Unavailable',
    504: 'Gateway Timeout'
  };
  return map[code] ? `${code} ${map[code]}` : `${code}`;
};

const methodColor = (method) => {
  const map = {
    GET: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    POST: 'bg-sky-500/10 text-sky-400 border-sky-500/30',
    PUT: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    PATCH: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    DELETE: 'bg-rose-500/10 text-rose-400 border-rose-500/30'
  };
  return map[method] || 'bg-surface-elevated text-text-secondary border-border-default';
};

const formatBytes = (bytes, decimals = 2) => {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const formatJson = (data) => {
  if (!data) return '';
  if (typeof data === 'object') return JSON.stringify(data, null, 2);
  try {
    return JSON.stringify(JSON.parse(data), null, 2);
  } catch {
    return String(data);
  }
};

const getRawString = (data) => {
  if (!data) return '';
  if (typeof data === 'object') return JSON.stringify(data);
  return String(data);
};

const getPayloadSize = (payload) => {
  if (!payload) return 0;
  if (typeof payload === 'string') return new Blob([payload]).size;
  return new Blob([JSON.stringify(payload)]).size;
};

const copyToClipboard = (text, fieldName) => {
  if (!text) return;
  const str = typeof text === 'object' ? JSON.stringify(text, null, 2) : String(text);
  navigator.clipboard.writeText(str);
  copiedField.value = fieldName;
  setTimeout(() => {
    if (copiedField.value === fieldName) copiedField.value = null;
  }, 2000);
};

const copyCurl = () => {
  if (!selectedRequest.value) return;
  const req = selectedRequest.value;
  let curl = `curl -X ${req.method} "${req.url}"`;
  if (req.request_headers) {
    Object.entries(req.request_headers).forEach(([k, v]) => {
      curl += ` \\\n  -H "${k}: ${v}"`;
    });
  }
  if (req.request_body && req.method !== 'GET') {
    const body = typeof req.request_body === 'string' ? req.request_body : JSON.stringify(req.request_body);
    curl += ` \\\n  -d '${body.replace(/'/g, "'\\''")}'`;
  }
  navigator.clipboard.writeText(curl);
  copiedField.value = 'curl';
  setTimeout(() => {
    if (copiedField.value === 'curl') copiedField.value = null;
  }, 2000);
};

const copyMarkdown = () => {
  if (!selectedRequest.value) return;
  const req = selectedRequest.value;
  let md = '';

  md += `# Outgoing API Request: ${req.method} ${req.url}\n\n`;

  md += `## Overview\n`;
  md += `- **Status:** ${statusText(req.status_code)}\n`;
  md += `- **Target Domain:** \`${req.domain}\`\n`;
  md += `- **Duration:** ${req.duration_ms} ms\n`;
  md += `- **Timestamp:** ${new Date(req.created_at).toISOString().replace('T', ' ').substring(0, 19)} UTC\n`;
  if (req.parent_request_id) {
    md += `- **Parent Ingress Request ID:** \`${req.parent_request_id}\`\n`;
  }
  md += `- **Egress Request UUID:** \`${req.id}\`\n\n`;

  if (req.exception_info || req.status_code === 0) {
    md += `## Network / Connection Failure\n`;
    md += `\`\`\`\n${req.exception_info || 'Unknown network error or connection timeout'}\n\`\`\`\n\n`;
  }

  if (req.request_headers && Object.keys(req.request_headers).length > 0) {
    md += `## Request Headers (${Object.keys(req.request_headers).length})\n\n\`\`\`http\n`;
    Object.entries(req.request_headers).forEach(([k, v]) => {
      const valStr = Array.isArray(v) ? v.join(', ') : v;
      md += `${k}: ${valStr}\n`;
    });
    md += `\`\`\`\n\n`;
  }

  if (req.request_body) {
    md += `## Request Payload\n\n`;
    md += `\`\`\`json\n${formatJson(req.request_body)}\n\`\`\`\n\n`;
  }

  if (req.response_headers && Object.keys(req.response_headers).length > 0) {
    md += `## Response Headers (${Object.keys(req.response_headers).length})\n\n\`\`\`http\n`;
    Object.entries(req.response_headers).forEach(([k, v]) => {
      const valStr = Array.isArray(v) ? v.join(', ') : v;
      md += `${k}: ${valStr}\n`;
    });
    md += `\`\`\`\n\n`;
  }

  if (req.response_body) {
    md += `## Response Body\n\n`;
    md += `\`\`\`json\n${formatJson(req.response_body)}\n\`\`\`\n\n`;
  }

  navigator.clipboard.writeText(md.trim());
  copiedField.value = 'markdown';
  setTimeout(() => {
    if (copiedField.value === 'markdown') copiedField.value = null;
  }, 2000);
};

const downloadResponseJson = () => {
  if (!selectedRequest.value?.response_body) return;
  const content = formatJson(selectedRequest.value.response_body);
  const blob = new Blob([content], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `outgoing-response-${selectedRequest.value.id}.json`;
  a.click();
  URL.revokeObjectURL(url);
};

const scrollToSection = (id) => {
  const sectionKey = id.replace('section-', '');
  if (collapsed.value[sectionKey] !== undefined) {
    collapsed.value[sectionKey] = false;
  }
  const el = document.getElementById(id);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// Filtered Headers in Detail View
const filteredRequestHeaders = computed(() => {
  if (!selectedRequest.value?.request_headers) return {};
  if (!searchHeaders.value.trim()) return selectedRequest.value.request_headers;
  const q = searchHeaders.value.toLowerCase();
  const res = {};
  for (const [k, v] of Object.entries(selectedRequest.value.request_headers)) {
    if (k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)) res[k] = v;
  }
  return res;
});

const filteredResponseHeaders = computed(() => {
  if (!selectedRequest.value?.response_headers) return {};
  if (!searchHeaders.value.trim()) return selectedRequest.value.response_headers;
  const q = searchHeaders.value.toLowerCase();
  const res = {};
  for (const [k, v] of Object.entries(selectedRequest.value.response_headers)) {
    if (k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)) res[k] = v;
  }
  return res;
});

onMounted(() => {
  fetchOutgoingData();
});
</script>

<template>
  <div class="space-y-6 pb-20 animate-[fade-in_0.3s_ease]">
    <!-- ========================================== -->
    <!-- VIEW A: FULL BENTO DETAIL VIEW             -->
    <!-- ========================================== -->
    <div v-if="selectedRequest" class="space-y-6 animate-[fade-in_0.2s_ease]">
      <!-- Detail Hero Toolbar Card -->
      <div class="glass-surface-elevated p-5 rounded-2xl border border-border-default shadow-xl relative overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border-subtle pb-4">
          <!-- Back & Badges -->
          <div class="flex items-center gap-3">
            <button
              @click="closeDetail"
              class="p-2 rounded-xl bg-surface-elevated hover:bg-surface-hover text-text-secondary hover:text-text-primary transition-all border border-border-subtle group"
              title="Back to Outgoing Requests List"
            >
              <ArrowLeft class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
            </button>

            <div class="flex items-center gap-2">
              <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg uppercase tracking-wider border font-mono shadow-xs', methodColor(selectedRequest.method)]">
                {{ selectedRequest.method }}
              </span>
              <span :class="['px-3 py-1 text-xs font-bold rounded-lg border font-mono shadow-xs', statusColor(selectedRequest.status_code)]">
                {{ statusText(selectedRequest.status_code) }}
              </span>
              <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-surface-elevated border border-border-subtle text-accent font-mono">
                {{ selectedRequest.domain }}
              </span>
            </div>
          </div>

          <!-- Hero Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Copy cURL -->
            <button
              @click="copyCurl"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle hover:border-accent transition-all"
              title="Copy cURL command"
            >
              <component :is="copiedField === 'curl' ? CheckCheck : Terminal" class="w-3.5 h-3.5 text-accent" />
              <span>{{ copiedField === 'curl' ? 'cURL Copied!' : 'Copy cURL' }}</span>
            </button>

            <!-- Copy Markdown -->
            <button
              @click="copyMarkdown"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle hover:border-sky-400 transition-all"
              title="Copy outgoing request details as Markdown"
            >
              <component :is="copiedField === 'markdown' ? CheckCheck : FileText" class="w-3.5 h-3.5 text-sky-400" />
              <span>{{ copiedField === 'markdown' ? 'Markdown Copied!' : 'Copy Markdown' }}</span>
            </button>

            <!-- Expand / Collapse All -->
            <button
              @click="toggleAllSections"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle"
              :title="allExpanded ? 'Collapse all Bento cards' : 'Expand all Bento cards'"
            >
              <component :is="allExpanded ? Minimize2 : Maximize2" class="w-3.5 h-3.5 text-text-muted" />
              <span class="hidden sm:inline">{{ allExpanded ? 'Collapse All' : 'Expand All' }}</span>
            </button>
          </div>
        </div>

        <!-- URL & Ingress Correlation Banner -->
        <div class="mt-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="space-y-1.5 min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-sm font-mono font-bold text-text-primary break-all select-all tracking-tight">
                {{ selectedRequest.url }}
              </span>
              <button
                @click="copyToClipboard(selectedRequest.url, 'url')"
                class="p-1 text-text-muted hover:text-accent transition-colors flex-shrink-0"
                title="Copy Target URL"
              >
                <component :is="copiedField === 'url' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
              </button>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary pt-0.5">
              <!-- Parent Ingress Correlation Link -->
              <div v-if="selectedRequest.parent_request_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-accent-soft text-accent border border-accent/30 font-mono text-[11px]">
                <ArrowLeftRight class="w-3.5 h-3.5" />
                <span>Parent Request:</span>
                <router-link
                  :to="'/requests/' + selectedRequest.parent_request_id"
                  class="font-bold underline hover:text-text-primary"
                >
                  {{ selectedRequest.parent_request_id.substring(0, 13) }}...
                </router-link>
              </div>

              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-elevated text-text-muted border border-border-subtle font-mono text-[11px]">
                Egress UUID: {{ selectedRequest.id }}
              </span>
            </div>
          </div>

          <!-- Time Info -->
          <div class="flex lg:flex-col items-end justify-between lg:justify-center gap-1 text-right text-xs flex-shrink-0">
            <span class="font-medium text-text-primary flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5 text-accent" />
              {{ formatDistanceToNow(new Date(selectedRequest.created_at), { addSuffix: true }) }}
            </span>
            <span class="text-text-muted font-mono text-[11px]">
              {{ new Date(selectedRequest.created_at).toISOString().replace('T', ' ').substring(0, 19) }} UTC
            </span>
          </div>
        </div>
      </div>

      <!-- Bento Grid: Performance & Health KPIs -->
      <div id="section-kpis" class="space-y-3">
        <div class="flex items-center justify-between px-1">
          <h2 class="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
            <Activity class="w-3.5 h-3.5 text-accent" /> Outgoing KPI Metrics
          </h2>
          <button @click="toggleSection('kpis')" class="text-text-muted hover:text-text-primary text-xs flex items-center gap-1">
            <span>{{ collapsed.kpis ? 'Show' : 'Hide' }}</span>
            <component :is="collapsed.kpis ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
          </button>
        </div>

        <div v-show="!collapsed.kpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Duration KPI -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Egress Response Time</span>
              <Clock class="w-4 h-4 text-accent" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-text-primary">{{ selectedRequest.duration_ms }}</span>
              <span class="text-xs font-mono text-text-muted">ms</span>
            </div>
            <div class="mt-2 flex items-center gap-1.5">
              <span :class="[
                'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                selectedRequest.duration_ms < 300
                  ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                  : selectedRequest.duration_ms < 800
                  ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                  : 'bg-rose-500/10 text-rose-400 border-rose-500/30'
              ]">
                {{ selectedRequest.duration_ms < 300 ? 'Fast (<300ms)' : selectedRequest.duration_ms < 800 ? 'Moderate (<800ms)' : 'Slow (>=800ms)' }}
              </span>
            </div>
          </div>

          <!-- HTTP Status KPI -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-sky-400/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Status Response</span>
              <Globe class="w-4 h-4 text-sky-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
                {{ selectedRequest.status_code === 0 ? 'FAIL' : selectedRequest.status_code }}
              </span>
              <span class="text-xs font-mono text-text-muted">HTTP</span>
            </div>
            <div class="mt-2 text-[11px] text-text-secondary font-mono truncate">
              {{ statusText(selectedRequest.status_code) }}
            </div>
          </div>

          <!-- Request Payload Size KPI -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Sent Payload Size</span>
              <Cpu class="w-4 h-4 text-purple-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
                {{ formatBytes(getPayloadSize(selectedRequest.request_body)) }}
              </span>
            </div>
            <div class="mt-2 text-[10px] text-text-muted">
              Outbound payload size
            </div>
          </div>

          <!-- Response Payload Size KPI -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-amber-400/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Received Payload Size</span>
              <Layers class="w-4 h-4 text-amber-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-amber-300">
                {{ formatBytes(getPayloadSize(selectedRequest.response_body)) }}
              </span>
            </div>
            <div class="mt-2 text-[10px] text-text-muted">
              Inbound response size
            </div>
          </div>
        </div>
      </div>

      <!-- Network / Connection Failure Card (if any) -->
      <div v-if="selectedRequest.status_code === 0 || selectedRequest.status_code >= 400 || selectedRequest.exception_info" class="glass-surface-elevated rounded-2xl border border-rose-500/30 overflow-hidden shadow-2xl relative">
        <div class="p-5 bg-rose-500/10 border-b border-rose-500/20 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-rose-500/20 text-rose-400">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-950/80 text-rose-200">
                  {{ selectedRequest.status_code === 0 ? 'Network Connection Failure' : 'HTTP ' + selectedRequest.status_code + ' Error' }}
                </span>
              </div>
              <h3 class="text-base font-bold text-rose-200 mt-1 leading-snug">
                Third-Party Service Call Error
              </h3>
            </div>
          </div>
          <button
            v-if="selectedRequest.exception_info"
            @click="copyToClipboard(selectedRequest.exception_info, 'failure_info')"
            class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-rose-500/30 hover:border-rose-400 text-rose-300"
          >
            <component :is="copiedField === 'failure_info' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
            <span>{{ copiedField === 'failure_info' ? 'Copied Error!' : 'Copy Error Details' }}</span>
          </button>
        </div>
        <div v-if="selectedRequest.exception_info" class="p-5">
          <pre class="bg-surface-base rounded-xl p-4 overflow-x-auto border border-border-subtle text-xs text-rose-300 font-mono leading-relaxed whitespace-pre-wrap">{{ selectedRequest.exception_info }}</pre>
        </div>
      </div>

      <!-- Bento: Request & Response Bodies Side-by-Side -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <!-- Request Body Card -->
        <div id="section-request" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
          <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span :class="['px-2 py-0.5 text-xs font-bold rounded uppercase border font-mono', methodColor(selectedRequest.method)]">
                {{ selectedRequest.method }}
              </span>
              <h3 class="text-sm font-bold text-text-primary">Sent Payload</h3>
              <span v-if="selectedRequest.request_body" class="text-[10px] px-2 py-0.5 rounded-full bg-surface-elevated text-text-muted border border-border-subtle font-mono">
                {{ formatBytes(getPayloadSize(selectedRequest.request_body)) }}
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <div v-if="selectedRequest.request_body" class="flex items-center bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-[11px]">
                <button
                  type="button"
                  @click="requestBodyViewMode = 'formatted'"
                  :class="['px-2 py-0.5 rounded', requestBodyViewMode === 'formatted' ? 'bg-surface-raised text-text-primary font-bold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                >
                  Formatted
                </button>
                <button
                  type="button"
                  @click="requestBodyViewMode = 'raw'"
                  :class="['px-2 py-0.5 rounded', requestBodyViewMode === 'raw' ? 'bg-surface-raised text-text-primary font-bold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                >
                  Raw
                </button>
              </div>

              <button
                v-if="selectedRequest.request_body"
                @click="copyToClipboard(selectedRequest.request_body, 'req_body')"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-accent transition-colors border border-border-subtle"
                title="Copy request payload"
              >
                <component :is="copiedField === 'req_body' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
              </button>

              <button
                @click="toggleSection('request')"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
              >
                <component :is="collapsed.request ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div v-show="!collapsed.request" class="p-4 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Outbound Content</h4>
                <div v-if="selectedRequest.request_body" class="relative">
                  <Search class="w-3 h-3 text-text-muted absolute left-2 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="searchRequestBody"
                    type="text"
                    placeholder="Filter in payload..."
                    class="pl-6 pr-2 py-0.5 text-[11px] bg-surface-elevated border border-border-subtle rounded-md text-text-primary focus:border-accent w-40"
                  />
                </div>
              </div>
              <div class="bg-surface-elevated rounded-xl p-4 overflow-x-auto border border-border-subtle min-h-[160px] max-h-[450px]">
                <div v-if="selectedRequest.request_body">
                  <pre v-if="requestBodyViewMode === 'formatted'" class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap">{{ formatJson(selectedRequest.request_body) }}</pre>
                  <pre v-else class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap break-all">{{ getRawString(selectedRequest.request_body) }}</pre>
                </div>
                <div v-else class="text-xs text-text-muted italic flex items-center justify-center h-28">
                  No request body submitted with this call (e.g. GET/HEAD).
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Response Body Card -->
        <div id="section-response" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
          <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span :class="['px-2 py-0.5 text-xs font-bold rounded border font-mono', statusColor(selectedRequest.status_code)]">
                {{ statusText(selectedRequest.status_code) }}
              </span>
              <h3 class="text-sm font-bold text-text-primary">Received Response</h3>
              <span v-if="selectedRequest.response_body" class="text-[10px] px-2 py-0.5 rounded-full bg-surface-elevated text-text-muted border border-border-subtle font-mono">
                {{ formatBytes(getPayloadSize(selectedRequest.response_body)) }}
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <div v-if="selectedRequest.response_body" class="flex items-center bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-[11px]">
                <button
                  type="button"
                  @click="responseBodyViewMode = 'formatted'"
                  :class="['px-2 py-0.5 rounded', responseBodyViewMode === 'formatted' ? 'bg-surface-raised text-text-primary font-bold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                >
                  Formatted
                </button>
                <button
                  type="button"
                  @click="responseBodyViewMode = 'raw'"
                  :class="['px-2 py-0.5 rounded', responseBodyViewMode === 'raw' ? 'bg-surface-raised text-text-primary font-bold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                >
                  Raw
                </button>
              </div>

              <button
                v-if="selectedRequest.response_body"
                @click="downloadResponseJson"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-accent transition-colors border border-border-subtle"
                title="Download Response as JSON"
              >
                <Download class="w-3.5 h-3.5" />
              </button>

              <button
                v-if="selectedRequest.response_body"
                @click="copyToClipboard(selectedRequest.response_body, 'res_body')"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-accent transition-colors border border-border-subtle"
                title="Copy response body"
              >
                <component :is="copiedField === 'res_body' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
              </button>

              <button
                @click="toggleSection('response')"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
              >
                <component :is="collapsed.response ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div v-show="!collapsed.response" class="p-4 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Inbound Response Content</h4>
                <div v-if="selectedRequest.response_body" class="relative">
                  <Search class="w-3 h-3 text-text-muted absolute left-2 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="searchResponseBody"
                    type="text"
                    placeholder="Filter in response..."
                    class="pl-6 pr-2 py-0.5 text-[11px] bg-surface-elevated border border-border-subtle rounded-md text-text-primary focus:border-accent w-40"
                  />
                </div>
              </div>
              <div class="bg-surface-elevated rounded-xl p-4 overflow-x-auto border border-border-subtle min-h-[160px] max-h-[500px]">
                <div v-if="selectedRequest.response_body">
                  <pre v-if="responseBodyViewMode === 'formatted'" class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap">{{ formatJson(selectedRequest.response_body) }}</pre>
                  <pre v-else class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap break-all">{{ getRawString(selectedRequest.response_body) }}</pre>
                </div>
                <div v-else class="text-xs text-text-muted italic flex items-center justify-center h-28">
                  No response body returned from external service.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bento: HTTP Headers & Technical Architecture Card -->
      <div id="section-headers" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
        <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <Layers class="w-4 h-4 text-accent" />
            <h3 class="text-sm font-bold text-text-primary">Egress Headers & Technical Metadata</h3>
          </div>

          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="w-3 h-3 text-text-muted absolute left-2 top-1/2 -translate-y-1/2" />
              <input
                v-model="searchHeaders"
                type="text"
                placeholder="Search headers..."
                class="pl-6 pr-2 py-0.5 text-[11px] bg-surface-elevated border border-border-subtle rounded-md text-text-primary focus:border-accent w-36"
              />
            </div>
            <button
              @click="toggleSection('headers')"
              class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
            >
              <component :is="collapsed.headers ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <div v-show="!collapsed.headers" class="p-5 space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Outgoing Request Headers -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Outbound Request Headers ({{ Object.keys(filteredRequestHeaders).length }})
                </h4>
                <button
                  v-if="selectedRequest.request_headers"
                  @click="copyToClipboard(selectedRequest.request_headers, 'out_req_headers')"
                  class="flex items-center gap-1 text-xs text-text-muted hover:text-accent transition-colors"
                >
                  <component :is="copiedField === 'out_req_headers' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
                  <span>{{ copiedField === 'out_req_headers' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
              <div class="bg-surface-elevated rounded-xl p-3.5 space-y-2 overflow-x-auto border border-border-subtle max-h-72 overflow-y-auto">
                <div
                  v-for="(val, header) in filteredRequestHeaders"
                  :key="header"
                  class="text-xs font-mono flex items-start gap-2 border-b border-border-subtle/50 pb-1.5 last:border-0 last:pb-0"
                >
                  <span class="font-bold text-accent min-w-[130px] break-all">{{ header }}:</span>
                  <span class="text-text-secondary break-all flex-1 select-all">{{ Array.isArray(val) ? val.join(', ') : val }}</span>
                </div>
                <p v-if="Object.keys(filteredRequestHeaders).length === 0" class="text-xs text-text-muted italic py-2">
                  No request headers captured.
                </p>
              </div>
            </div>

            <!-- Inbound Response Headers -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Inbound Response Headers ({{ Object.keys(filteredResponseHeaders).length }})
                </h4>
                <button
                  v-if="selectedRequest.response_headers"
                  @click="copyToClipboard(selectedRequest.response_headers, 'out_res_headers')"
                  class="flex items-center gap-1 text-xs text-text-muted hover:text-accent transition-colors"
                >
                  <component :is="copiedField === 'out_res_headers' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
                  <span>{{ copiedField === 'out_res_headers' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
              <div class="bg-surface-elevated rounded-xl p-3.5 space-y-2 overflow-x-auto border border-border-subtle max-h-72 overflow-y-auto">
                <div
                  v-for="(val, header) in filteredResponseHeaders"
                  :key="header"
                  class="text-xs font-mono flex items-start gap-2 border-b border-border-subtle/50 pb-1.5 last:border-0 last:pb-0"
                >
                  <span class="font-bold text-sky-400 min-w-[130px] break-all">{{ header }}:</span>
                  <span class="text-text-secondary break-all flex-1 select-all">{{ Array.isArray(val) ? val.join(', ') : val }}</span>
                </div>
                <p v-if="Object.keys(filteredResponseHeaders).length === 0" class="text-xs text-text-muted italic py-2">
                  No response headers captured.
                </p>
              </div>
            </div>
          </div>

          <!-- Architecture Context -->
          <div class="space-y-2 pt-2 border-t border-border-subtle">
            <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Egress Execution Context</h4>
            <div class="bg-surface-elevated rounded-xl border border-border-subtle overflow-hidden">
              <dl class="divide-y divide-border-subtle text-xs">
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Target Domain</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono font-bold text-accent">{{ selectedRequest.domain }}</dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Parent Ingress Context</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-primary">
                    <span v-if="selectedRequest.parent_request_id">
                      Linked to Request #{{ selectedRequest.parent_request_id }}
                    </span>
                    <span v-else class="text-text-muted">Standalone Egress Call (No Parent Request)</span>
                  </dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Egress Record ID</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-muted select-all">{{ selectedRequest.id }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Floating Mini-Dock for Fast Bento Navigation -->
      <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
        <div class="flex items-center gap-1.5 p-1.5 rounded-full bg-surface-elevated/90 backdrop-blur-xl border border-border-default/80 shadow-[0_10px_35px_rgba(0,0,0,0.6)]">
          <button
            type="button"
            @click="scrollToSection('section-kpis')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
          >
            <Activity class="w-3.5 h-3.5 text-accent" />
            <span class="hidden sm:inline">Metrics</span>
          </button>

          <button
            type="button"
            @click="scrollToSection('section-request')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
          >
            <Cpu class="w-3.5 h-3.5 text-sky-400" />
            <span class="hidden sm:inline">Payload</span>
          </button>

          <button
            type="button"
            @click="scrollToSection('section-response')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
          >
            <Globe class="w-3.5 h-3.5 text-emerald-400" />
            <span class="hidden sm:inline">Response</span>
          </button>

          <button
            type="button"
            @click="scrollToSection('section-headers')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
          >
            <Layers class="w-3.5 h-3.5 text-amber-400" />
            <span class="hidden md:inline">Headers</span>
          </button>

          <div class="h-4 w-px bg-border-subtle mx-0.5" />

          <!-- Master Toggle -->
          <button
            type="button"
            @click="toggleAllSections"
            class="p-1.5 rounded-full text-text-muted hover:text-text-primary hover:bg-surface-hover transition-all"
            :title="allExpanded ? 'Collapse all Bento cards' : 'Expand all Bento cards'"
          >
            <ChevronsUpDown class="w-4 h-4" />
          </button>

          <!-- Copy Markdown Shortcut -->
          <button
            type="button"
            @click="copyMarkdown"
            class="p-1.5 rounded-full text-sky-400 hover:bg-sky-500/20 transition-all"
            title="Copy outgoing request Markdown"
          >
            <component :is="copiedField === 'markdown' ? CheckCheck : FileText" class="w-3.5 h-3.5" />
          </button>

          <!-- Close Detail Shortcut -->
          <button
            type="button"
            @click="closeDetail"
            class="p-1.5 rounded-full text-text-muted hover:text-text-primary hover:bg-surface-hover transition-all"
            title="Back to Table"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- VIEW B: OUTGOING REQUESTS LIST & TABLE     -->
    <!-- ========================================== -->
    <div v-else class="space-y-6">
      <!-- Master Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
            <ExternalLink class="w-6 h-6 text-accent" /> Outgoing API Observability (Egress)
          </h1>
          <p class="text-sm text-text-secondary mt-0.5">
            Transparently monitor external HTTP requests made by your application (Stripe, OpenAI, Twilio, AWS)
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="fetchOutgoingData"
            class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
            title="Refresh Egress Data"
          >
            <RefreshCw :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" />
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- Bento KPI Strip -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Calls -->
        <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-text-muted">Total Egress Calls</span>
            <div class="p-2 rounded-xl bg-accent-soft text-accent">
              <Globe class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
              {{ stats.total_outgoing_requests.toLocaleString() }}
            </span>
          </div>
          <div class="mt-2 text-[10px] text-text-muted flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-accent inline-block animate-pulse" />
            <span>Third-Party HTTP requests</span>
          </div>
        </div>

        <!-- KPI 2: Average Latency -->
        <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-amber-400/40 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-text-muted">Average Egress Latency</span>
            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
              <Clock class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black font-mono tracking-tight text-amber-300">
              {{ stats.avg_latency }}
            </span>
            <span class="text-xs font-mono text-text-muted">ms</span>
          </div>
          <div class="mt-2 text-[10px] text-text-muted">
            Round-trip third-party time
          </div>
        </div>

        <!-- KPI 3: Error Rate -->
        <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-rose-500/40 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-text-muted">Third-Party Error Rate</span>
            <div class="p-2 rounded-xl bg-rose-500/10 text-rose-400">
              <AlertTriangle class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black font-mono tracking-tight text-rose-400">
              {{ stats.error_rate }}%
            </span>
          </div>
          <div class="mt-2 flex items-center gap-1.5">
            <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', stats.error_rate > 5 ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30']">
              {{ stats.error_rate > 5 ? 'Elevated Errors' : 'Healthy Network' }}
            </span>
          </div>
        </div>

        <!-- KPI 4: External Services Monitored -->
        <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-text-muted">External Domains</span>
            <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
              <Layers class="w-4 h-4" />
            </div>
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
              {{ (stats.top_domains || []).length }}
            </span>
            <span class="text-xs font-mono text-text-muted">active</span>
          </div>
          <div class="mt-2 text-[10px] text-text-muted">
            Distinct external providers
          </div>
        </div>
      </div>

      <!-- Top Domains Breakdown Bento Strip -->
      <div v-if="stats.top_domains && stats.top_domains.length > 0" class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
            <Globe class="w-3.5 h-3.5 text-accent" /> Top External Domains (Click pill to filter)
          </h2>
          <button
            v-if="activeDomain"
            @click="activeDomain = ''; fetchOutgoingData()"
            class="text-[11px] text-accent hover:underline flex items-center gap-1"
          >
            Clear Domain Filter ({{ activeDomain }})
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <button
            v-for="d in stats.top_domains"
            :key="d.domain"
            type="button"
            @click="filterByDomain(d.domain)"
            :class="[
              'p-3 rounded-xl border text-left transition-all group flex flex-col justify-between',
              activeDomain === d.domain
                ? 'bg-accent/15 border-accent text-accent shadow-sm'
                : 'bg-surface-elevated hover:bg-surface-hover border-border-subtle'
            ]"
          >
            <div class="flex items-center justify-between w-full">
              <span class="font-mono text-xs font-bold text-accent truncate">{{ d.domain }}</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded bg-surface-raised border border-border-subtle font-mono text-text-muted">
                {{ d.count }} calls
              </span>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs text-text-secondary">
              <span class="text-[11px] text-text-muted">Average Latency:</span>
              <span class="font-mono font-bold text-text-primary">{{ d.avg_duration }} ms</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle space-y-3">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <!-- Search Bar -->
          <div class="relative flex-1">
            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" />
            <input
              v-model="searchInput"
              type="text"
              placeholder="Search by URL, domain, method, or payload content..."
              class="w-full pl-10 pr-8 py-2 text-xs bg-surface-elevated border border-border-default rounded-xl text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
            />
            <button
              v-if="searchInput"
              @click="searchInput = ''; fetchOutgoingData()"
              type="button"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Method Selector Pills -->
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            <button
              type="button"
              @click="setMethodFilter('')"
              :class="['px-2.5 py-1 rounded-lg text-xs font-bold font-mono transition-all', !activeMethod && (!advancedFilters.method || advancedFilters.method.length === 0) ? 'bg-accent text-text-inverted shadow-xs' : 'bg-surface-elevated text-text-secondary hover:text-text-primary border border-border-subtle']"
            >
              ALL
            </button>
            <button
              v-for="m in ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']"
              :key="m"
              type="button"
              @click="setMethodFilter(m)"
              :class="['px-2.5 py-1 rounded-lg text-xs font-bold font-mono transition-all', activeMethod === m || (advancedFilters.method && advancedFilters.method.includes(m)) ? 'bg-accent text-text-inverted shadow-xs' : 'bg-surface-elevated text-text-secondary hover:text-text-primary border border-border-subtle']"
            >
              {{ m }}
            </button>
          </div>

          <!-- Status Selector Pills -->
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
            <button
              type="button"
              @click="setStatusFilter('')"
              :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', !activeStatusGroup && !advancedFilters.status_group && (!advancedFilters.status_code || advancedFilters.status_code.length === 0) ? 'bg-surface-raised text-text-primary border border-border-default' : 'text-text-muted hover:text-text-primary']"
            >
              Any Status
            </button>
            <button
              type="button"
              @click="setStatusFilter('2xx')"
              :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeStatusGroup === '2xx' || advancedFilters.status_group === '2xx' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-text-muted hover:text-emerald-400']"
            >
              2xx Success
            </button>
            <button
              type="button"
              @click="setStatusFilter('errors')"
              :class="['px-2.5 py-1 rounded-lg text-xs font-bold transition-all', activeStatusGroup === 'errors' || advancedFilters.status_group === 'errors' ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'text-text-muted hover:text-rose-400']"
            >
              Errors / Failures
            </button>
          </div>

          <!-- Filter Drawer Trigger Button -->
          <button
            type="button"
            @click="isFilterOpen = true"
            :class="[
              'btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl transition-all relative shrink-0',
              activeFilterCount > 0 ? 'border-accent text-accent bg-accent-soft' : ''
            ]"
            title="Open Advanced Outgoing Filters"
          >
            <SlidersHorizontal class="w-3.5 h-3.5" />
            <span>Filters</span>
            <span
              v-if="activeFilterCount > 0"
              class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-accent text-text-inverted"
            >
              {{ activeFilterCount }}
            </span>
          </button>
        </div>

        <!-- Active Filter Tags Bar -->
        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border-subtle/60 text-xs">
          <span class="text-text-muted font-semibold mr-1">Active Filters:</span>
          
          <span v-if="searchInput || advancedFilters.search" class="badge bg-accent-soft text-accent border border-accent/20 flex items-center gap-1 font-mono text-[11px]">
            Query: "{{ searchInput || advancedFilters.search }}"
            <button type="button" @click="removeFilterKey('search')"><X class="w-3 h-3" /></button>
          </span>

          <span v-if="activeDomain || advancedFilters.domain" class="badge bg-purple-500/10 text-purple-300 border border-purple-500/20 flex items-center gap-1 font-mono text-[11px]">
            Domain: {{ activeDomain || advancedFilters.domain }}
            <button type="button" @click="removeFilterKey('domain')"><X class="w-3 h-3" /></button>
          </span>

          <span v-if="advancedFilters.url" class="badge bg-surface-raised text-text-secondary border border-border-default flex items-center gap-1 font-mono text-[11px]">
            URL: {{ advancedFilters.url }}
            <button type="button" @click="removeFilterKey('url')"><X class="w-3 h-3" /></button>
          </span>

          <span v-if="advancedFilters.parent_request_id" class="badge bg-surface-raised text-text-secondary border border-border-default flex items-center gap-1 font-mono text-[11px]">
            Parent ID: {{ advancedFilters.parent_request_id }}
            <button type="button" @click="removeFilterKey('parent_request_id')"><X class="w-3 h-3" /></button>
          </span>

          <template v-if="advancedFilters.method && advancedFilters.method.length > 0">
            <span
              v-for="m in advancedFilters.method"
              :key="'m-' + m"
              class="badge bg-info-soft text-info border border-info/20 flex items-center gap-1 font-mono text-[11px]"
            >
              Method: {{ m }}
              <button type="button" @click="removeMethodFilter(m)"><X class="w-3 h-3" /></button>
            </span>
          </template>
          <span v-else-if="activeMethod" class="badge bg-info-soft text-info border border-info/20 flex items-center gap-1 font-mono text-[11px]">
            Method: {{ activeMethod }}
            <button type="button" @click="setMethodFilter(activeMethod)"><X class="w-3 h-3" /></button>
          </span>

          <template v-if="advancedFilters.status_code && advancedFilters.status_code.length > 0">
            <span
              v-for="s in advancedFilters.status_code"
              :key="'s-' + s"
              class="badge bg-warning-soft text-warning border border-warning/20 flex items-center gap-1 font-mono text-[11px]"
            >
              Status: {{ s }}
              <button type="button" @click="removeStatusCodeFilter(s)"><X class="w-3 h-3" /></button>
            </span>
          </template>

          <span v-if="activeStatusGroup || advancedFilters.status_group" class="badge bg-warning-soft text-warning border border-warning/20 flex items-center gap-1 font-mono text-[11px]">
            Group: {{ activeStatusGroup || advancedFilters.status_group }}
            <button type="button" @click="removeFilterKey('status_group')"><X class="w-3 h-3" /></button>
          </span>

          <span v-if="advancedFilters.duration_min !== '' || advancedFilters.duration_max !== ''" class="badge bg-surface-raised text-text-secondary border border-border-default flex items-center gap-1 font-mono text-[11px]">
            Duration: {{ advancedFilters.duration_min || 0 }}ms - {{ advancedFilters.duration_max || 'inf' }}ms
            <button type="button" @click="removeFilterKey('duration')"><X class="w-3 h-3" /></button>
          </span>

          <span v-if="advancedFilters.date_from || advancedFilters.date_to" class="badge bg-surface-raised text-text-secondary border border-border-default flex items-center gap-1 font-mono text-[11px]">
            Date Range
            <button type="button" @click="removeFilterKey('date')"><X class="w-3 h-3" /></button>
          </span>

          <button
            @click="clearAllFilters"
            class="text-[11px] text-text-muted hover:text-text-primary underline ml-2"
          >
            Clear all
          </button>
        </div>
      </div>

      <!-- Outgoing Requests Table Card -->
      <div class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
        <div class="px-5 py-4 bg-surface-raised border-b border-border-subtle flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-text-primary">Egress Request Logs</h2>
            <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-surface-elevated text-text-muted border border-border-subtle">
              {{ totalCount }} total
            </span>
          </div>
          <div class="text-xs text-text-muted">
            Click any row to inspect full Bento request details
          </div>
        </div>

        <div v-if="loading" class="p-12 text-center text-text-muted space-y-2">
          <RefreshCw class="w-6 h-6 animate-spin mx-auto text-accent" />
          <p class="text-xs font-medium">Loading outgoing HTTP logs...</p>
        </div>

        <div v-else-if="outgoingRequests.length === 0" class="p-12 text-center text-text-muted space-y-2">
          <Globe class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
          <p class="text-sm font-bold text-text-primary">No outgoing HTTP calls found</p>
          <p class="text-xs text-text-secondary">
            {{ hasActiveFilters ? 'No egress requests match your active filters.' : 'Your application has not made any outgoing HTTP calls yet via Laravel Http Client.' }}
          </p>
          <button v-if="hasActiveFilters" @click="clearAllFilters" class="btn btn-secondary text-xs mt-3">
            Reset Active Filters
          </button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase text-[10px] font-semibold tracking-wider">
              <tr>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Domain & Target Endpoint</th>
                <th class="px-4 py-3">Parent Ingress Context</th>
                <th class="px-4 py-3">Duration</th>
                <th class="px-4 py-3">Timestamp</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle font-mono">
              <tr
                v-for="req in outgoingRequests"
                :key="req.id"
                @click="selectRequest(req)"
                class="hover:bg-surface-hover cursor-pointer transition-colors group"
              >
                <!-- Method -->
                <td class="px-4 py-3">
                  <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border uppercase', methodColor(req.method)]">
                    {{ req.method }}
                  </span>
                </td>

                <!-- Status Code -->
                <td class="px-4 py-3">
                  <span :class="['px-2 py-0.5 rounded text-[11px] font-bold border', statusColor(req.status_code)]">
                    {{ req.status_code === 0 ? 'FAILED' : req.status_code }}
                  </span>
                </td>

                <!-- Domain & URL -->
                <td class="px-4 py-3 max-w-md truncate" :title="req.url">
                  <div class="flex items-center gap-1.5 truncate">
                    <span class="font-bold text-accent">{{ req.domain }}</span>
                    <span class="text-text-secondary truncate">{{ req.url.replace(/^https?:\/\/[^\/]+/, '') || '/' }}</span>
                  </div>
                </td>

                <!-- Parent Correlation -->
                <td class="px-4 py-3 text-text-muted">
                  <span v-if="req.parent_request_id" class="inline-flex items-center gap-1 text-[11px] text-accent hover:underline" @click.stop="router.push('/requests/' + req.parent_request_id)">
                    <ArrowLeftRight class="w-3 h-3 text-accent" />
                    <span>#{{ req.parent_request_id.substring(0, 8) }}</span>
                  </span>
                  <span v-else class="text-text-muted/60">—</span>
                </td>

                <!-- Duration -->
                <td class="px-4 py-3 font-bold text-text-primary">
                  <span :class="req.duration_ms > 800 ? 'text-rose-400' : req.duration_ms > 300 ? 'text-amber-400' : 'text-emerald-400'">
                    {{ req.duration_ms }} ms
                  </span>
                </td>

                <!-- Timestamp -->
                <td class="px-4 py-3 text-text-muted font-sans text-xs">
                  <div class="font-medium text-text-secondary">{{ formatDistanceToNow(new Date(req.created_at), { addSuffix: true }) }}</div>
                  <div class="text-[10px] text-text-muted font-mono">{{ new Date(req.created_at).toISOString().replace('T', ' ').substring(0, 19) }}</div>
                </td>

                <!-- Action -->
                <td class="px-4 py-3 text-right" @click.stop="selectRequest(req)">
                  <button class="btn btn-secondary text-xs px-2.5 py-1 rounded-lg border border-border-subtle group-hover:border-accent transition-all">
                    Inspect
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div class="p-4 border-t border-border-subtle bg-surface-raised flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-3">
            <span class="text-text-muted">Rows per page:</span>
            <select
              v-model="perPage"
              @change="currentPage = 1; fetchOutgoingData()"
              class="bg-surface-elevated border border-border-subtle rounded-lg px-2 py-1 text-text-primary focus:border-accent focus:outline-none"
            >
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <span class="text-text-muted">
              Showing page {{ currentPage }} of {{ totalPages }} ({{ totalCount }} total records)
            </span>
          </div>

          <div class="flex items-center gap-1.5">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="p-1.5 rounded-lg border border-border-subtle bg-surface-elevated hover:bg-surface-hover disabled:opacity-40 disabled:cursor-not-allowed text-text-secondary hover:text-text-primary transition-all"
              title="Previous Page"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="p-1.5 rounded-lg border border-border-subtle bg-surface-elevated hover:bg-surface-hover disabled:opacity-40 disabled:cursor-not-allowed text-text-secondary hover:text-text-primary transition-all"
              title="Next Page"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced Filter Slide-over Drawer -->
    <OutgoingFilterSidebar
      :is-open="isFilterOpen"
      :filters="advancedFilters"
      :available-domains="availableDomains"
      @close="isFilterOpen = false"
      @apply="applyAdvancedFilters"
      @reset="resetAdvancedFilters"
    />
  </div>
</template>
