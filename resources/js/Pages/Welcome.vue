<script setup lang="ts">
defineProps<{
    version: string;
    phpVersion: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-6 text-slate-800">
        <div class="max-w-md w-full bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center space-y-4">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xl">
                SS
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">School Suite</h1>
            <p class="text-sm text-slate-500">
                Satu ruang kerja sekolah terpadu untuk akademik, pembelajaran, penilaian, dan tata kelola.
            </p>
            <div class="pt-4 border-t border-slate-100 flex justify-between text-xs text-slate-400">
                <span>Laravel {{ version }}</span>
                <span>PHP {{ phpVersion }}</span>
            </div>
        </div>
    </div>
</template>

