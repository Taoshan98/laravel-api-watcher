<script setup>
import { useRoute } from 'vue-router';
import { LayoutDashboard, List, Activity, Bell, MoreHorizontal } from 'lucide-vue-next';

const route = useRoute();

const tabs = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'Requests', href: '/requests', icon: List },
  { name: 'Analytics', href: '/analytics', icon: Activity },
  { name: 'Alerts', href: '/alerts', icon: Bell },
  { name: 'More', href: '/settings', icon: MoreHorizontal },
];

const isActive = (path) => {
  if (path === '/') return route.path === '/';
  if (path === '/settings') return route.path.startsWith('/settings') || route.path.startsWith('/keys');
  return route.path.startsWith(path);
};
</script>

<template>
  <nav class="fixed bottom-0 left-0 right-0 z-50 lg:hidden glass-surface-blur pb-safe">
    <div class="flex items-center justify-around h-14">
      <router-link
        v-for="tab in tabs"
        :key="tab.name"
        :to="tab.href"
        :class="[
          'flex flex-col items-center justify-center gap-0.5 flex-1 h-full transition-colors duration-200',
          isActive(tab.href) ? 'text-accent' : 'text-text-muted'
        ]"
      >
        <component :is="tab.icon" class="w-5 h-5" />
        <span class="text-[10px] font-medium">{{ tab.name }}</span>
      </router-link>
    </div>
  </nav>
</template>
