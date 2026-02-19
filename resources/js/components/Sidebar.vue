<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useRequests } from '../composables/useRequests';
import {
  LayoutDashboard,
  List,
  Activity,
  Bell,
  Key,
  Settings,
  ChevronLeft,
  ChevronRight,
  Radio
} from 'lucide-vue-next';

const props = defineProps({
  collapsed: Boolean
});

const emit = defineEmits(['toggle']);

const route = useRoute();
const { packageVersion, fetchConfig } = useRequests();

const navigation = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'Requests', href: '/requests', icon: List },
  { name: 'Analytics', href: '/analytics', icon: Activity },
  { name: 'Alerts', href: '/alerts', icon: Bell },
  { name: 'API Keys', href: '/keys', icon: Key },
  { name: 'Settings', href: '/settings', icon: Settings },
];

const isActive = (path) => {
  if (path === '/') return route.path === '/';
  return route.path.startsWith(path);
};

onMounted(() => {
  fetchConfig();
});
</script>

<template>
  <aside
    :class="[
      'hidden lg:flex flex-col flex-shrink-0 h-screen transition-all duration-300 ease-in-out relative',
      'bg-surface-raised border-r border-border-subtle',
      collapsed ? 'w-[68px]' : 'w-60'
    ]"
  >
    <!-- Collapse toggle (Border Button) -->
    <button
      @click="emit('toggle')"
      class="absolute top-8 -right-3 w-6 h-6 rounded-full bg-surface-raised border border-border-white flex items-center justify-center text-text-white hover:text-accent hover:border-accent shadow-lg transition-all duration-200 z-50 group"
      :title="collapsed ? 'Expand' : 'Collapse'"
    >
      <component 
        :is="collapsed ? ChevronRight : ChevronLeft" 
        class="w-3.5 h-3.5 transition-transform duration-200 group-hover:scale-110" 
      />
    </button>

    <!-- Brand -->
    <div class="h-16 flex items-center px-4 border-b border-border-subtle flex-shrink-0">
      <div class="flex items-center gap-2.5 overflow-hidden">
        <Radio class="w-6 h-6 text-accent flex-shrink-0" />
        <transition name="fade">
          <span v-if="!collapsed" class="text-lg font-bold text-gradient-accent whitespace-nowrap">
            API Watcher
          </span>
        </transition>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2.5 py-4 space-y-1 overflow-y-auto">
      <router-link
        v-for="item in navigation"
        :key="item.name"
        :to="item.href"
        :class="[
          'group flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-[10px] transition-all duration-200 relative',
          isActive(item.href)
            ? 'bg-accent-soft text-accent'
            : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'
        ]"
        :title="collapsed ? item.name : undefined"
      >
        <!-- Active indicator -->
        <div
          v-if="isActive(item.href)"
          class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 bg-accent rounded-r-full"
        />

        <component
          :is="item.icon"
          :class="[
            'flex-shrink-0 w-[18px] h-[18px] transition-colors duration-200',
            isActive(item.href) ? 'text-accent' : 'text-text-muted group-hover:text-text-secondary'
          ]"
        />

        <transition name="fade">
          <span v-if="!collapsed" class="whitespace-nowrap">{{ item.name }}</span>
        </transition>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="border-t border-border-subtle p-2.5 flex-shrink-0">
      <!-- Version -->
      <div v-show="!collapsed" class="px-3 py-1">
        <span class="text-[15px] text-text-muted">{{ packageVersion }}</span>
      </div>
    </div>
  </aside>
</template>
