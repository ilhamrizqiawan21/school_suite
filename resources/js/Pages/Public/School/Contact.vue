<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import PublicSectionLayout from '@/components/PublicSectionLayout.vue';

interface School {
    name: string;
    logo_url?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    website?: string | null;
}

defineProps<{ school: School }>();
</script>

<template>
    <PublicSectionLayout
        :school="school"
        title="Hubungi sekolah"
        eyebrow="Kontak resmi"
        :description="`Temukan alamat dan kanal komunikasi resmi ${school.name}.`"
        active="contact"
    >
        <section class="section-content" aria-label="Kontak sekolah">
            <div class="contact-grid">
                <article class="contact-card contact-card-main">
                    <span class="card-label">Alamat sekolah</span>
                    <p>{{ school.address || 'Alamat sekolah belum diatur.' }}</p>
                    <Link href="/profil-sekolah" class="card-link">Lihat profil sekolah →</Link>
                </article>
                <article class="contact-card">
                    <span class="card-label">Telepon</span>
                    <a v-if="school.phone" :href="`tel:${school.phone}`">{{ school.phone }}</a>
                    <p v-else>Nomor telepon belum diatur.</p>
                </article>
                <article class="contact-card">
                    <span class="card-label">Email</span>
                    <a v-if="school.email" :href="`mailto:${school.email}`">{{ school.email }}</a>
                    <p v-else>Alamat email belum diatur.</p>
                </article>
                <article v-if="school.website" class="contact-card">
                    <span class="card-label">Website</span>
                    <a :href="school.website" target="_blank" rel="noreferrer">{{ school.website }}</a>
                </article>
            </div>
        </section>
    </PublicSectionLayout>
</template>

<style scoped>
.section-content { padding-bottom: 1rem; }.contact-grid { display: grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 1rem; }.contact-card { display: grid; align-content: start; gap: .7rem; min-height: 150px; padding: 1.35rem; background: #fffdf8; border: 1px solid #ded7c9; border-radius: .8rem; }.contact-card-main { grid-row: span 2; color: #f5efe2; background: #123247; border-color: #123247; }.card-label { color: #9a7329; font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }.contact-card-main .card-label { color: #d4a74b; }.contact-card p, .contact-card a { margin: 0; color: #526673; font-size: .95rem; line-height: 1.7; overflow-wrap: anywhere; }.contact-card-main p { color: #e3e9e5; font-family: var(--font-display); font-size: 1.6rem; line-height: 1.25; }.contact-card a { color: #173044; font-weight: 700; text-decoration: none; }.contact-card-main .card-link { align-self: end; margin-top: auto; color: #d4a74b; font-size: .85rem; font-weight: 800; }.card-link { text-decoration: none; }
@media (max-width: 800px) { .contact-grid { grid-template-columns: 1fr 1fr; }.contact-card-main { grid-row: auto; grid-column: 1 / -1; } }
@media (max-width: 560px) { .contact-grid { grid-template-columns: 1fr; }.contact-card-main { grid-column: auto; } }
</style>
