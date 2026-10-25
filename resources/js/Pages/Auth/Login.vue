<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props=defineProps<{status?:string;branding?:{school_name:string;logo_url?:string|null;favicon_url?:string|null;theme_primary?:string}|null}>();

const form = useForm({
    identity: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
onMounted(()=>{if(props.branding?.theme_primary)document.documentElement.style.setProperty('--primary',props.branding.theme_primary);if(props.branding?.favicon_url){let link=document.querySelector<HTMLLinkElement>('link[rel="icon"]');if(!link){link=document.createElement('link');link.rel='icon';document.head.appendChild(link)}link.href=props.branding.favicon_url}});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 text-slate-800">
        <Head title="Masuk ke Sistem" />

        <div class="max-w-md w-full bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
            <div class="text-center space-y-2">
                <img v-if="branding?.logo_url" :src="branding.logo_url" :alt="`Logo ${branding.school_name}`" class="mx-auto w-14 h-14 rounded-xl object-contain" />
                <div v-else class="inline-flex items-center justify-center w-12 h-12 rounded-xl text-white font-bold text-lg shadow-sm" style="background:var(--primary)">SS</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">School Suite</h1>
                <strong v-if="branding?.school_name" class="block text-sm text-slate-700">{{ branding.school_name }}</strong>
                <p class="text-xs text-slate-500">
                    Masuk dengan email atau nama pengguna Anda
                </p>
            </div>

            <div v-if="status" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800" role="status">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label for="identity" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Email atau Nama Pengguna
                    </label>
                    <input
                        id="identity"
                        v-model="form.identity"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="contoh@sekolah.sch.id / username"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        :class="{ 'border-rose-500 focus:ring-rose-500': form.errors.identity }"
                    />
                    <p v-if="form.errors.identity" class="text-xs text-rose-600 mt-1 font-medium">
                        {{ form.errors.identity }}
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                            Kata Sandi
                        </label>
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium focus:outline-hidden"
                            tabindex="-1"
                        >
                            {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
                        </button>
                    </div>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        :class="{ 'border-rose-500 focus:ring-rose-500': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="text-xs text-rose-600 mt-1 font-medium">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                        />
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm transition-colors shadow-xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    style="background:var(--primary); color:#fff"
                >
                    <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>

