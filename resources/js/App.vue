<script setup>
import { ref, provide } from 'vue';
import Sidebar from './components/Sidebar.vue';
import MobileNav from './components/MobileNav.vue';

const sidebarCollapsed = ref(localStorage.getItem('aw-sidebar-collapsed') === 'true');

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  localStorage.setItem('aw-sidebar-collapsed', sidebarCollapsed.value);
};

provide('sidebarCollapsed', sidebarCollapsed);
provide('toggleSidebar', toggleSidebar);
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-surface-base font-sans">
    <!-- Desktop Sidebar -->
    <Sidebar :collapsed="sidebarCollapsed" @toggle="toggleSidebar" />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <main class="flex-1 overflow-auto p-4 md:p-6 pb-20 lg:pb-6">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>

    <!-- Mobile Bottom Nav -->
    <MobileNav />
  </div>
</template>
