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
    legal_name?: string;
    school_status?: string;
    accreditation?: string;
    principal_name?: string;
    website?: string;
    vision?: string;
    mission?: string;
    logo_path?: string;
    favicon_path?: string;
    settings?: { theme_primary?: string };
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
    legal_name: props.school.legal_name ?? '',
    school_status: props.school.school_status ?? '',
    accreditation: props.school.accreditation ?? '',
    principal_name: props.school.principal_name ?? '',
    website: props.school.website ?? '',
    vision: props.school.vision ?? '',
    mission: props.school.mission ?? '',
    theme_primary: props.school.settings?.theme_primary ?? '#3157a4',
    logo: null as File | null,
    favicon: null as File | null,
});

const submit = () => {
    form.post('/pengaturan/sekolah', { forceFormData: true, preserveScroll: true });
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
                    <section class="rounded-xl border border-slate-200 p-4 space-y-4">
                        <div><h2 class="text-sm font-bold text-slate-900">Branding sekolah</h2><p class="text-xs text-slate-500">Mengubah identitas visual tanpa mengubah susunan layout.</p></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Logo</label><input type="file" accept="image/jpeg,image/png,image/webp" @input="form.logo = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-xs"/><p class="text-[11px] text-slate-500 mt-1">JPG/PNG/WebP, maksimal 2 MB.</p><p v-if="form.errors.logo" class="text-xs text-rose-600">{{ form.errors.logo }}</p></div>
                            <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Favicon</label><input type="file" accept="image/png" @input="form.favicon = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-xs"/><p class="text-[11px] text-slate-500 mt-1">PNG persegi, maksimal 512 KB.</p><p v-if="form.errors.favicon" class="text-xs text-rose-600">{{ form.errors.favicon }}</p></div>
                        </div>
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Warna utama</label><div class="flex flex-wrap gap-3"><label v-for="color in ['#3157a4','#277450','#7255ae','#b87818','#b64a42']" :key="color" class="theme-choice"><input v-model="form.theme_primary" type="radio" :value="color"><i :style="{background:color}"></i><span>{{ color }}</span></label></div><p v-if="form.errors.theme_primary" class="text-xs text-rose-600">{{ form.errors.theme_primary }}</p></div>
                    </section>
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
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Badan Hukum</label><input v-model="form.legal_name" type="text" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs" /></div>
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Status Sekolah</label><select v-model="form.school_status" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs"><option value="">Belum diisi</option><option value="Negeri">Negeri</option><option value="Swasta">Swasta</option></select></div>
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Akreditasi</label><input v-model="form.accreditation" type="text" maxlength="10" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs" /></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Kepala Sekolah</label><input v-model="form.principal_name" type="text" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs" /></div>
                        <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Website</label><input v-model="form.website" type="url" placeholder="https://sekolah.sch.id" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs" /></div>
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

                    <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Visi</label><textarea v-model="form.vision" rows="3" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs"></textarea></div>
                    <div><label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Misi</label><textarea v-model="form.mission" rows="5" class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs" placeholder="Tuliskan setiap butir misi pada baris terpisah"></textarea></div>

                    <div class="pt-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition-colors focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                            style="background:var(--primary)"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Profil Sekolah' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
<style scoped>.theme-choice{display:flex;align-items:center;gap:.4rem;padding:.45rem .65rem;border:1px solid #cbd5e1;border-radius:.65rem;font-size:.7rem}.theme-choice i{width:1.1rem;height:1.1rem;border-radius:50%}</style>
