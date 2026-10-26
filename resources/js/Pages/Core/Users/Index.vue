<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RowActionButton from '@/components/RowActionButton.vue';
import AppLayout from '../../../layouts/AppLayout.vue';
import type { SharedProps } from '../../../types';

interface RoleItem {
    id: number;
    name: string;
    label: string;
}

interface UserItem {
    id: number;
    name: string;
    username?: string;
    email: string;
    phone?: string;
    is_active: boolean;
    role?: RoleItem;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    users: PaginatedData<UserItem>;
    roles: RoleItem[];
    filters: { search?: string; role?: string };
    can_create: boolean;
    can_update: boolean;
    can_delete: boolean;
    can_manage_roles: boolean;
}>();

const page = usePage<SharedProps>();
const search = ref(props.filters.search ?? '');
const selectedRole = ref(props.filters.role ?? '');

// Filter watcher
watch([search, selectedRole], ([newSearch, newRole]) => {
    router.get(
        '/pengaturan/pengguna',
        { search: newSearch || undefined, role: newRole || undefined },
        { preserveState: true, replace: true }
    );
});

const createForm = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role_id: props.roles[0]?.id ?? 1,
    is_active: true,
});
const createErrors = computed(() => Object.entries(createForm.errors));

const submitCreate = () => {
    createForm.post('/pengaturan/pengguna', {
        onSuccess: () => {
            createForm.reset();
        },
    });
};

// Edit Modal
const isEditModalOpen = ref(false);
const editingUser = ref<UserItem | null>(null);
const editForm = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role_id: 1,
    is_active: true,
});
const editErrors = computed(() => Object.entries(editForm.errors));

