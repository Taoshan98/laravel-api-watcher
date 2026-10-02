<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRequests } from '../composables/useRequests';
import { formatDistanceToNow } from 'date-fns';
import ReplayModal from '../components/ReplayModal.vue';
import {
  ArrowLeft,
  Copy,
  RefreshCw,
  Clock,
  HardDrive,
  Globe,
  CheckCheck,
  FileText,
  Database,
  Cpu,
  Layers,
  ExternalLink,
  Code,
  AlertTriangle,
  User,
  CheckCircle2,
  XCircle,
  AlertCircle,
  FileCode,
  Tag,
  ChevronDown,
  ChevronUp,
  Search,
  ChevronsUpDown,
  Download,
  Terminal,
  Activity,
  Maximize2,
  Minimize2
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const { request, loading, fetchRequest, replayRequest } = useRequests();

const isReplayModalOpen = ref(false);
const replayLoading = ref(false);
const replayResponse = ref(null);
const replayError = ref(null);
const copiedField = ref(null);

const waterfallData = ref(null);
const waterfallLoading = ref(false);

// Interactive Bento State
const collapsed = ref({
  kpis: false,
  exception: false,
  request: false,
  response: false,
  waterfall: false,
  headers: false,
  context: false,
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

// In-card Search & View Modes
const searchRequestBody = ref('');
const requestBodyViewMode = ref('formatted'); // 'formatted' | 'raw'

const searchResponseBody = ref('');
const responseBodyViewMode = ref('formatted'); // 'formatted' | 'raw'

const searchHeaders = ref('');
const searchQueryParams = ref('');

const searchTrace = ref('');
const appOnlyFrames = ref(false);

const fetchWaterfall = async (id) => {
  waterfallLoading.value = true;
  try {
    const res = await fetch(`/api-watcher/api/requests/${id}/waterfall`);
    if (res.ok) {
      waterfallData.value = await res.json();
    }
  } catch (e) {
    console.error('Failed to load waterfall data', e);
  } finally {
    waterfallLoading.value = false;
  }
};

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
  copiedField.value = 'curl';
  setTimeout(() => (copiedField.value = null), 2000);
};

const copyToClipboard = (text, fieldName) => {
  if (!text) return;
  const str = typeof text === 'object' ? JSON.stringify(text, null, 2) : String(text);
  navigator.clipboard.writeText(str);
  copiedField.value = fieldName;
  setTimeout(() => {
    if (copiedField.value === fieldName) {
      copiedField.value = null;
    }
  }, 2000);
};

const downloadResponseJson = () => {
  if (!request.value?.response_body) return;
  const content = typeof request.value.response_body === 'string'
    ? request.value.response_body
    : JSON.stringify(request.value.response_body, null, 2);
  const blob = new Blob([content], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `response-${request.value.id}.json`;
  a.click();
  URL.revokeObjectURL(url);
};

const scrollToSection = (id) => {
  const sectionKey = id.replace('section-', '');
  if (collapsed.value[sectionKey] !== undefined) {
    collapsed.value[sectionKey] = false;
  }
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

const formatBytes = (bytes, decimals = 2) => {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const formatMemory = (kb) => {
  if (!kb) return '—';
  if (kb < 1024) return `${kb} KB`;
  return `${(kb / 1024).toFixed(2)} MB`;
};

const formatJson = (data) => {
  if (!data) return '';
  if (typeof data === 'object') {
    return JSON.stringify(data, null, 2);
  }
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

const statusColor = (code) => {
  if (!code) return 'bg-border-subtle text-text-muted border-border-default';
  if (code >= 200 && code < 300) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30';
  if (code >= 300 && code < 400) return 'bg-sky-500/10 text-sky-400 border-sky-500/30';
  if (code >= 400 && code < 500) return 'bg-amber-500/10 text-amber-400 border-amber-500/30';
  return 'bg-rose-500/10 text-rose-400 border-rose-500/30';
};

const statusText = (code) => {
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
    405: 'Method Not Allowed',
    422: 'Unprocessable Entity',
    429: 'Too Many Requests',
    500: 'Internal Server Error',
    502: 'Bad Gateway',
    503: 'Service Unavailable',
    504: 'Gateway Timeout',
  };
  return map[code] ? `${code} ${map[code]}` : `${code}`;
};

const methodColor = (method) => {
  const map = {
    GET: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    POST: 'bg-sky-500/10 text-sky-400 border-sky-500/30',
    PUT: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    PATCH: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    DELETE: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
  };
  return map[method] || 'bg-surface-elevated text-text-secondary border-border-default';
};

const queryParams = computed(() => {
  if (!request.value?.url) return [];
  try {
    const url = new URL(request.value.url);
    const params = [];
    url.searchParams.forEach((val, key) => {
      params.push({ key, val });
    });
    return params;
  } catch {
    return [];
  }
});

const filteredQueryParams = computed(() => {
  if (!searchQueryParams.value.trim()) return queryParams.value;
  const q = searchQueryParams.value.toLowerCase();
  return queryParams.value.filter(p => p.key.toLowerCase().includes(q) || p.val.toLowerCase().includes(q));
});

const getExceptionInfo = (req) => {
  if (!req?.exception_info) return null;
  try {
    return typeof req.exception_info === 'string' ? JSON.parse(req.exception_info) : req.exception_info;
  } catch {
    return { message: String(req.exception_info) };
  }
};

const isAppFrame = (file) => {
  if (!file) return false;
  return !file.includes('/vendor/') && (file.includes('/app/') || file.includes('app/'));
};

const filteredTraceFrames = computed(() => {
  const info = getExceptionInfo(request.value);
  if (!info || !Array.isArray(info.trace)) return [];
  let frames = info.trace;
  if (appOnlyFrames.value) {
    frames = frames.filter(f => isAppFrame(f.file));
  }
  if (searchTrace.value.trim()) {
    const q = searchTrace.value.toLowerCase();
    frames = frames.filter(f =>
      (f.file && f.file.toLowerCase().includes(q)) ||
      (f.function && f.function.toLowerCase().includes(q)) ||
      (f.class && f.class.toLowerCase().includes(q)) ||
      (f.line && String(f.line).includes(q))
    );
  }
  return frames;
});

const formatTraceForCopy = (exceptionInfo) => {
  if (!exceptionInfo) return '';
  if (typeof exceptionInfo.trace === 'string') return exceptionInfo.trace;
  if (Array.isArray(exceptionInfo.trace) && exceptionInfo.trace.length > 0) {
    return exceptionInfo.trace.map((f, i) => {
      const call = (f.class || '') + (f.type || '') + (f.function || '');
      return `#${i} ${f.file || 'unknown'}(${f.line || '?'}) ${call}`;
    }).join('\n');
  }
  return `${exceptionInfo.class ? exceptionInfo.class + ': ' : ''}${exceptionInfo.message || ''}\n${exceptionInfo.file || ''}:${exceptionInfo.line || ''}`;
};

const copyMarkdown = () => {
  if (!request.value) return;
  const req = request.value;
  let md = '';

  md += `# API Request: ${req.method} ${req.url}\n\n`;

  md += `## Overview\n`;
  md += `- **Status:** ${statusText(req.status_code)}\n`;
  md += `- **Duration:** ${req.duration_ms} ms\n`;
  md += `- **Timestamp:** ${new Date(req.created_at).toISOString().replace('T', ' ').substring(0, 19)} UTC\n`;
  md += `- **Client IP:** \`${req.ip_address || '—'}\`\n`;
  md += `- **User:** ${req.user_id ? `User #${req.user_id}` : 'Guest (Unauthenticated)'}\n`;
  md += `- **Route:** \`${req.route_name || '—'}\`\n`;
  md += `- **Action:** \`${req.controller_action || '—'}\`\n`;
  md += `- **Memory Peak:** ${formatMemory(req.memory_usage_kb)}\n`;
  md += `- **Database Queries:** ${req.query_count ?? 0} queries (${req.query_time_ms ?? 0} ms)\n`;
  md += `- **Request UUID:** \`${req.id}\`\n\n`;

  // Exception Details (if present)
  const exc = getExceptionInfo(req);
  if (exc) {
    md += `## Exception / Crash Details\n`;
    if (exc.class) md += `- **Exception Class:** \`${exc.class}\`\n`;
    if (exc.message) md += `- **Message:** ${exc.message}\n`;
    if (exc.file) md += `- **Crash Origin:** \`${exc.file}:${exc.line || '?'}\`\n`;
    const trace = formatTraceForCopy(exc);
    if (trace) {
      md += `\n### Stack Trace\n\`\`\`\n${trace}\n\`\`\`\n\n`;
    } else {
      md += `\n`;
    }
  }

  // Query Parameters
  if (queryParams.value && queryParams.value.length > 0) {
    md += `## Query Parameters (${queryParams.value.length})\n\n`;
    md += `| Parameter | Value |\n`;
    md += `| :--- | :--- |\n`;
    queryParams.value.forEach(p => {
      md += `| \`${p.key}\` | \`${String(p.val).replace(/\|/g, '\\|')}\` |\n`;
    });
    md += `\n`;
  }

  // Request Headers
  if (req.request_headers && Object.keys(req.request_headers).length > 0) {
    md += `## Request Headers (${Object.keys(req.request_headers).length})\n\n\`\`\`http\n`;
    Object.entries(req.request_headers).forEach(([k, v]) => {
      const valStr = Array.isArray(v) ? v.join(', ') : v;
      md += `${k}: ${valStr}\n`;
    });
    md += `\`\`\`\n\n`;
  }

  // Request Body
  if (req.request_body) {
    md += `## Request Payload\n\n`;
    const formatted = formatJson(req.request_body);
    md += `\`\`\`json\n${formatted}\n\`\`\`\n\n`;
  }

  // Response Headers
  if (req.response_headers && Object.keys(req.response_headers).length > 0) {
    md += `## Response Headers (${Object.keys(req.response_headers).length})\n\n\`\`\`http\n`;
    Object.entries(req.response_headers).forEach(([k, v]) => {
      const valStr = Array.isArray(v) ? v.join(', ') : v;
      md += `${k}: ${valStr}\n`;
    });
    md += `\`\`\`\n\n`;
  }

  // Response Body
  if (req.response_body) {
    md += `## Response Body\n\n`;
    const formatted = formatJson(req.response_body);
    md += `\`\`\`json\n${formatted}\n\`\`\`\n\n`;
  }

  // Outgoing Waterfall (Egress)
  if (waterfallData.value?.outgoing_requests && waterfallData.value.outgoing_requests.length > 0) {
    md += `## Outgoing HTTP Calls (${waterfallData.value.outgoing_requests_count || waterfallData.value.outgoing_requests.length} calls)\n\n`;
    md += `| Status | Method | Domain / URL | Duration |\n`;
    md += `| :--- | :--- | :--- | :--- |\n`;
    waterfallData.value.outgoing_requests.forEach(call => {
      const status = call.status_code === 0 ? 'FAIL' : call.status_code;
      const target = call.domain ? `**${call.domain}** (${call.url})` : call.url;
      md += `| ${status} | ${call.method} | ${target.replace(/\|/g, '\\|')} | ${call.duration_ms} ms |\n`;
    });
    md += `\n`;
  }

  navigator.clipboard.writeText(md.trim());
  copiedField.value = 'markdown';
  setTimeout(() => {
    if (copiedField.value === 'markdown') {
      copiedField.value = null;
    }
  }, 2000);
};

const filteredRequestHeaders = computed(() => {
  if (!request.value?.request_headers) return {};
  if (!searchHeaders.value.trim()) return request.value.request_headers;
  const q = searchHeaders.value.toLowerCase();
  const res = {};
  for (const [k, v] of Object.entries(request.value.request_headers)) {
    if (k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)) {
      res[k] = v;
    }
  }
  return res;
});

const filteredResponseHeaders = computed(() => {
  if (!request.value?.response_headers) return {};
  if (!searchHeaders.value.trim()) return request.value.response_headers;
  const q = searchHeaders.value.toLowerCase();
  const res = {};
  for (const [k, v] of Object.entries(request.value.response_headers)) {
    if (k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)) {
      res[k] = v;
    }
  }
  return res;
});

onMounted(async () => {
  await fetchRequest(route.params.id);
  if (route.params.id) {
    fetchWaterfall(route.params.id);
  }
});
</script>

<template>
  <div class="relative space-y-6 pb-24 animate-[fade-in_0.3s_ease]">
    <!-- Skeleton loader -->
    <div v-if="loading" class="space-y-4">
      <div class="h-28 bg-surface-elevated animate-pulse rounded-2xl border border-border-subtle" />
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="h-28 bg-surface-elevated animate-pulse rounded-2xl border border-border-subtle" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="h-96 bg-surface-elevated animate-pulse rounded-2xl border border-border-subtle" />
        <div class="h-96 bg-surface-elevated animate-pulse rounded-2xl border border-border-subtle" />
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="!request" class="glass-surface-elevated p-12 text-center rounded-2xl">
      <AlertTriangle class="w-12 h-12 text-warning mx-auto mb-3" />
      <h3 class="text-base font-semibold text-text-primary">Request log not found</h3>
      <p class="text-xs text-text-secondary mt-1">The requested API log may have been pruned or expired.</p>
      <button @click="router.push('/requests')" class="btn btn-primary mt-4 text-xs">
        <ArrowLeft class="w-3.5 h-3.5" /> Back to Requests
      </button>
    </div>

    <div v-else class="space-y-6">
      <!-- ═══════════════════════════════════════════════
           1. HERO BENTO CARD: Context & Dev Tools Toolbar
           ═══════════════════════════════════════════════ -->
      <div class="glass-surface-elevated p-5 rounded-2xl border border-border-default shadow-xl relative overflow-hidden">
        <!-- Top Back Bar & Action Buttons -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border-subtle pb-4">
          <div class="flex items-center gap-3">
            <button
              @click="router.push('/requests')"
              class="p-2 rounded-xl bg-surface-elevated hover:bg-surface-hover text-text-secondary hover:text-text-primary transition-all border border-border-subtle group"
              title="Back to Requests List"
            >
              <ArrowLeft class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
            </button>
            <div class="flex items-center gap-2">
              <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg uppercase tracking-wider border font-mono shadow-xs', methodColor(request.method)]">
                {{ request.method }}
              </span>
              <span :class="['px-3 py-1 text-xs font-bold rounded-lg border font-mono shadow-xs', statusColor(request.status_code)]">
                {{ statusText(request.status_code) }}
              </span>
            </div>
          </div>

          <!-- Dev Action Toolbar -->
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

            <!-- Replay Request -->
            <button
              @click="handleReplay"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle hover:border-emerald-400 transition-all"
              title="Re-execute this request locally"
            >
              <RefreshCw class="w-3.5 h-3.5 text-emerald-400" />
              <span>Replay Request</span>
            </button>

            <!-- Copy as Markdown -->
            <button
              @click="copyMarkdown"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle hover:border-sky-400 transition-all"
              title="Copy complete request details as Markdown for sharing"
            >
              <component :is="copiedField === 'markdown' ? CheckCheck : FileText" class="w-3.5 h-3.5 text-sky-400" />
              <span>{{ copiedField === 'markdown' ? 'Markdown Copied!' : 'Copy Markdown' }}</span>
            </button>

            <!-- Master Expand/Collapse Toggle -->
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

        <!-- URL & Routing Banner -->
        <div class="mt-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="space-y-1.5 min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-sm font-mono font-bold text-text-primary break-all select-all tracking-tight">
                {{ request.url }}
              </span>
              <button
                @click="copyToClipboard(request.url, 'url')"
                class="p-1 text-text-muted hover:text-accent transition-colors flex-shrink-0"
                title="Copy full URL"
              >
                <component :is="copiedField === 'url' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Route and Execution tags -->
            <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary pt-0.5">
              <span v-if="request.route_name" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-elevated text-accent border border-border-subtle font-mono text-[11px]">
                <Tag class="w-3 h-3 text-accent" /> {{ request.route_name }}
              </span>
              <span v-if="request.controller_action" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-elevated text-text-secondary border border-border-subtle font-mono text-[11px]">
                <Code class="w-3 h-3 text-text-muted" /> {{ request.controller_action }}
              </span>
              <span v-if="request.ip_address" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-elevated text-text-muted border border-border-subtle font-mono text-[11px]">
                <Globe class="w-3 h-3" /> {{ request.ip_address }}
              </span>
              <span v-if="request.user_id" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-elevated text-text-muted border border-border-subtle font-mono text-[11px]">
                <User class="w-3 h-3 text-accent" /> User #{{ request.user_id }}
              </span>
            </div>
          </div>

          <!-- Timestamp details -->
          <div class="flex lg:flex-col items-end justify-between lg:justify-center gap-1 text-right text-xs flex-shrink-0">
            <span class="font-medium text-text-primary flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5 text-accent" />
              {{ formatDistanceToNow(new Date(request.created_at), { addSuffix: true }) }}
            </span>
            <span class="text-text-muted font-mono text-[11px]">
              {{ new Date(request.created_at).toISOString().replace('T', ' ').substring(0, 19) }} UTC
            </span>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════
           2. BENTO CARD: 4 Key Performance Indicators (KPIs)
           ═══════════════════════════════════════════════ -->
      <div id="section-kpis" class="space-y-3">
        <div class="flex items-center justify-between px-1">
          <h2 class="text-xs font-bold uppercase tracking-wider text-text-muted flex items-center gap-1.5">
            <Activity class="w-3.5 h-3.5 text-accent" />
            Performance & Health Indicators
          </h2>
          <button
            @click="toggleSection('kpis')"
            class="text-text-muted hover:text-text-primary text-xs flex items-center gap-1 transition-colors"
          >
            <span>{{ collapsed.kpis ? 'Show Metrics' : 'Hide' }}</span>
            <component :is="collapsed.kpis ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
          </button>
        </div>

        <div v-show="!collapsed.kpis" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- KPI 1: Latency -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Total Response Time</span>
              <Clock class="w-4 h-4 text-accent" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
                {{ request.duration_ms }}
              </span>
              <span class="text-xs font-mono text-text-muted">ms</span>
            </div>
            <div class="mt-2 flex items-center gap-1.5">
              <span
                :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                  request.duration_ms < 200
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'
                    : request.duration_ms < 500
                    ? 'bg-amber-500/10 text-amber-400 border-amber-500/30'
                    : 'bg-rose-500/10 text-rose-400 border-rose-500/30'
                ]"
              >
                {{ request.duration_ms < 200 ? 'Fast (<200ms)' : request.duration_ms < 500 ? 'Moderate (<500ms)' : 'Slow (>=500ms)' }}
              </span>
            </div>
          </div>

          <!-- KPI 2: Database Metrics -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Database SQL Metrics</span>
              <Database class="w-4 h-4 text-purple-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
                {{ request.query_count ?? 0 }}
              </span>
              <span class="text-xs font-mono text-text-muted">queries</span>
            </div>
            <div class="mt-2 text-[11px] text-text-secondary flex items-center justify-between font-mono">
              <span>Time: <strong class="text-purple-300">{{ request.query_time_ms ?? 0 }} ms</strong></span>
              <span v-if="request.duration_ms && request.query_time_ms" class="text-text-muted text-[10px]">
                ({{ Math.round((request.query_time_ms / request.duration_ms) * 100) }}% total)
              </span>
            </div>
          </div>

          <!-- KPI 3: Peak Memory -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-sky-400/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">Memory Peak Usage</span>
              <Cpu class="w-4 h-4 text-sky-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-text-primary">
                {{ formatMemory(request.memory_usage_kb) }}
              </span>
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-[10px] text-text-muted">
              <span class="w-2 h-2 rounded-full bg-sky-400 inline-block" />
              <span>PHP Worker Process</span>
            </div>
          </div>

          <!-- KPI 4: Egress Outgoing Requests -->
          <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-amber-400/40 transition-all">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold text-text-muted">External Egress Calls</span>
              <ExternalLink class="w-4 h-4 text-amber-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black font-mono tracking-tight text-amber-300">
                {{ waterfallData?.outgoing_requests_count ?? (request.outgoing_requests_count ?? 0) }}
              </span>
              <span class="text-xs font-mono text-text-muted">calls</span>
            </div>
            <div class="mt-2 text-[10px] text-text-muted flex items-center gap-1">
              <span>Third-party HTTP services</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════
           3. BENTO CARD: Exception & Call Stack Explorer (Hero Alert on Error)
           ═══════════════════════════════════════════════ -->
      <div
        v-if="request.status_code >= 400 || getExceptionInfo(request)"
        id="section-exception"
        class="glass-surface-elevated rounded-2xl border border-rose-500/30 overflow-hidden shadow-2xl relative"
      >
        <!-- Card Header -->
        <div class="p-5 bg-rose-500/10 border-b border-rose-500/20 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-rose-500/20 text-rose-400">
              <AlertCircle class="w-5 h-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span
                  v-if="getExceptionInfo(request)?.class"
                  class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40"
                >
                  {{ getExceptionInfo(request).class }}
                </span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-950/80 text-rose-200">
                  HTTP {{ request.status_code }} Server Crash
                </span>
              </div>
              <h3 class="text-base font-bold text-rose-200 mt-1 leading-snug">
                {{ getExceptionInfo(request)?.message || 'Application Error Encountered' }}
              </h3>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="getExceptionInfo(request)"
              @click="copyToClipboard(formatTraceForCopy(getExceptionInfo(request)), 'trace')"
              class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-rose-500/30 hover:border-rose-400 text-rose-300"
            >
              <component :is="copiedField === 'trace' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
              <span>{{ copiedField === 'trace' ? 'Copied Full Report!' : 'Copy Error Details' }}</span>
            </button>

            <button
              @click="toggleSection('exception')"
              class="p-2 rounded-xl bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors"
            >
              <component :is="collapsed.exception ? ChevronDown : ChevronUp" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Collapsible Body -->
        <div v-show="!collapsed.exception" class="p-5 space-y-4">
          <!-- File & Line location -->
          <div v-if="getExceptionInfo(request)?.file" class="flex flex-wrap items-center gap-2 text-xs font-mono bg-surface-elevated p-3 rounded-xl border border-border-subtle">
            <span class="text-text-muted font-sans font-semibold">Crash Origin:</span>
            <code class="text-rose-300 bg-surface-base px-2 py-0.5 rounded border border-rose-500/20 break-all select-all">
              {{ getExceptionInfo(request).file }}
            </code>
            <span class="text-rose-400 font-bold bg-rose-500/20 px-2 py-0.5 rounded border border-rose-500/30 whitespace-nowrap">
              Line {{ getExceptionInfo(request).line }}
            </span>
          </div>

          <!-- Stack Trace Explorer -->
          <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
              <div class="flex items-center gap-2">
                <h4 class="text-xs font-bold text-text-muted uppercase tracking-wider flex items-center gap-1.5">
                  <Layers class="w-3.5 h-3.5 text-accent" />
                  Stack Trace Frames
                </h4>
                <span
                  v-if="Array.isArray(getExceptionInfo(request)?.trace)"
                  class="text-xs font-mono px-2 py-0.5 rounded-full bg-surface-elevated text-text-muted border border-border-subtle"
                >
                  {{ filteredTraceFrames.length }} / {{ getExceptionInfo(request).trace.length }} frames
                </span>
              </div>

              <!-- Filter Controls: App Only & Search Trace -->
              <div class="flex flex-wrap items-center gap-2">
                <div class="relative">
                  <Search class="w-3.5 h-3.5 text-text-muted absolute left-2.5 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="searchTrace"
                    type="text"
                    placeholder="Search in trace frames..."
                    class="pl-8 pr-2.5 py-1 text-xs bg-surface-elevated border border-border-subtle rounded-lg text-text-primary focus:border-accent w-48"
                  />
                </div>

                <div
                  v-if="Array.isArray(getExceptionInfo(request)?.trace) && getExceptionInfo(request).trace.length > 0"
                  class="flex items-center gap-1 bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-xs"
                >
                  <button
                    type="button"
                    @click="appOnlyFrames = false"
                    :class="['px-2.5 py-1 rounded-md transition-colors', !appOnlyFrames ? 'bg-surface-raised text-text-primary font-semibold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                  >
                    All Frames
                  </button>
                  <button
                    type="button"
                    @click="appOnlyFrames = true"
                    :class="['px-2.5 py-1 rounded-md transition-colors', appOnlyFrames ? 'bg-surface-raised text-accent font-semibold shadow-xs' : 'text-text-muted hover:text-text-primary']"
                  >
                    App Only
                  </button>
                </div>
              </div>
            </div>

            <!-- Structured Trace Frames -->
            <div
              v-if="Array.isArray(getExceptionInfo(request)?.trace) && filteredTraceFrames.length > 0"
              class="space-y-2 max-h-96 overflow-y-auto pr-1"
            >
              <div
                v-for="(frame, idx) in filteredTraceFrames"
                :key="idx"
                class="p-3 rounded-xl border border-border-subtle bg-surface-elevated hover:border-border-default transition-all space-y-1 font-mono text-xs"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="text-text-muted text-[11px] font-bold">#{{ idx }}</span>
                    <span
                      v-if="isAppFrame(frame.file)"
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"
                    >
                      APP
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-surface-raised text-text-muted border border-border-subtle"
                    >
                      VENDOR
                    </span>
                    <span class="text-text-primary font-semibold truncate">
                      {{ (frame.class || '') + (frame.type || '') + (frame.function || 'closure') }}()
                    </span>
                  </div>
                  <span v-if="frame.line" class="text-rose-300 font-bold text-[11px] whitespace-nowrap">
                    Line {{ frame.line }}
                  </span>
                </div>

                <div v-if="frame.file" class="text-text-muted text-[11px] truncate flex items-center gap-1.5 pl-6" :title="frame.file">
                  <FileCode class="w-3.5 h-3.5 text-text-muted flex-shrink-0" />
                  <span class="truncate select-all">{{ frame.file }}</span>
                </div>
              </div>
            </div>

            <!-- Fallback string trace -->
            <div
              v-else-if="typeof getExceptionInfo(request)?.trace === 'string' && getExceptionInfo(request).trace.trim()"
              class="bg-surface-base rounded-xl p-4 overflow-x-auto border border-border-subtle max-h-96"
            >
              <pre class="text-xs text-rose-300 font-mono leading-relaxed whitespace-pre-wrap">{{ getExceptionInfo(request).trace }}</pre>
            </div>

            <!-- No frames placeholder -->
            <div
              v-else
              class="p-6 text-center text-xs text-text-muted bg-surface-elevated rounded-xl border border-border-subtle"
            >
              No stack trace frames matching current filters.
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════
           4. DUAL BENTO GRID: Request Anatomy vs Response Anatomy (Side-by-Side)
           ═══════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <!-- 4A. LEFT BENTO CARD: Incoming Request (Params & Body) -->
        <div id="section-request" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
          <!-- Card Header -->
          <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span :class="['px-2 py-0.5 text-xs font-bold rounded uppercase border font-mono', methodColor(request.method)]">
                {{ request.method }}
              </span>
              <h3 class="text-sm font-bold text-text-primary">Incoming Request Payload</h3>
              <span v-if="request.request_body" class="text-[10px] px-2 py-0.5 rounded-full bg-surface-elevated text-text-muted border border-border-subtle font-mono">
                {{ formatBytes(getPayloadSize(request.request_body)) }}
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <!-- Raw vs Formatted Mode Switch -->
              <div v-if="request.request_body" class="flex items-center bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-[11px]">
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

              <!-- Copy Request Body -->
              <button
                v-if="request.request_body"
                @click="copyToClipboard(request.request_body, 'req_body')"
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

          <!-- Card Content -->
          <div v-show="!collapsed.request" class="p-4 space-y-4">
            <!-- URL Query Parameters Sub-panel -->
            <div v-if="queryParams.length > 0" class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider flex items-center gap-1.5">
                  <Code class="w-3.5 h-3.5 text-accent" />
                  Query Parameters ({{ queryParams.length }})
                </h4>
                <div v-if="queryParams.length > 3" class="relative">
                  <Search class="w-3 h-3 text-text-muted absolute left-2 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="searchQueryParams"
                    type="text"
                    placeholder="Filter query params..."
                    class="pl-6 pr-2 py-0.5 text-[11px] bg-surface-elevated border border-border-subtle rounded-md text-text-primary focus:border-accent w-40"
                  />
                </div>
              </div>

              <div class="bg-surface-elevated rounded-xl border border-border-subtle overflow-hidden max-h-48 overflow-y-auto">
                <table class="w-full text-left text-xs">
                  <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase text-[10px]">
                    <tr>
                      <th class="px-3 py-1.5 font-medium">Key</th>
                      <th class="px-3 py-1.5 font-medium">Value</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-border-subtle font-mono">
                    <tr v-for="p in filteredQueryParams" :key="p.key" class="hover:bg-surface-hover">
                      <td class="px-3 py-1.5 text-accent font-semibold">{{ p.key }}</td>
                      <td class="px-3 py-1.5 text-text-primary break-all">{{ p.val }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Request Body Container -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Body Content
                </h4>
                <!-- In-Payload Search -->
                <div v-if="request.request_body" class="relative">
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
                <template v-if="request.request_body">
                  <pre
                    v-if="requestBodyViewMode === 'formatted'"
                    class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap"
                  >{{ formatJson(request.request_body) }}</pre>
                  <pre
                    v-else
                    class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap break-all"
                  >{{ getRawString(request.request_body) }}</pre>
                </template>
                <div v-else class="text-xs text-text-muted italic flex items-center justify-center h-28">
                  No request body submitted with this request (e.g. GET/HEAD).
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4B. RIGHT BENTO CARD: Response Output (Body & Metrics) -->
        <div id="section-response" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
          <!-- Card Header -->
          <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span :class="['px-2 py-0.5 text-xs font-bold rounded border font-mono', statusColor(request.status_code)]">
                {{ statusText(request.status_code) }}
              </span>
              <h3 class="text-sm font-bold text-text-primary">Response Output</h3>
              <span v-if="request.response_body" class="text-[10px] px-2 py-0.5 rounded-full bg-surface-elevated text-text-muted border border-border-subtle font-mono">
                {{ formatBytes(getPayloadSize(request.response_body)) }}
              </span>
            </div>

            <div class="flex items-center gap-1.5">
              <!-- Raw vs Formatted Mode Switch -->
              <div v-if="request.response_body" class="flex items-center bg-surface-elevated p-0.5 rounded-lg border border-border-subtle text-[11px]">
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

              <!-- Download JSON button -->
              <button
                v-if="request.response_body"
                @click="downloadResponseJson"
                class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-accent transition-colors border border-border-subtle"
                title="Download Response as JSON"
              >
                <Download class="w-3.5 h-3.5" />
              </button>

              <!-- Copy Response Body -->
              <button
                v-if="request.response_body"
                @click="copyToClipboard(request.response_body, 'res_body')"
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

          <!-- Card Content -->
          <div v-show="!collapsed.response" class="p-4 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Response Body
                </h4>
                <!-- In-Response Search -->
                <div v-if="request.response_body" class="relative">
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
                <template v-if="request.response_body">
                  <pre
                    v-if="responseBodyViewMode === 'formatted'"
                    class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap"
                  >{{ formatJson(request.response_body) }}</pre>
                  <pre
                    v-else
                    class="text-xs text-text-secondary font-mono leading-relaxed whitespace-pre-wrap break-all"
                  >{{ getRawString(request.response_body) }}</pre>
                </template>
                <div v-else class="text-xs text-text-muted italic flex items-center justify-center h-28">
                  No response body captured.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════
           5. BENTO CARD: Execution Waterfall & Database Distribution
           ═══════════════════════════════════════════════ -->
      <div id="section-waterfall" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
        <div class="p-4 bg-surface-raised border-b border-border-subtle flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Clock class="w-4 h-4 text-accent" />
            <h3 class="text-sm font-bold text-text-primary">Execution Timeline & Outgoing Waterfall</h3>
            <span class="text-xs font-mono text-text-muted">
              {{ request.duration_ms }} ms total
            </span>
          </div>

          <button
            @click="toggleSection('waterfall')"
            class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
          >
            <component :is="collapsed.waterfall ? ChevronDown : ChevronUp" class="w-3.5 h-3.5" />
          </button>
        </div>

        <div v-show="!collapsed.waterfall" class="p-5 space-y-6">
          <!-- Execution Breakdown Bar -->
          <div class="space-y-2">
            <div class="flex items-center justify-between text-xs text-text-muted">
              <span>Time Distribution Breakdown</span>
              <span class="font-mono">SQL: {{ request.query_time_ms ?? 0 }}ms | App: {{ Math.max(0, request.duration_ms - (request.query_time_ms || 0)) }}ms</span>
            </div>
            <div class="h-6 w-full rounded-xl bg-surface-elevated overflow-hidden flex border border-border-subtle p-0.5">
              <!-- Database bar -->
              <div
                v-if="request.query_time_ms && request.duration_ms"
                :style="{ width: Math.min(100, (request.query_time_ms / request.duration_ms) * 100) + '%' }"
                class="bg-purple-500 rounded-lg h-full flex items-center justify-center text-[10px] font-bold text-white transition-all shadow-xs"
                :title="'Database SQL: ' + request.query_time_ms + 'ms (' + Math.round((request.query_time_ms / request.duration_ms) * 100) + '%)'"
              >
                <span v-if="(request.query_time_ms / request.duration_ms) > 0.15">SQL {{ Math.round((request.query_time_ms / request.duration_ms) * 100) }}%</span>
              </div>
              <!-- App Framework overhead bar -->
              <div
                :style="{ width: Math.max(0, 100 - Math.min(100, ((request.query_time_ms || 0) / (request.duration_ms || 1)) * 100)) + '%' }"
                class="bg-accent/80 rounded-lg h-full flex items-center justify-center text-[10px] font-bold text-text-inverted transition-all shadow-xs"
                :title="'Application Framework Overhead: ' + Math.max(0, request.duration_ms - (request.query_time_ms || 0)) + 'ms'"
              >
                <span>Application Processing</span>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs text-text-muted pt-1">
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-purple-500 inline-block" /> SQL Queries: {{ request.query_time_ms ?? 0 }} ms ({{ request.query_count ?? 0 }} queries)</span>
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-accent/80 inline-block" /> Application & Framework: {{ Math.max(0, request.duration_ms - (request.query_time_ms || 0)) }} ms</span>
            </div>
          </div>

          <!-- Outgoing Egress Calls -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider flex items-center gap-1.5">
                <ExternalLink class="w-3.5 h-3.5 text-accent" />
                Third-Party Outgoing HTTP Calls (Egress)
              </h4>
              <span class="text-xs font-mono text-text-muted">{{ waterfallData?.outgoing_requests_count ?? 0 }} calls linked</span>
            </div>

            <div v-if="waterfallLoading" class="p-6 text-center text-xs text-text-muted">Loading waterfall calls...</div>
            <div v-else-if="!waterfallData?.outgoing_requests || waterfallData.outgoing_requests.length === 0" class="p-6 text-center text-xs text-text-muted bg-surface-elevated rounded-xl border border-border-subtle">
              No outgoing third-party HTTP requests (e.g. Stripe, OpenAI, external APIs) were made during this request execution.
            </div>
            <div v-else class="overflow-x-auto bg-surface-elevated rounded-xl border border-border-subtle">
              <table class="w-full text-left text-xs">
                <thead class="bg-surface-raised border-b border-border-subtle text-text-muted uppercase text-[10px]">
                  <tr>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Method</th>
                    <th class="px-4 py-2.5">Third-Party URL</th>
                    <th class="px-4 py-2.5">Duration</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle font-mono">
                  <tr v-for="call in waterfallData.outgoing_requests" :key="call.id" class="hover:bg-surface-hover">
                    <td class="px-4 py-2.5">
                      <span :class="['px-2 py-0.5 rounded text-[10px] font-bold border', statusColor(call.status_code)]">
                        {{ call.status_code === 0 ? 'FAIL' : call.status_code }}
                      </span>
                    </td>
                    <td class="px-4 py-2.5 font-bold text-accent">{{ call.method }}</td>
                    <td class="px-4 py-2.5 text-text-secondary truncate max-w-md" :title="call.url">
                      <strong class="text-text-primary">{{ call.domain }}</strong> — {{ call.url }}
                    </td>
                    <td class="px-4 py-2.5 text-text-primary font-bold">{{ call.duration_ms }} ms</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════
           6. BENTO CARD: Headers & Architecture Context
           ═══════════════════════════════════════════════ -->
      <div id="section-headers" class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden">
        <div class="p-4 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <Layers class="w-4 h-4 text-accent" />
            <h3 class="text-sm font-bold text-text-primary">HTTP Headers & Architecture Context</h3>
          </div>

          <div class="flex items-center gap-2">
            <!-- Search across headers -->
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
          <!-- Request vs Response Headers Side by Side -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Request Headers -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Request Headers ({{ Object.keys(filteredRequestHeaders).length }})
                </h4>
                <button
                  v-if="request.request_headers"
                  @click="copyToClipboard(request.request_headers, 'req_headers')"
                  class="flex items-center gap-1 text-xs text-text-muted hover:text-accent transition-colors"
                >
                  <component :is="copiedField === 'req_headers' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
                  <span>{{ copiedField === 'req_headers' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
              <div class="bg-surface-elevated rounded-xl p-3.5 space-y-2 overflow-x-auto border border-border-subtle max-h-72 overflow-y-auto">
                <template v-if="Object.keys(filteredRequestHeaders).length">
                  <div
                    v-for="(value, key) in filteredRequestHeaders"
                    :key="key"
                    class="text-xs font-mono flex items-start gap-2 border-b border-border-subtle/50 pb-1.5 last:border-0 last:pb-0"
                  >
                    <span class="font-bold text-accent min-w-[130px] break-all">{{ key }}:</span>
                    <span class="text-text-secondary break-all flex-1 select-all">{{ Array.isArray(value) ? value.join(', ') : value }}</span>
                  </div>
                </template>
                <p v-else class="text-xs text-text-muted italic py-2">No request headers found matching filter.</p>
              </div>
            </div>

            <!-- Response Headers -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
                  Response Headers ({{ Object.keys(filteredResponseHeaders).length }})
                </h4>
                <button
                  v-if="request.response_headers"
                  @click="copyToClipboard(request.response_headers, 'res_headers')"
                  class="flex items-center gap-1 text-xs text-text-muted hover:text-accent transition-colors"
                >
                  <component :is="copiedField === 'res_headers' ? CheckCheck : Copy" class="w-3.5 h-3.5" />
                  <span>{{ copiedField === 'res_headers' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
              <div class="bg-surface-elevated rounded-xl p-3.5 space-y-2 overflow-x-auto border border-border-subtle max-h-72 overflow-y-auto">
                <template v-if="Object.keys(filteredResponseHeaders).length">
                  <div
                    v-for="(value, key) in filteredResponseHeaders"
                    :key="key"
                    class="text-xs font-mono flex items-start gap-2 border-b border-border-subtle/50 pb-1.5 last:border-0 last:pb-0"
                  >
                    <span class="font-bold text-sky-400 min-w-[130px] break-all">{{ key }}:</span>
                    <span class="text-text-secondary break-all flex-1 select-all">{{ Array.isArray(value) ? value.join(', ') : value }}</span>
                  </div>
                </template>
                <p v-else class="text-xs text-text-muted italic py-2">No response headers found matching filter.</p>
              </div>
            </div>
          </div>

          <!-- Architecture & System Context Breakdown -->
          <div class="space-y-2 pt-2 border-t border-border-subtle">
            <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">
              Internal Architecture Context
            </h4>
            <div class="bg-surface-elevated rounded-xl border border-border-subtle overflow-hidden">
              <dl class="divide-y divide-border-subtle text-xs">
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Route Identifier</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono font-bold text-accent">{{ request.route_name || '— (Unnamed route)' }}</dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Controller Action</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-primary">{{ request.controller_action || '— (Closure)' }}</dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Origin Client IP</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-primary">{{ request.ip_address || '—' }}</dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">User Authentication</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-primary">{{ request.user_id ? 'Authenticated User #' + request.user_id : 'Guest (Unauthenticated)' }}</dd>
                </div>
                <div class="px-4 py-2.5 sm:grid sm:grid-cols-3 sm:gap-4 hover:bg-surface-hover">
                  <dt class="font-semibold text-text-muted">Unique Log UUID</dt>
                  <dd class="mt-1 sm:mt-0 sm:col-span-2 font-mono text-text-muted select-all">{{ request.id }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         7. MINI-DOCK FLOTTANTE: Quick Navigation Pill Dock
         ═══════════════════════════════════════════════ -->
    <div v-if="request" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
      <div class="flex items-center gap-1.5 p-1.5 rounded-full bg-surface-elevated/90 backdrop-blur-xl border border-border-default/80 shadow-[0_10px_35px_rgba(0,0,0,0.6)]">
        <!-- Overview / KPIs -->
        <button
          type="button"
          @click="scrollToSection('section-kpis')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
        >
          <Activity class="w-3.5 h-3.5 text-accent" />
          <span class="hidden sm:inline">Metrics</span>
        </button>

        <!-- Exception (if error, glow badge!) -->
        <button
          v-if="request.status_code >= 400 || getExceptionInfo(request)"
          type="button"
          @click="scrollToSection('section-exception')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold text-rose-300 bg-rose-500/20 border border-rose-500/40 hover:bg-rose-500/30 transition-all animate-pulse"
        >
          <AlertCircle class="w-3.5 h-3.5 text-rose-400" />
          <span>Error</span>
        </button>

        <!-- Request -->
        <button
          type="button"
          @click="scrollToSection('section-request')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
        >
          <Code class="w-3.5 h-3.5 text-sky-400" />
          <span class="hidden sm:inline">Request</span>
        </button>

        <!-- Response -->
        <button
          type="button"
          @click="scrollToSection('section-response')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
        >
          <Terminal class="w-3.5 h-3.5 text-emerald-400" />
          <span class="hidden sm:inline">Response</span>
        </button>

        <!-- Waterfall -->
        <button
          type="button"
          @click="scrollToSection('section-waterfall')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-text-secondary hover:text-text-primary hover:bg-surface-hover transition-all"
        >
          <Clock class="w-3.5 h-3.5 text-purple-400" />
          <span class="hidden md:inline">Waterfall</span>
        </button>

        <!-- Headers -->
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
          title="Copy Markdown"
        >
          <component :is="copiedField === 'markdown' ? CheckCheck : FileText" class="w-3.5 h-3.5" />
        </button>

        <!-- Replay Shortcut -->
        <button
          type="button"
          @click="handleReplay"
          class="p-1.5 rounded-full text-emerald-400 hover:bg-emerald-500/20 transition-all"
          title="Replay Request"
        >
          <RefreshCw class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>



    <!-- Replay Modal -->
    <ReplayModal
      :is-open="isReplayModalOpen"
      :loading="replayLoading"
      :response="replayResponse"
      :error="replayError"
      @close="isReplayModalOpen = false"
    />
  </div>
</template>
