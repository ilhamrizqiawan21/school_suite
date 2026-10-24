<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedProps } from '../../../types';

interface SchoolData {
    id: number;
    name: string;
    npsn: string;
    level: string;
    address?: string;
    phone?: string;
    email?: string;
}

const props = defineProps<{
    school: SchoolData;
}>();

const page = usePage<SharedProps>();

const form = useForm({
    name: props.school.name,
    npsn: props.school.npsn,
    level: props.school.level,
    address: props.school.address ?? '',
    phone: props.school.phone ?? '',
    email: props.school.email ?? '',
});

const submit = () => {
    form.put('/pengaturan/sekolah');
};
</script>

<template>
    <AppLayout title="Profil Sekolah">
        <div class="max-w-3xl space-y-6">
            <!-- Header section -->
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Identitas & Profil Sekolah</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Konfigurasi identitas resmi unit sekolah untuk kop surat, rapor, dan dokumen administrasi
                </p>
            </div>

            <!-- Flash message -->
            <div v-if="page.props.flash?.message" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800" role="status">
                {{ page.props.flash.message }}
            </div>

            <!-- Form Card -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-xs">
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Nama Resmi Sekolah
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                                :class="{ 'border-rose-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                NPSN
                            </label>
                            <input
                                v-model="form.npsn"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                                :class="{ 'border-rose-500': form.errors.npsn }"
                            />
                            <p v-if="form.errors.npsn" class="text-xs text-rose-600 mt-1 font-medium">{{ form.errors.npsn }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Jenjang Sekolah
                            </label>
                            <select
                                v-model="form.level"
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="SD">SD (Sekolah Dasar)</option>
                                <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                                <option value="SMA">SMA (Sekolah Menengah Atas)</option>
                                <option value="SMK">SMK (Sekolah Menengah Kejuruan)</option>
                                <option value="MI">MI (Madrasah Ibtidaiyah)</option>
                                <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
                                <option value="MA">MA (Madrasah Aliyah)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Telepon
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                                Email Resmi
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                            Alamat Lengkap
                        </label>
                        <textarea
                            v-model="form.address"
                            rows="3"
                            class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-indigo-500"
                            placeholder="Alamat jalan, kelurahan, kecamatan, kota/kabupaten..."
                        ></textarea>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition-colors focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Profil Sekolah' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