const openEditModal = (user: UserItem) => {
    editForm.clearErrors();
    editingUser.value = user;
    editForm.name = user.name;
    editForm.username = user.username ?? '';
    editForm.email = user.email;
    editForm.phone = user.phone ?? '';
    editForm.password = '';
    editForm.role_id = user.role?.id ?? 1;
    editForm.is_active = user.is_active;
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.put(`/pengaturan/pengguna/${editingUser.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingUser.value = null;
        },
    });
};

// Delete action
const deleteUser = (user: UserItem) => {
    if (confirm(`Yakin ingin menghapus pengguna "${user.name}"?`)) {
        router.delete(`/pengaturan/pengguna/${user.id}`);
    }
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <div class="view space-y-6">
            <!-- Header section -->
            <div class="page-heading compact-heading">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Akun Pengguna</h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola akun, peran, dan status akses pengguna unit sekolah
                    </p>
                </div>
            </div>

            <!-- Flash message -->
            <div v-if="page.props.flash?.message" class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800" role="status">
                {{ page.props.flash.message }}
            </div>

            <!-- Filter Card -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama, email, atau username..."
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div class="w-full sm:w-48">
                    <select
                        v-model="selectedRole"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">Semua Peran</option>
                        <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.label }}</option>
                    </select>
                </div>
            </div>

            <section v-if="can_create" class="bg-indigo-50/40 border border-indigo-100 rounded-xl p-5">
                <div class="mb-4"><p class="text-[10px] font-bold uppercase tracking-[.12em] text-indigo-600">Tambah data</p><h2 class="text-base font-bold text-slate-900">Pengguna baru</h2></div>
                <form class="grid gap-3 md:grid-cols-[1.3fr_1.3fr_1.2fr_1fr_1.1fr_auto] items-end" @submit.prevent="submitCreate">
                    <div v-if="createErrors.length" class="md:col-span-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert" aria-live="polite">
                        <p class="font-semibold">Pengguna belum tersimpan. Periksa kembali:</p>
                        <ul class="mt-1 list-inside list-disc">
                            <li v-for="([field, message]) in createErrors" :key="field">{{ message }}</li>
                        </ul>
                    </div>
                    <label class="text-xs font-semibold text-slate-700">Nama lengkap<input v-model="createForm.name" required :aria-invalid="!!createForm.errors.name" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><span v-if="createForm.errors.name" class="mt-1 block text-[11px] font-normal text-rose-600">{{ createForm.errors.name }}</span></label>
                    <label class="text-xs font-semibold text-slate-700">Email<input v-model="createForm.email" type="email" required :aria-invalid="!!createForm.errors.email" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><span v-if="createForm.errors.email" class="mt-1 block text-[11px] font-normal text-rose-600">{{ createForm.errors.email }}</span></label>
                    <label class="text-xs font-semibold text-slate-700">Username<input v-model="createForm.username" :aria-invalid="!!createForm.errors.username" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><span v-if="createForm.errors.username" class="mt-1 block text-[11px] font-normal text-rose-600">{{ createForm.errors.username }}</span></label>
                    <label class="text-xs font-semibold text-slate-700">Peran<select v-model="createForm.role_id" :aria-invalid="!!createForm.errors.role_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></select><span v-if="createForm.errors.role_id" class="mt-1 block text-[11px] font-normal text-rose-600">{{ createForm.errors.role_id }}</span></label>
                    <label class="text-xs font-semibold text-slate-700">Kata sandi<input v-model="createForm.password" type="password" minlength="8" required :aria-invalid="!!createForm.errors.password" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><span v-if="createForm.errors.password" class="mt-1 block text-[11px] font-normal text-rose-600">{{ createForm.errors.password }}</span></label>
                    <button type="submit" :disabled="createForm.processing" class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Simpan</button>
                </form>
            </section>

            <!-- Users Table -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Nama Lengkap</th>
                                <th class="py-3 px-4">Akun</th>
                                <th class="py-3 px-4">Peran</th>
                                <th class="py-3 px-4">Status</th>
                                <th v-if="can_update || can_delete" class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ u.name }}
                                    <span v-if="u.phone" class="block text-[11px] font-normal text-slate-400">{{ u.phone }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ u.email }}</div>
                                    <div v-if="u.username" class="text-[11px] text-slate-400">@{{ u.username }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-md font-medium text-[11px] bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ u.role?.label ?? 'Tanpa Peran' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium',
                                            u.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', u.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td v-if="can_update || can_delete" class="py-3 px-4 text-right space-x-2">
                                    <RowActionButton v-if="can_update" action="edit" label="Ubah" @click="openEditModal(u)" />
                                    <RowActionButton v-if="can_delete" action="delete" label="Hapus" @click="deleteUser(u)" />
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td :colspan="can_update || can_delete ? 5 : 4" class="py-8 text-center text-slate-400">
                                    Tidak ada data pengguna yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Edit Modal -->
            <div v-if="isEditModalOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4 border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900">Ubah Data Pengguna</h2>
                        <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xs">Batal</button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-3">
                        <div v-if="editErrors.length" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert" aria-live="polite">
                            <p class="font-semibold">Perubahan belum tersimpan. Periksa kembali:</p>
                            <ul class="mt-1 list-inside list-disc">
                                <li v-for="([field, message]) in editErrors" :key="field">{{ message }}</li>
                            </ul>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                            <input v-model="editForm.name" type="text" required :aria-invalid="!!editForm.errors.name" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs" />
                            <p v-if="editForm.errors.name" class="text-[11px] text-rose-600 mt-0.5">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                <input v-model="editForm.email" type="email" required :aria-invalid="!!editForm.errors.email" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs" />
                                <p v-if="editForm.errors.email" class="text-[11px] text-rose-600 mt-0.5">{{ editForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Akses</label>
                                <select v-if="can_manage_roles" v-model="editForm.role_id" :aria-invalid="!!editForm.errors.role_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
                                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option>
                                </select>
                                <p v-else class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ editingUser?.role?.label ?? 'Tanpa Peran' }}</p>
                                <p v-if="editForm.errors.role_id" class="text-[11px] text-rose-600 mt-0.5">{{ editForm.errors.role_id }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru (Kosongkan bila tidak diganti)</label>
                                <input v-model="editForm.password" type="password" minlength="8" :aria-invalid="!!editForm.errors.password" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs" />
                                <p v-if="editForm.errors.password" class="text-[11px] text-rose-600 mt-0.5">{{ editForm.errors.password }}</p>
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700">
                                    <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600" />
                                    <span>Akun Aktif</span>
                                </label>
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="isEditModalOpen = false" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium text-slate-600">Batal</button>
                            <button type="submit" :disabled="editForm.processing" class="px-4 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold shadow-xs">Perbarui</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
