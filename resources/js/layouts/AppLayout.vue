<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import TopNavbar from '../components/TopNavbar.vue';
import SidebarNavigation from '../components/SidebarNavigation.vue';

defineProps<{
    title?: string;
}>();

interface PageProps {
    appName?: string;
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const appName = computed(() => page.props.appName ?? 'School Suite');
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col antialiased text-slate-800">
        <Head :title="title" />

        <!-- Accessibility: Skip Link -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-indigo-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-hidden"
        >
            Lewati ke konten utama
        </a>

        <div class="flex flex-1">
            <!-- Sidebar Desktop -->
            <aside class="w-64 border-r border-slate-200 bg-white flex flex-col shrink-0">
                <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-200">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                        SS
                    </div>
                    <span class="font-bold text-base text-slate-900 tracking-tight">{{ appName }}</span>
                </div>

                <div class="flex-1 overflow-y-auto py-2">
                    <SidebarNavigation />
                </div>

                <div class="p-4 border-t border-slate-200 text-xs text-slate-400">
                    School Suite v0.1-pilot
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <TopNavbar />

                <main id="main-content" class="flex-1 p-6 sm:p-8 max-w-7xl w-full mx-auto" tabindex="-1">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

