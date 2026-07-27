<script setup>
import { ref, onMounted } from 'vue';
import { GitCompare, Search, AlertCircle } from 'lucide-vue-next';

const searchUrl = ref('');
const drifts = ref([]);
const loading = ref(false);

const checkDrifts = async () => {
  loading.value = true;
  try {
    const res = await fetch(`/api-watcher/api/schema-drifts?url=${encodeURIComponent(searchUrl.value)}`).then(r => r.json());
    drifts.value = res;
  } catch (err) {
    console.error('Failed to fetch schema drifts', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  checkDrifts();
});
</script>

<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
          <GitCompare class="w-6 h-6 text-accent" /> Response Schema Drift Detector
        </h1>
        <p class="text-sm text-text-secondary">Detect breaking changes or structural JSON alterations in your API responses over time</p>
      </div>
    </div>

    <!-- Search input -->
    <div class="card p-4 flex gap-3">
      <div class="relative flex-1">
        <input 
          v-model="searchUrl" 
          @keyup.enter="checkDrifts"
          type="text" 
          placeholder="Filter by API URL or route (e.g. /api/users)..." 
          class="input pl-9 text-xs"
        />
        <Search class="w-4 h-4 text-text-muted absolute left-3 top-2.5" />
      </div>
      <button @click="checkDrifts" class="btn btn-primary text-xs">Detect Schema Drifts</button>
    </div>

    <!-- Results -->
    <div v-if="loading" class="p-8 text-center text-xs text-text-muted">Analyzing JSON response structures...</div>
    <div v-else-if="drifts.length === 0" class="card p-8 text-center text-xs text-text-muted">
      <AlertCircle class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
      <div class="font-bold text-text-primary">No Schema Drifts Detected</div>
      <div class="text-text-secondary mt-1">All recorded API responses for this route have consistent JSON key structures.</div>
    </div>
    <div v-else class="space-y-4">
      <div v-for="(drift, idx) in drifts" :key="idx" class="card p-5 space-y-3">
        <div class="flex items-center justify-between border-b border-border-subtle pb-3">
          <span class="font-mono text-sm font-bold text-accent">{{ drift.route }}</span>
          <span class="px-2 py-0.5 text-xs font-bold rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
            {{ drift.variations_count }} Schema Variations Detected
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(v, vIdx) in drift.variations" :key="vIdx" class="p-4 rounded-lg bg-surface-hover border border-border-subtle">
            <div class="text-xs font-bold text-text-secondary mb-2">Variation #{{ vIdx + 1 }} (First seen: {{ new Date(v.first_seen).toLocaleString() }})</div>
            <pre class="bg-surface-raised p-3 rounded text-[11px] font-mono text-emerald-400 overflow-x-auto max-h-48">{{ JSON.stringify(v.schema, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
