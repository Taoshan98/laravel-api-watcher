<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  GitCompare,
  Search,
  AlertCircle,
  CheckCircle2,
  AlertTriangle,
  Code,
  Copy,
  CheckCheck,
  ExternalLink,
  ChevronDown,
  ChevronUp,
  Maximize2,
  Minimize2,
  RefreshCw,
  X,
  Layers,
  Activity,
  FileCode
} from 'lucide-vue-next';

const router = useRouter();

const searchUrl = ref('');
const drifts = ref([]);
const loading = ref(false);
const copiedField = ref(null);
const searchField = ref('');

const collapsed = ref({});

const toggleRoute = (routeKey) => {
  collapsed.value[routeKey] = !collapsed.value[routeKey];
};

const allExpanded = computed(() => {
  return drifts.value.length > 0 && drifts.value.every((_, i) => !collapsed.value[i]);
});

const toggleAll = () => {
  const target = allExpanded.value;
  drifts.value.forEach((_, i) => {
    collapsed.value[i] = target;
  });
};

const checkDrifts = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/api-watcher/api/schema-drifts?url=${encodeURIComponent(searchUrl.value.trim())}`).then(r => r.json());
    drifts.value = res;
    // default expand all
    drifts.value.forEach((_, i) => {
      collapsed.value[i] = false;
    });
  } catch (err) {
    console.error('Failed to fetch schema drifts', err);
  } finally {
    loading.value = false;
  }
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

const filterSchema = (schemaObj) => {
  if (!searchField.value.trim() || !schemaObj) return schemaObj;
  const q = searchField.value.trim().toLowerCase();

  const filterRecursive = (obj) => {
    if (typeof obj !== 'object' || obj === null) return obj;
    const result = {};
    for (const [k, v] of Object.entries(obj)) {
      if (k.toLowerCase().includes(q) || String(v).toLowerCase().includes(q)) {
        result[k] = v;
      } else if (typeof v === 'object' && v !== null) {
        const nested = filterRecursive(v);
        if (Object.keys(nested).length > 0) {
          result[k] = nested;
        }
      }
    }
    return result;
  };

  return filterRecursive(schemaObj);
};

const totalVariations = computed(() => {
  return drifts.value.reduce((acc, d) => acc + (d.variations_count || 0), 0);
});

onMounted(() => {
  checkDrifts();
});
</script>

<template>
  <div class="space-y-6 pb-16 animate-[fade-in_0.3s_ease]">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
          <GitCompare class="w-6 h-6 text-accent" /> Response Schema Drift Detector
        </h1>
        <p class="text-sm text-text-secondary mt-0.5">
          Structural JSON type-mapping analysis to detect breaking changes and payload signature alterations
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="drifts.length > 0"
          @click="toggleAll"
          class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
          :title="allExpanded ? 'Collapse all Bento cards' : 'Expand all Bento cards'"
        >
          <component :is="allExpanded ? Minimize2 : Maximize2" class="w-3.5 h-3.5 text-text-muted" />
          <span class="hidden sm:inline">{{ allExpanded ? 'Collapse All' : 'Expand All' }}</span>
        </button>

        <button
          @click="checkDrifts"
          class="btn btn-secondary text-xs flex items-center gap-1.5 px-3 py-2 rounded-xl"
          title="Run Schema Drift Analysis"
        >
          <RefreshCw :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']" />
          <span>Re-Analyze</span>
        </button>
      </div>
    </div>

    <!-- Master Bento KPI Strip (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- KPI 1: Routes with Drift -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-accent/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Drifted Endpoints</span>
          <div class="p-2 rounded-xl bg-accent-soft text-accent">
            <GitCompare class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span :class="['text-2xl font-black font-mono tracking-tight', drifts.length > 0 ? 'text-rose-400' : 'text-emerald-400']">
            {{ drifts.length }}
          </span>
          <span class="text-xs font-mono text-text-muted">routes</span>
        </div>
        <div class="mt-2 flex items-center gap-1.5">
          <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full border', drifts.length > 0 ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30']">
            {{ drifts.length > 0 ? 'Schema Drifts Present' : 'All Schemas Stable' }}
          </span>
        </div>
      </div>

      <!-- KPI 2: Total Variations -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-amber-400/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Total Schema Variations</span>
          <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
            <Layers class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-amber-300">
            {{ totalVariations }}
          </span>
          <span class="text-xs font-mono text-text-muted">signatures</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Unique response JSON signatures
        </div>
      </div>

      <!-- KPI 3: Stability Rating -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-emerald-400/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">API Schema Health</span>
          <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span :class="['text-2xl font-black font-mono tracking-tight', drifts.length === 0 ? 'text-emerald-400' : 'text-amber-400']">
            {{ drifts.length === 0 ? '100%' : 'Needs Review' }}
          </span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Structural consistency score
        </div>
      </div>

      <!-- KPI 4: Evaluation Scope -->
      <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle relative overflow-hidden group hover:border-purple-500/40 transition-all">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-text-muted">Analysis Sample Depth</span>
          <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
            <Activity class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-2 flex items-baseline gap-2">
          <span class="text-2xl font-black font-mono tracking-tight text-purple-300">
            50
          </span>
          <span class="text-xs font-mono text-text-muted">samples / route</span>
        </div>
        <div class="mt-2 text-[10px] text-text-muted">
          Recent 200 OK responses examined
        </div>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="glass-surface-elevated p-4 rounded-2xl border border-border-subtle flex flex-col sm:flex-row items-center gap-3">
      <div class="relative flex-1 w-full">
        <Search class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input
          v-model="searchUrl"
          @keyup.enter="checkDrifts"
          type="text"
          placeholder="Filter by API URL or route prefix (e.g. /api/users, /api/v1)..."
          class="w-full pl-10 pr-8 py-2 text-xs bg-surface-elevated border border-border-default rounded-xl text-text-primary placeholder:text-text-muted focus:outline-none focus:border-accent transition-colors"
        />
        <button
          v-if="searchUrl"
          @click="searchUrl = ''; checkDrifts()"
          type="button"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <button
        @click="checkDrifts"
        class="btn btn-primary text-xs whitespace-nowrap w-full sm:w-auto px-4 py-2 rounded-xl"
      >
        Scan For Drifts
      </button>
    </div>

    <!-- Results Section -->
    <div v-if="loading" class="p-12 text-center text-text-muted space-y-2">
      <RefreshCw class="w-6 h-6 animate-spin mx-auto text-accent" />
      <p class="text-xs font-medium">Extracting and comparing JSON response structures...</p>
    </div>

    <!-- Zero Drifts State -->
    <div v-else-if="drifts.length === 0" class="glass-surface-elevated p-12 text-center rounded-2xl border border-border-subtle space-y-3">
      <CheckCircle2 class="w-12 h-12 text-emerald-400 mx-auto mb-2" />
      <h2 class="text-base font-bold text-text-primary">All Response Schemas Are Consistent</h2>
      <p class="text-xs text-text-secondary max-w-lg mx-auto leading-relaxed">
        No schema drift detected. All sampled 200 OK responses for {{ searchUrl ? `endpoint "${searchUrl}"` : 'your API endpoints' }} share uniform property names and data types.
      </p>
    </div>

    <!-- Drifts Detected List: Bento Cards -->
    <div v-else class="space-y-6">
      <div
        v-for="(drift, idx) in drifts"
        :key="idx"
        class="glass-surface-elevated rounded-2xl border border-border-default shadow-xl overflow-hidden"
      >
        <!-- Card Header -->
        <div class="p-5 bg-surface-raised border-b border-border-subtle flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
              <AlertTriangle class="w-4 h-4" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono text-sm font-bold text-accent">{{ drift.route || 'All Sampled Endpoints' }}</span>
                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30">
                  {{ drift.variations_count }} Schema Variations Detected
                </span>
              </div>
              <p class="text-xs text-text-muted mt-0.5">
                Breaking type changes or polymorphic JSON keys identified across historical responses
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <!-- In-card Schema Field Filter -->
            <div class="relative">
              <Search class="w-3.5 h-3.5 text-text-muted absolute left-2.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="searchField"
                type="text"
                placeholder="Search keys..."
                class="pl-8 pr-2.5 py-1 text-xs bg-surface-elevated border border-border-subtle rounded-lg text-text-primary focus:border-accent w-36"
              />
            </div>

            <button
              @click="toggleRoute(idx)"
              class="p-1.5 rounded-lg bg-surface-elevated hover:bg-surface-hover text-text-muted hover:text-text-primary transition-colors border border-border-subtle"
            >
              <component :is="collapsed[idx] ? ChevronDown : ChevronUp" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Variations Comparison Bento Grid -->
        <div v-show="!collapsed[idx]" class="p-5 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(v, vIdx) in drift.variations"
              :key="vIdx"
              class="p-4 rounded-xl bg-surface-elevated border border-border-subtle space-y-3 flex flex-col justify-between"
            >
              <!-- Variation Meta Bar -->
              <div class="flex items-center justify-between border-b border-border-subtle pb-2.5">
                <div class="flex items-center gap-1.5">
                  <span :class="['px-2 py-0.5 rounded text-[11px] font-bold', vIdx === 0 ? 'bg-accent-soft text-accent' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20']">
                    {{ vIdx === 0 ? 'Baseline Signature' : `Variation #${vIdx + 1}` }}
                  </span>
                </div>

                <div class="flex items-center gap-1.5">
                  <!-- Copy Schema Button -->
                  <button
                    @click="copyToClipboard(v.schema, `schema_${idx}_${vIdx}`)"
                    class="p-1 rounded text-text-muted hover:text-accent transition-colors"
                    title="Copy schema signature"
                  >
                    <component :is="copiedField === `schema_${idx}_${vIdx}` ? CheckCheck : Copy" class="w-3.5 h-3.5" />
                  </button>

                  <!-- Jump to Sample Request -->
                  <router-link
                    v-if="v.sample_request_id"
                    :to="'/requests/' + v.sample_request_id"
                    class="p-1 rounded text-text-muted hover:text-accent transition-colors"
                    title="Inspect sample request"
                  >
                    <ExternalLink class="w-3.5 h-3.5" />
                  </router-link>
                </div>
              </div>

              <!-- Timestamp -->
              <div class="text-[11px] text-text-muted font-mono flex items-center justify-between">
                <span>First observed:</span>
                <span class="text-text-primary">{{ new Date(v.first_seen).toISOString().replace('T', ' ').substring(0, 19) }}</span>
              </div>

              <!-- Schema Structure JSON Preview -->
              <div class="bg-surface-base rounded-xl p-3 border border-border-subtle overflow-x-auto max-h-64 font-mono text-[11px] leading-relaxed">
                <pre class="text-emerald-400">{{ JSON.stringify(filterSchema(v.schema), null, 2) }}</pre>
              </div>
            </div>
          </div>

          <!-- Explanation Callout -->
          <div class="p-3.5 rounded-xl bg-surface-base border border-border-subtle text-xs text-text-secondary flex items-start gap-2.5">
            <Code class="w-4 h-4 text-accent flex-shrink-0 mt-0.5" />
            <div>
              <span class="font-bold text-text-primary">What does this drift indicate?</span>
              <p class="text-[11px] text-text-muted mt-0.5 leading-relaxed">
                The schema detector identified structural variations in the response keys or data types returned for this endpoint across different requests. This usually signals inconsistent serialization between controller methods, optional fields without defaults, or unversioned API changes that may break frontend clients or mobile apps.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
