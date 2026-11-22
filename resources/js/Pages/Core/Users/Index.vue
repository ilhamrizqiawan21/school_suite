<script setup lang="ts">
import AppButton from '@/components/AppButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
    can_update: boolean;
    can_delete: boolean;
}

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    users: PaginatedData<UserItem>;
    roles: RoleItem[];
    filter_roles: RoleItem[];
    current_user_id: number;
    filters: { search?: string; role?: string; status?: string };
    can_create: boolean;
    can_update: boolean;
    can_delete: boolean;
    can_manage_roles: boolean;
}>();

const page = usePage<SharedProps>();
const search = ref(props.filters.search ?? '');
const selectedRole = ref(props.filters.role ?? '');

const selectedStatus = ref(props.filters.status ?? '');
const applyFilters = () => router.get('/pengaturan/pengguna', {
    search: search.value || undefined, role: selectedRole.value || undefined, status: selectedStatus.value || undefined,
}, { preserveState: true, replace: true });
const resetFilters = () => {
    search.value = ''; selectedRole.value = ''; selectedStatus.value = ''; applyFilters();
};
const showCreate = ref(false);
const isEditModalOpen = ref(false);
const activeOverlay = computed(() => showCreate.value || isEditModalOpen.value);
const overlayRoot = ref<HTMLElement | null>(null);
let overlayTrigger: HTMLElement | null = null;
watch(activeOverlay, async (open) => {
    if (open) {
        overlayTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        await nextTick();
        overlayRoot.value?.querySelector<HTMLElement>('input:not([type="hidden"]), button')?.focus();
    } else {
        await nextTick();
        overlayTrigger?.focus();
        overlayTrigger = null;
    }
});
const handleOverlayKeydown = (event: KeyboardEvent) => {
    if (!activeOverlay.value) return;
    if (event.key === 'Escape') {
        if (isEditModalOpen.value) isEditModalOpen.value = false;
        else showCreate.value = false;
    }
    if (event.key !== 'Tab' || !overlayRoot.value) return;
    const focusable = Array.from(overlayRoot.value.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), select:not([disabled]), a[href]'));
    const first = focusable[0];
    const last = focusable.at(-1);
    if (first && last && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    }
};
onMounted(() => document.addEventListener('keydown', handleOverlayKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleOverlayKeydown));
const deletingId = ref<number | null>(null);
const actionError = ref('');
const changePage = (url: string | null) => {
    if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
};

const createForm = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role_id: '' as number | '',
    is_active: true,
});
const createErrors = computed(() => Object.entries(createForm.errors));

