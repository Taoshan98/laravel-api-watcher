<script setup>
import { computed } from 'vue';
import { X, Loader2 } from 'lucide-vue-next';

const props = defineProps({ isOpen: Boolean, loading: Boolean, response: Object, error: String });
const emit = defineEmits(['close']);

const statusColor = computed(() => {
  if (!props.response) return '';
  const c = props.response.status;
  if (c >= 500) return 'bg-danger-soft text-danger';
  if (c >= 400) return 'bg-warning-soft text-warning';
  if (c >= 300) return 'bg-info-soft text-info';
  return 'bg-success-soft text-success';
});
</script>

<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')" />

        <div class="relative w-full max-w-lg glass-surface-elevated overflow-hidden animate-[slide-up_0.25s_ease]">
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-border-subtle">
            <h3 class="text-base font-semibold text-text-primary">Replay Result</h3>
            <button @click="emit('close')" class="p-1.5 rounded-lg hover:bg-surface-hover transition-colors text-text-muted hover:text-text-primary">
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Body -->
          <div class="px-5 py-5">
            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-10">
              <Loader2 class="w-8 h-8 text-accent animate-spin" />
            </div>

            <!-- Error -->
            <div v-else-if="error" class="p-4 rounded-xl bg-danger-soft border border-danger/20">
              <p class="text-sm text-danger">{{ error }}</p>
            </div>

            <!-- Response -->
            <div v-else-if="response" class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Status</span>
                <span :class="[statusColor, 'badge text-xs']">{{ response.status }} {{ response.statusText }}</span>
              </div>

              <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block mb-2">Body</span>
                <div class="bg-surface-elevated rounded-xl p-3 overflow-auto max-h-60">
                  <pre class="text-xs text-text-secondary font-mono whitespace-pre-wrap">{{ typeof response.data === 'object' ? JSON.stringify(response.data, null, 2) : response.data }}</pre>
                </div>
              </div>

              <div>
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider block mb-2">Headers</span>
                <div class="bg-surface-elevated rounded-xl p-3 overflow-auto max-h-40">
                  <pre class="text-xs text-text-secondary font-mono whitespace-pre-wrap">{{ JSON.stringify(response.headers, null, 2) }}</pre>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-5 py-4 border-t border-border-subtle flex justify-end">
            <button @click="emit('close')" class="px-4 py-2 text-sm font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover rounded-[10px] transition-all">
              Close
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
