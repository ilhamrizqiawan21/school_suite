<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActivePeriodBadge from './ActivePeriodBadge.vue';

interface PageProps {
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
            username?: string;
            role?: string;
            role_label?: string;
        };
    };
    school?: {
        name: string;
    };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const schoolName = computed(() => page.props.school?.name ?? 'Sekolah Pilot School Suite');
const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => {
    if (!user.value?.name) return 'U';
    return user.value.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});
</script>

<template>
    <header class="h-16 border-b border-slate-200 bg-white/80 backdrop-blur-xs sticky top-0 z-20 flex items-center justify-between px-6">
        <div class="flex items-center gap-4">
            <div class="flex flex-col">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unit Sekolah</span>
                <span class="text-sm font-semibold text-slate-900 leading-tight">{{ schoolName }}</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <ActivePeriodBadge />
            <div class="h-6 w-px bg-slate-200 mx-1" aria-hidden="true"></div>

            <div v-if="user" class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-indigo-50 border border-indigo-200 flex items-center justify-center text-xs font-bold text-indigo-700">
                        {{ userInitials }}
                    </span>
                    <div class="hidden sm:flex flex-col text-left">
                        <span class="text-xs font-semibold text-slate-900 leading-tight">{{ user.name }}</span>
                        <span class="text-[10px] text-slate-500 leading-tight">{{ user.role_label }}</span>
                    </div>
                </div>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors focus:outline-hidden focus:ring-2 focus:ring-rose-500"
                    title="Keluar / Logout"
                    aria-label="Keluar / Logout"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </Link>
            </div>
            <div v-else>
                <Link
                    href="/login"
                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium transition-colors"
                >
                    Masuk
                </Link>
            </div>
        </div>
    </header>
</template>
