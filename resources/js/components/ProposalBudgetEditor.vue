<script setup lang="ts">
import { computed } from 'vue';
import AppButton from '@/components/AppButton.vue';

export interface BudgetRow {
    name: string;
    qty: number;
    unit: string;
    unit_price: number;
    note?: string | null;
}

const props = withDefaults(defineProps<{
    modelValue: BudgetRow[];
    disabled?: boolean;
}>(), {
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [rows: BudgetRow[]];
}>();

const totalAmount = computed(() =>
    props.modelValue.reduce((sum, item) => sum + Math.max(0, Number(item.qty) || 0) * Math.max(0, Number(item.unit_price) || 0), 0),
);

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);

const addRow = () => {
    emit('update:modelValue', [
        ...props.modelValue,
        { name: '', qty: 1, unit: 'paket', unit_price: 0, note: '' },
    ]);
};

const removeRow = (index: number) => {
    if (props.modelValue.length <= 1) return;
    const copy = [...props.modelValue];
    copy.splice(index, 1);
    emit('update:modelValue', copy);
};

const updateField = (index: number, key: keyof BudgetRow, value: string | number) => {
    const copy = props.modelValue.map((item, idx) => (idx === index ? { ...item, [key]: value } : item));
    emit('update:modelValue', copy);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold text-slate-800">Rincian Rencana Anggaran Biaya (RAB)</h4>
                <p class="text-xs text-slate-500">Total dihitung ulang secara otomatis dan divalidasi oleh server.</p>
            </div>
            <AppButton v-if="!disabled" variant="secondary" size="sm" @click="addRow">
                + Tambah Pos RAB
            </AppButton>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Pos Kebutuhan</th>
                        <th class="px-3 py-2 w-20">Qty</th>
                        <th class="px-3 py-2 w-24">Satuan</th>
                        <th class="px-3 py-2 w-36">Harga Satuan (Rp)</th>
                        <th class="px-3 py-2 w-36 text-right">Subtotal</th>
                        <th v-if="!disabled" class="px-3 py-2 w-16 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="(item, idx) in modelValue" :key="idx">
                        <td class="px-3 py-2">
                            <input
                                :value="item.name"
                                :disabled="disabled"
                                type="text"
                                placeholder="Nama pos anggaran"
                                class="w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-sm"
                                @input="updateField(idx, 'name', ($event.target as HTMLInputElement).value)"
                            >
                        </td>
                        <td class="px-3 py-2">
                            <input
                                :value="item.qty"
                                :disabled="disabled"
                                type="number"
                                min="1"
                                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                @input="updateField(idx, 'qty', Number(($event.target as HTMLInputElement).value))"
                            >
                        </td>
                        <td class="px-3 py-2">
                            <input
                                :value="item.unit"
                                :disabled="disabled"
                                type="text"
                                placeholder="paket"
                                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                @input="updateField(idx, 'unit', ($event.target as HTMLInputElement).value)"
                            >
                        </td>
                        <td class="px-3 py-2">
                            <input
                                :value="item.unit_price"
                                :disabled="disabled"
                                type="number"
                                min="0"
                                class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                @input="updateField(idx, 'unit_price', Number(($event.target as HTMLInputElement).value))"
                            >
                        </td>
                        <td class="px-3 py-2 text-right font-medium text-slate-800">
                            {{ formatCurrency(Math.max(0, Number(item.qty) || 0) * Math.max(0, Number(item.unit_price) || 0)) }}
                        </td>
                        <td v-if="!disabled" class="px-3 py-2 text-center">
                            <button
                                v-if="modelValue.length > 1"
                                type="button"
                                class="text-xs font-medium text-rose-600 hover:underline"
                                @click="removeRow(idx)"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td :colspan="disabled ? 4 : 4" class="px-3 py-2 text-right text-sm font-semibold text-slate-700">
                            Total Pengajuan RAB
                        </td>
                        <td class="px-3 py-2 text-right text-sm font-bold text-slate-900">
                            {{ formatCurrency(totalAmount) }}
                        </td>
                        <td v-if="!disabled" />
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
