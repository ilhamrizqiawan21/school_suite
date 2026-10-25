<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PageProps {
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
            username?: string;
            role?: string;
            role_label?: string;
            avatar_url?: string | null;
        };
    };
    school?: {
        name: string;
    };
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const userInitials = computed(() => {
    if (!user.value?.name) return 'U';
    return user.value.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});
</script>

<template>
    <div class="topbar-user">
        <div v-if="user" class="flex items-center gap-3">
            <Link href="/profil" class="profile-button" title="Buka profil saya">
                <span class="avatar"><img v-if="user.avatar_url" :src="user.avatar_url" alt=""><template v-else>{{ userInitials }}</template></span>
                <div class="profile-copy">
                    <strong>{{ user.name }}</strong>
                    <small>{{ user.role_label }}</small>
                </div>
            </Link>

            <Link
                href="/logout"
                method="post"
                as="button"
                class="icon-button"
                title="Keluar / Logout"
                aria-label="Keluar / Logout"
            >
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </Link>
        </div>
        <div v-else>
            <Link href="/login" class="primary-button">Masuk</Link>
        </div>
    </div>
</template>
<style scoped>.avatar{overflow:hidden}.avatar img{width:100%;height:100%;object-fit:cover}</style>
