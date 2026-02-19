<template>
  <div class="space-y-6 animate-[fade-in_0.3s_ease]">
    <!-- Toast -->
    <transition name="slide-up">
      <div v-if="toast" :class="['fixed top-5 right-5 z-50 flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-medium shadow-lg', toast.type === 'error' ? 'bg-danger text-white' : 'bg-success text-text-inverted']">
        {{ toast.message }}
      </div>
    </transition>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-text-primary">API Keys</h1>
        <p class="text-sm text-text-muted mt-0.5">Manage access keys for the Public API</p>
      </div>
      <button
        @click="showCreateModal = true"
        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium bg-accent text-text-inverted rounded-[10px] hover:bg-accent-hover transition-all"
      >
        <Plus class="w-4 h-4" /> Create Key
      </button>
    </div>

    <!-- Keys List -->
    <div class="glass-surface overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-text-muted text-sm">Loading keys...</div>

      <div v-else-if="keys.length === 0" class="p-12 text-center">
        <Key class="w-10 h-10 mx-auto text-text-muted opacity-40 mb-3" />
        <h3 class="text-sm font-semibold text-text-primary mb-1">No API keys</h3>
        <p class="text-xs text-text-muted">Create a key to get started.</p>
      </div>

      <div v-else class="divide-y divide-border-subtle">
        <div
          v-for="key in keys"
          :key="key.id"
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 hover:bg-surface-hover transition-colors"
        >
          <div>
            <p class="text-sm font-semibold text-text-primary">{{ key.name }}</p>
            <div class="flex items-center gap-3 mt-1 text-xs text-text-muted">
              <span>Created {{ formatDate(key.created_at) }}</span>
              <span>•</span>
              <span>Last used {{ key.last_used_at ? formatDate(key.last_used_at) : 'Never' }}</span>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <button @click="openEditModal(key)" class="px-3 py-1.5 text-xs font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover rounded-lg transition-all">
              Edit
            </button>
            <button @click="regenerateKey(key)" class="px-3 py-1.5 text-xs font-medium glass-surface text-accent hover:bg-accent-soft rounded-lg transition-all">
              Regenerate
            </button>
            <button @click="deleteKey(key.id)" class="px-3 py-1.5 text-xs font-medium glass-surface text-danger hover:bg-danger-soft rounded-lg transition-all">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
          <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal" />

          <div class="relative w-full max-w-md glass-surface-elevated overflow-hidden animate-[slide-up_0.25s_ease]">
            <div class="px-5 py-4 border-b border-border-subtle">
              <h3 class="text-base font-semibold text-text-primary">
                {{ isRegenerating ? 'Regenerate Key' : (isEditing ? 'Edit Key' : 'Create New Key') }}
              </h3>
            </div>

            <div class="px-5 py-5">
              <div v-if="!newToken">
                <p v-if="isRegenerating" class="text-sm text-text-secondary mb-4">
                  Regenerate key for <span class="font-bold text-text-primary">{{ newKeyName }}</span>?
                  <br><span class="text-danger text-xs font-semibold">Current key will stop working immediately.</span>
                </p>

                <div v-if="!isRegenerating">
                  <label class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-2 block">Key Name</label>
                  <input type="text" v-model="newKeyName" placeholder="e.g. Mobile App" class="w-full" />
                </div>
              </div>

              <div v-else class="p-4 rounded-xl bg-success-soft border border-success/20">
                <p class="text-sm text-success font-medium mb-1">{{ isRegenerating ? 'Key Regenerated!' : 'Key Created!' }}</p>
                <p class="text-xs text-text-muted mb-3">Copy your API key now — you won't see it again.</p>
                <div class="flex items-center gap-2">
                  <code class="flex-1 bg-surface-base p-2.5 rounded-lg border border-border-subtle text-xs font-mono break-all text-text-primary">{{ newToken }}</code>
                  <button @click="copyToken" class="px-3 py-2 text-xs font-medium bg-accent text-text-inverted rounded-lg hover:bg-accent-hover transition-all flex-shrink-0">
                    Copy
                  </button>
                </div>
              </div>
            </div>

            <div class="px-5 py-4 border-t border-border-subtle flex gap-3 justify-end">
              <button @click="closeModal" class="px-4 py-2 text-sm font-medium glass-surface text-text-secondary hover:text-text-primary hover:bg-surface-hover rounded-[10px] transition-all">
                Close
              </button>
              <button
                v-if="!newToken"
                @click="saveKey"
                :class="[
                  'px-4 py-2 text-sm font-medium rounded-[10px] transition-all',
                  isRegenerating ? 'bg-danger text-white hover:bg-red-600' : 'bg-accent text-text-inverted hover:bg-accent-hover'
                ]"
              >
                {{ isRegenerating ? 'Regenerate' : (isEditing ? 'Update' : 'Create') }}
              </button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Plus, Key } from 'lucide-vue-next';

