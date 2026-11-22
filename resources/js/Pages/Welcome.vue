<script setup lang="ts">
defineProps<{
    version: string;
    phpVersion: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-[var(--surface-subtle)] flex flex-col justify-center items-center p-6 text-[var(--ink)]">
        <div class="max-w-md w-full bg-[var(--surface)] rounded-xl shadow-sm border border-[var(--line)] p-8 text-center space-y-4">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xl">
                SS
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-[var(--ink)]">School Suite</h1>
            <p class="text-sm text-[var(--ink-soft)]">
                Satu ruang kerja sekolah terpadu untuk akademik, pembelajaran, penilaian, dan tata kelola.
            </p>
            <div class="pt-4 border-t border-[var(--line)] flex justify-between text-meta text-[var(--ink-soft)]">
                <span>Laravel {{ version }}</span>
                <span>PHP {{ phpVersion }}</span>
            </div>
        </div>
    </div>
</template>