const submitCreate = () => {
    createForm.post('/pengaturan/pengguna', {
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
};

// Edit Modal
const editingUser = ref<UserItem | null>(null);
const editForm = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    password: '',
    role_id: null as number | null,
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
    editForm.role_id = user.role?.id ?? null;
    editForm.is_active = user.is_active;
    isEditModalOpen.value = true;
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.transform((data) => {
        const payload: Record<string, unknown> = { ...data };
        if (!props.can_manage_roles || data.role_id === null || editingUser.value?.id === props.current_user_id) delete payload.role_id;
        if (!data.password || editingUser.value?.id === props.current_user_id) delete payload.password;
        return payload;
    }).put(`/pengaturan/pengguna/${editingUser.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            editingUser.value = null;
        },
    });
};

// Delete action
const pendingDeleteUser = ref<UserItem | null>(null);
const showCreatePassword = ref(false);
const showEditPassword = ref(false);

const deleteUser = (user: UserItem) => {
    pendingDeleteUser.value = user;
};

const confirmDeleteUser = () => {
    const user = pendingDeleteUser.value;
    if (!user) return;
    actionError.value = '';
    deletingId.value = user.id;
    router.delete(`/pengaturan/pengguna/${user.id}`, {
        preserveScroll: true,
        onError: (errors) => { actionError.value = Object.values(errors).join(' '); },
        onFinish: () => {
            deletingId.value = null;
            pendingDeleteUser.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <div class="view user-page space-y-6">
            <Head title="Manajemen Pengguna" />
            <!-- Header section -->
            <div class="page-heading compact-heading">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Akun Pengguna</h1>

                </div>
                <AppButton variant="primary" icon="plus" v-if="can_create" type="button" class="primary-button" @click="showCreate = true">Tambah pengguna</AppButton>
            </div>

            <!-- Flash message -->
            <p v-if="actionError" class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">{{ actionError }}</p>
            <form class="panel grid gap-3 p-5 sm:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]" @submit.prevent="applyFilters">
                <label class="user-field">Cari pengguna<input v-model="search" class="search-control" type="search" maxlength="255" placeholder="Nama, email, atau username" /></label>
                <label class="user-field">Peran<select v-model="selectedRole"><option value="">Semua peran</option><option v-for="r in filter_roles" :key="r.id" :value="r.name">{{ r.label }}</option></select></label>
                <label class="user-field">Status akun<select v-model="selectedStatus"><option value="">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                <div class="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-3"><AppButton variant="primary" type="submit" class="primary-button">Terapkan filter</AppButton><AppButton variant="secondary" type="button" class="secondary-button" @click="resetFilters">Reset</AppButton></div>
            </form>

            <section v-if="can_create && showCreate" ref="overlayRoot" class="user-overlay" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
              <div class="user-drawer">
                <div class="user-drawer-header mb-4"><div><p class="text-meta font-bold uppercase tracking-[.12em] text-indigo-600">Tambah data</p><h2 id="create-user-title" class="text-base font-bold text-slate-900">Pengguna baru</h2></div><AppButton variant="ghost" type="button" class="user-close" aria-label="Tutup formulir pengguna baru" @click="showCreate = false">×</AppButton></div>
                <form class="grid gap-3 sm:grid-cols-2 items-end" @submit.prevent="submitCreate">
                    <div v-if="createErrors.length" class="sm:col-span-full rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-meta text-rose-700" role="alert" aria-live="polite">
                        <p class="font-semibold">Pengguna belum tersimpan. Periksa kembali:</p>
                        <ul class="mt-1 list-inside list-disc">
                            <li v-for="([field, message]) in createErrors" :key="field">{{ message }}</li>
                        </ul>
                    </div>
                    <label class="text-meta font-semibold text-slate-700">Nama lengkap<input v-model="createForm.name" required :aria-invalid="!!createForm.errors.name" class="mt-1 w-full rounded-lg border border-slate-300 bg-[var(--surface)] px-3 py-2 text-sm" /><span v-if="createForm.errors.name" class="mt-1 block text-meta font-normal text-rose-600">{{ createForm.errors.name }}</span></label>
                    <label class="text-meta font-semibold text-slate-700">Email<input v-model="createForm.email" type="email" required :aria-invalid="!!createForm.errors.email" class="mt-1 w-full rounded-lg border border-slate-300 bg-[var(--surface)] px-3 py-2 text-sm" /><span v-if="createForm.errors.email" class="mt-1 block text-meta font-normal text-rose-600">{{ createForm.errors.email }}</span></label>
                    <label class="text-meta font-semibold text-slate-700">Username<input v-model="createForm.username" :aria-invalid="!!createForm.errors.username" class="mt-1 w-full rounded-lg border border-slate-300 bg-[var(--surface)] px-3 py-2 text-sm" /><span v-if="createForm.errors.username" class="mt-1 block text-meta font-normal text-rose-600">{{ createForm.errors.username }}</span></label>
                    <label class="text-meta font-semibold text-slate-700">Peran<select v-model="createForm.role_id" required :aria-invalid="!!createForm.errors.role_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-[var(--surface)] px-3 py-2 text-sm"><option disabled value="">Pilih peran</option><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option></select><span v-if="createForm.errors.role_id" class="mt-1 block text-meta font-normal text-rose-600">{{ createForm.errors.role_id }}</span></label>
                    <label class="text-meta font-semibold text-slate-700">Kata sandi
                        <span class="relative mt-1 flex items-center">
                            <input v-model="createForm.password" :type="showCreatePassword ? 'text' : 'password'" minlength="8" required :aria-invalid="!!createForm.errors.password" class="w-full rounded-lg border border-slate-300 bg-[var(--surface)] px-3 py-2 pr-16 text-sm" />
                            <button type="button" class="absolute right-2 rounded px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" :aria-label="showCreatePassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showCreatePassword" @click="showCreatePassword = !showCreatePassword">{{ showCreatePassword ? 'Tutup' : 'Lihat' }}</button>
                        </span>
                        <span v-if="createForm.errors.password" class="mt-1 block text-meta font-normal text-rose-600">{{ createForm.errors.password }}</span>
                    </label>
                    <p class="text-meta leading-5 text-slate-600 sm:col-span-full">Pengguna wajib mengganti kata sandi awal saat masuk. Akun guru dan siswa juga dapat dibuat melalui menu Data Guru atau Data Siswa.</p>
                    <div class="user-drawer-footer flex flex-wrap justify-end gap-2 sm:col-span-full"><AppButton variant="secondary"  type="button" class="secondary-button" :disabled="createForm.processing" @click="showCreate = false">Batal</AppButton><AppButton variant="primary" :loading="createForm.processing" type="submit" :disabled="createForm.processing" class="primary-button">{{ createForm.processing ? 'Menyimpan…' : 'Simpan pengguna' }}</AppButton></div>
                </form>
              </div>
            </section>

            <!-- Users Table -->
            <div class="panel user-table-panel overflow-hidden">
                <p class="px-4 py-3 text-meta text-slate-600">Geser tabel ke samping pada layar kecil untuk melihat semua kolom dan tindakan.</p>
                <div class="overflow-x-auto table-scroll" tabindex="0" role="region" aria-label="Daftar pengguna">
                    <table class="app-table min-w-[680px] w-full text-left border-collapse text-meta">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                                <th scope="col" class="py-3 px-4">Nama Lengkap</th>
                                <th scope="col" class="py-3 px-4">Akun</th>
                                <th scope="col" class="py-3 px-4 text-center">Peran</th>
                                <th scope="col" class="py-3 px-4 text-center">Status</th>
                                <th scope="col" v-if="can_update || can_delete" class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ u.name }}<span v-if="u.id === current_user_id" class="ml-2 text-meta font-normal text-blue-700">Anda</span>
                                    <span v-if="u.phone" class="block text-meta font-normal text-slate-600">{{ u.phone }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ u.email }}</div>
                                    <div v-if="u.username" class="text-meta text-slate-600">@{{ u.username }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded-md font-medium text-meta bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ u.role?.label ?? 'Tanpa Peran' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-meta font-medium',
                                            u.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', u.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td v-if="can_update || can_delete" class="py-3 px-4 text-center space-x-2">
                                    <RowActionButton v-if="u.can_update" action="edit" label="Ubah" @click="openEditModal(u)" />
                                    <RowActionButton v-if="u.can_delete" :disabled="deletingId !== null" action="delete" label="Hapus" @click="deleteUser(u)" />
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td :colspan="can_update || can_delete ? 5 : 4" class="py-8 text-center text-slate-600">
                                    Tidak ada data pengguna yang cocok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <nav v-if="users.total" class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600" aria-label="Halaman pengguna">
                <p>{{ users.from }}–{{ users.to }} dari {{ users.total }} pengguna · Halaman {{ users.current_page }} / {{ users.last_page }}</p>
                <div class="flex gap-2"><AppButton variant="secondary" type="button" class="secondary-button" :disabled="!users.prev_page_url" @click="changePage(users.prev_page_url)">Sebelumnya</AppButton><AppButton variant="secondary" type="button" class="secondary-button" :disabled="!users.next_page_url" @click="changePage(users.next_page_url)">Berikutnya</AppButton></div>
            </nav>
            <!-- Edit Modal -->
            <div v-if="isEditModalOpen" ref="overlayRoot" class="user-overlay" role="dialog" aria-modal="true" aria-labelledby="edit-user-title">
                <div class="user-drawer space-y-4">
                    <div class="user-drawer-header flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 id="edit-user-title" class="text-sm font-bold text-slate-900">Ubah Data Pengguna</h2>
                        <AppButton variant="ghost" type="button" @click="isEditModalOpen = false" class="user-close" aria-label="Tutup formulir ubah pengguna">×</AppButton>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-3">
                        <div v-if="editErrors.length" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-meta text-rose-700" role="alert" aria-live="polite">
                            <p class="font-semibold">Perubahan belum tersimpan. Periksa kembali:</p>
                            <ul class="mt-1 list-inside list-disc">
                                <li v-for="([field, message]) in editErrors" :key="field">{{ message }}</li>
                            </ul>
                        </div>
                        <div>
                            <label for="edit-user-name" class="block text-meta font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                            <input id="edit-user-name" v-model="editForm.name" type="text" required :aria-invalid="!!editForm.errors.name" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-meta" />
                            <p v-if="editForm.errors.name" class="text-meta text-rose-600 mt-0.5">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="edit-user-email" class="block text-meta font-semibold text-slate-700 mb-1">Email</label>
                                <input id="edit-user-email" v-model="editForm.email" type="email" required :aria-invalid="!!editForm.errors.email" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-meta" />
                                <p v-if="editForm.errors.email" class="text-meta text-rose-600 mt-0.5">{{ editForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-meta font-semibold text-slate-700 mb-1">Peran Akses</label>
                                <select v-if="can_manage_roles && editingUser?.id !== current_user_id" v-model="editForm.role_id" aria-label="Peran Akses" :aria-invalid="!!editForm.errors.role_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-meta">
                                    <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.label }}</option>
                                </select>
                                <p v-else class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-meta text-slate-600">{{ editingUser?.role?.label ?? 'Tanpa Peran' }}</p>
                                <p v-if="editForm.errors.role_id" class="text-meta text-rose-600 mt-0.5">{{ editForm.errors.role_id }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-if="editingUser?.id !== current_user_id">
                                <label for="edit-user-password" class="block text-meta font-semibold text-slate-700 mb-1">Kata Sandi Baru (Kosongkan bila tidak diganti)</label>
                                <div class="relative flex items-center">
                                    <input id="edit-user-password" v-model="editForm.password" :type="showEditPassword ? 'text' : 'password'" minlength="8" :aria-invalid="!!editForm.errors.password" class="w-full px-3 py-2 pr-16 rounded-lg border border-slate-300 text-meta" />
                                    <button type="button" class="absolute right-2 rounded px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100" :aria-label="showEditPassword ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'" :aria-pressed="showEditPassword" @click="showEditPassword = !showEditPassword">{{ showEditPassword ? 'Tutup' : 'Lihat' }}</button>
                                </div>
                                <p v-if="editForm.errors.password" class="text-meta text-rose-600 mt-0.5">{{ editForm.errors.password }}</p>
                            </div>
                            <p v-else class="text-meta leading-5 text-slate-600">Untuk mengganti kata sandi Anda, buka menu Ganti Kata Sandi.</p>
                            <div class="flex items-center pt-5">
                                <label class="flex items-center gap-2 cursor-pointer text-meta font-semibold text-slate-700">
                                    <input v-model="editForm.is_active" :disabled="editingUser?.id === current_user_id" type="checkbox" class="rounded border-slate-300 text-indigo-600" />
                                    <span>Akun Aktif</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="user-field">Username<input v-model="editForm.username" maxlength="50" /><small v-if="editForm.errors.username" class="text-rose-700">{{ editForm.errors.username }}</small></label>
                            <label class="user-field">Nomor telepon<input v-model="editForm.phone" type="tel" maxlength="30" /><small v-if="editForm.errors.phone" class="text-rose-700">{{ editForm.errors.phone }}</small></label>
                        </div>
                        <p class="text-meta leading-5 text-slate-600">Reset kata sandi mewajibkan pengguna menggantinya saat masuk berikutnya. Nonaktifkan akun untuk mencabut akses tanpa menghapus riwayat.</p>
                        <div class="user-drawer-footer pt-2 flex justify-end gap-2">
                            <AppButton variant="secondary" type="button" @click="isEditModalOpen = false" >Batal</AppButton>
                            <AppButton variant="primary" :loading="editForm.processing" type="submit" :disabled="editForm.processing" class="primary-button">Perbarui</AppButton>
                        </div>
                    </form>
                </div>
            </div>

            <ConfirmDialog
                :open="pendingDeleteUser !== null"
                title="Hapus akun pengguna?"
                :message="pendingDeleteUser ? `Akun pengguna &quot;${pendingDeleteUser.name}&quot; (${pendingDeleteUser.email}) akan dihapus permanen dari daftar akses sekolah.` : ''"
                confirm-label="Ya, hapus pengguna"
                variant="danger"
                :loading="deletingId !== null"
                @confirm="confirmDeleteUser"
                @cancel="pendingDeleteUser = null"
            />
        </div>
    </AppLayout>
</template>

<style scoped>
.user-page .page-heading h1 { color: var(--ink); font-size: var(--type-3xl); font-weight: var(--weight-bold); }
.user-page .page-heading p { color: var(--ink-soft); }
.user-page > form.panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: none; }
.user-table-panel > p { color: var(--ink-soft); }
.user-table-panel thead tr { color: var(--ink-soft); background: var(--surface-subtle); border-color: var(--line); }
.user-table-panel tbody { color: var(--ink); }
.user-table-panel tbody tr { border-color: var(--line); }
.user-table-panel tbody tr:hover { background: var(--primary-soft); }
.user-table-panel tbody td:first-child { color: var(--ink); }
.user-table-panel tbody td:nth-child(2) > div:first-child { color: var(--ink); }
.user-table-panel tbody td :is(.text-slate-400, .text-slate-500) { color: var(--ink-soft); }
.user-field { display: grid; min-width: 0; gap: .4rem; font-size: var(--type-sm); font-weight: var(--weight-semibold); color: var(--ink-soft); }
.user-field input, .user-field select { width: 100%; min-width: 0; min-height: 2.65rem; padding: .6rem .75rem; border: 1px solid var(--line-strong); border-radius: .6rem; background: var(--surface); color: var(--ink); font-weight: var(--weight-regular); }
.user-table-panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: none; }
.user-overlay { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 1rem; background: rgb(19 32 51 / 45%); backdrop-filter: blur(3px); }
.user-drawer { width: min(560px, 100%); max-height: min(90dvh, 860px); height: auto; padding: clamp(18px, 3vw, 28px); overflow-y: auto; overscroll-behavior: contain; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); color: var(--ink); box-shadow: 0 24px 80px rgb(15 23 42 / 25%); }
.user-drawer-header { position: sticky; top: calc(-1 * clamp(18px, 3vw, 28px)); z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: calc(-1 * clamp(18px, 3vw, 28px)); padding-top: clamp(18px, 3vw, 28px); padding-bottom: .75rem; background: var(--surface); border-bottom: 1px solid var(--line); }
.user-drawer-footer { position: sticky; bottom: calc(-1 * clamp(18px, 3vw, 28px)); z-index: 2; margin-bottom: calc(-1 * clamp(18px, 3vw, 28px)); padding: 1rem 0 clamp(18px, 3vw, 28px); background: var(--surface); border-top: 1px solid var(--line); }
.user-close { flex: 0 0 44px; }
.user-drawer :deep(input), .user-drawer :deep(select) { max-width: 100%; color: var(--ink); background: var(--surface); border-color: var(--line-strong); }
.user-drawer :deep(.text-slate-900), .user-drawer :deep(.text-slate-800), .user-drawer :deep(.text-slate-700) { color: var(--ink); }
.user-drawer :deep(.text-slate-600), .user-drawer :deep(.text-slate-500), .user-drawer :deep(.text-slate-400) { color: var(--ink-soft); }
.user-drawer :deep(input:focus-visible), .user-drawer :deep(select:focus-visible), .user-drawer button:focus-visible { outline: 3px solid var(--primary); outline-offset: 2px; }
.page-heading { flex-wrap: wrap; gap: 1rem; }
.page-heading > div { min-width: 0; }
button:disabled { cursor: not-allowed; opacity: .5; }
@media (max-width: 600px) { .user-overlay { padding: .75rem; } .user-drawer { max-height: 92dvh; border-radius: 16px; } }
</style>