const keys = ref([]);
const loading = ref(true);
const showCreateModal = ref(false);
const newKeyName = ref('');
const newToken = ref('');
const isEditing = ref(false);
const isRegenerating = ref(false);
const editingKeyId = ref(null);
const toast = ref(null);

const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => (toast.value = null), 3000);
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const fetchKeys = async () => {
  loading.value = true;
  try {
    const res = await fetch('/api-watcher/api/keys');
    if (res.ok) keys.value = await res.json();
  } catch (e) { console.error('Failed to fetch keys', e); }
  finally { loading.value = false; }
};

const openEditModal = (key) => {
  isEditing.value = true;
  isRegenerating.value = false;
  editingKeyId.value = key.id;
  newKeyName.value = key.name;
  newToken.value = '';
  showCreateModal.value = true;
};

const regenerateKey = (key) => {
  isRegenerating.value = true;
  isEditing.value = false;
  editingKeyId.value = key.id;
  newKeyName.value = key.name;
  newToken.value = '';
  showCreateModal.value = true;
};

const saveKey = async () => {
  if (isRegenerating.value) await confirmRegenerate();
  else if (isEditing.value) await updateKey();
  else await createKey();
};

const confirmRegenerate = async () => {
  try {
    const res = await fetch(`/api-watcher/api/keys/${editingKeyId.value}/refresh`, {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
    });
    if (res.ok) { const d = await res.json(); newToken.value = d.token; await fetchKeys(); }
  } catch (e) { console.error(e); }
};

const updateKey = async () => {
  try {
    const res = await fetch(`/api-watcher/api/keys/${editingKeyId.value}`, {
      method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({ name: newKeyName.value })
    });
    if (res.ok) { await fetchKeys(); closeModal(); showToast('Key updated.'); }
  } catch (e) { console.error(e); }
};

const createKey = async () => {
  try {
    const res = await fetch('/api-watcher/api/keys', {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
      body: JSON.stringify({ name: newKeyName.value })
    });
    if (res.ok) { const d = await res.json(); newToken.value = d.token; await fetchKeys(); }
  } catch (e) { console.error(e); }
};

const deleteKey = async (id) => {
  if (!confirm('Delete this API key? Access will be revoked immediately.')) return;
  try {
    await fetch(`/api-watcher/api/keys/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken() } });
    await fetchKeys();
    showToast('Key deleted.');
  } catch (e) { console.error(e); }
};

const closeModal = () => {
  showCreateModal.value = false;
  newKeyName.value = '';
  newToken.value = '';
  isEditing.value = false;
  isRegenerating.value = false;
  editingKeyId.value = null;
};

const copyToken = () => {
  navigator.clipboard.writeText(newToken.value);
  showToast('Copied to clipboard!');
};

const formatDate = (dateString) => {
  const d = new Date(dateString);
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(fetchKeys);
</script>
