<script setup lang="ts">
import { usePublicContentText } from '@/composables/usePublicContentText';
import { Link } from '@inertiajs/vue3';
import Head from '@/components/PublicHead.vue';
import { computed } from 'vue';
import PublicOrganizationTreeNode from '@/components/PublicOrganizationTreeNode.vue';
import PublicHeader from '@/components/PublicHeader.vue';
import { publicThemeStyle } from '@/composables/publicTheme';

interface Member {
    id: number;
    parent_id?: number | null;
    name: string;
    position: string;
    department?: string | null;
    photo_url?: string | null;
    depth: number;
}

interface TreeMember extends Member {
    children: TreeMember[];
}

const props = defineProps<{
    school: { name: string; logo_url?: string | null; theme?: { primary?: string | null } };
    organization: Member[];
}>();
const initial = computed(() => props.school.name.trim().charAt(0).toUpperCase() || 'S');
const themeStyle = computed(() => publicThemeStyle(props.school.theme));
const organizationTree = computed<TreeMember[]>(() => {
    const nodes = new Map<number, TreeMember>(
        props.organization.map((member) => [member.id, { ...member, children: [] }]),
    );
    const roots: TreeMember[] = [];

    nodes.forEach((member) => {
        const parentId = member.parent_id ?? null;
        const parent = parentId === null ? undefined : nodes.get(parentId);

        if (parent) {
            parent.children.push(member);
        } else {
            roots.push(member);
        }
    });

    return roots;
});
const publicText = usePublicContentText();
</script>

<template>
    <Head :title="'Struktur organisasi — ' + school.name" />
    <main class="public-site public-organization" :style="themeStyle">
        <div class="identity-watermark" aria-hidden="true"><img v-if="school.logo_url" :src="school.logo_url" alt="" /><span v-else>{{ initial }}</span></div>
        <PublicHeader :school="school" active="organization" />

        <section class="madrasah-profile-hero page-intro" aria-labelledby="organization-title">
            <p class="eyebrow">{{ publicText("copy.vue_organization_index", "text_001", "Struktur organisasi") }}</p>
            <h1 id="organization-title">{{ publicText("copy.vue_organization_index", "text_002", "Orang-orang yang menggerakkan sekolah.") }}</h1>
            <p>{{ publicText("copy.vue_organization_index", "text_9001", "Kenali pengelola") }} {{ school.name }} {{ publicText("copy.vue_organization_index", "text_9002", "yang bekerja bersama untuk menjaga proses belajar tetap bertumbuh.") }}</p>
        </section>

        <section class="organization-tree" :aria-label="publicText('copy.vue_organization_index', 'aria_label_006', 'Bagan struktur organisasi')">
            <div v-if="organizationTree.length" class="tree-flow">
                <ul class="tree-root">
                    <PublicOrganizationTreeNode v-for="member in organizationTree" :key="member.id" :member="member" />
                </ul>
            </div>
            <div v-else class="empty-state"><strong>{{ publicText("copy.vue_organization_index", "text_003", "Struktur organisasi sedang disiapkan.") }}</strong><p>{{ publicText("copy.vue_organization_index", "text_004", "Informasi pengelola sekolah akan tampil di halaman ini.") }}</p></div>
        </section>

        <footer><Link href="/" class="back-link">{{ publicText("copy.vue_organization_index", "text_005", "← Kembali ke beranda") }}</Link></footer>
    </main>
</template>

<style scoped>
:global(body) { margin: 0; background: #fffdf8; }
:global(html[data-theme='dark']) .public-site { color: #173044 !important; background: #fffdf8 !important; }
.public-site { --ink: #173044; --muted: #6d7c82; --paper: #fffdf8; --deep: #123247; --gold: #d4a74b; min-height: 100vh; padding: 1.4rem clamp(1rem, 4vw, 3rem); color: var(--ink); background: var(--paper); font-family: var(--font-ui); }
.public-header, .page-intro, .organization-tree, footer { width: min(1120px, 100%); margin-inline: auto; }.public-header { display: flex; align-items: center; justify-content: space-between; gap: 2rem; }.brand { display: flex; align-items: center; gap: .7rem; color: var(--ink); font-size: var(--type-md); font-weight: var(--weight-bold); text-decoration: none; }.brand-mark { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; overflow: hidden; color: var(--deep); background: var(--gold); border-radius: .75rem .75rem .75rem .2rem; font-family: var(--font-display); font-size: 1.2rem; }.brand-mark img { width: 100%; height: 100%; object-fit: contain; background: #fff; }.public-header nav { display: flex; align-items: center; gap: 1.1rem; font-size: var(--type-meta); }.public-header nav a { color: var(--muted); text-decoration: none; }.public-header nav a:hover, .public-header nav a:focus-visible { color: var(--ink); }.public-header .header-action { padding: .65rem .9rem; color: var(--paper); background: var(--deep); border-radius: .45rem; font-weight: var(--weight-bold); }.page-intro { margin-top: clamp(4rem, 10vw, 8rem); }.eyebrow { margin: 0; color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }.page-intro h1 { max-width: 720px; margin: .9rem 0 1rem; font-family: var(--font-ui); font-size: clamp(2.8rem, 6vw, 5.8rem); font-weight: var(--weight-regular); letter-spacing: -.065em; line-height: .98; }.page-intro > p:last-child { max-width: 570px; margin: 0; color: var(--muted); line-height: 1.8; }.organization-tree { display: grid; gap: .8rem; margin-top: 4rem; padding-bottom: 2rem; }.organization-node { display: grid; grid-template-columns: 5px 56px minmax(0, 1fr); gap: 1rem; align-items: center; margin-left: calc(var(--depth) * 1.5rem); padding: 1.1rem 1.25rem; background: #fff; border: 1px solid #ded7c9; border-radius: .7rem; box-shadow: 0 8px 24px #183b5d0a; }.node-line { height: 100%; min-height: 58px; background: var(--gold); border-radius: 999px; }.node-avatar { display: grid; place-items: center; width: 56px; height: 56px; overflow: hidden; color: var(--deep); background: #e7d39e; border-radius: 50%; font-family: var(--font-ui); font-size: 1.4rem; }.node-avatar img { width: 100%; height: 100%; object-fit: cover; }.node-position { color: #9a7329; font-size: var(--type-meta); font-weight: var(--weight-bold); letter-spacing: .05em; text-transform: uppercase; }.organization-node h2 { margin: .25rem 0 0; font-family: var(--font-ui); font-size: 1.35rem; font-weight: var(--weight-regular); }.organization-node p { margin: .25rem 0 0; color: var(--muted); font-size: var(--type-meta); }.empty-state { padding: 4rem 1rem; color: var(--muted); text-align: center; }.empty-state strong { color: var(--ink); font-family: var(--font-ui); font-size: 1.4rem; font-weight: var(--weight-regular); }footer { margin-top: 4rem; padding: 1.4rem 0 2rem; border-top: 1px solid #cfc8ba; }.public-site a:focus-visible { outline: 3px solid #c6953e; outline-offset: 4px; border-radius: .2rem; }
@media (max-width: 700px) { .public-header { align-items: flex-start; flex-direction: column; }.public-header nav { flex-wrap: wrap; }.organization-node { margin-left: calc(var(--depth) * .6rem); } }
@media (max-width: 520px) { .public-site { padding-inline: 1rem; }.public-header nav a:nth-child(-n+2) { display: none; }.page-intro { margin-top: 4rem; }.page-intro h1 { font-size: clamp(2.6rem, 12vw, 4rem); }.organization-tree { margin-top: 3rem; }.organization-node { grid-template-columns: 4px 44px minmax(0, 1fr); gap: .75rem; padding: .9rem; }.node-avatar { width: 44px; height: 44px; }.organization-node h2 { font-size: 1.05rem; } }
</style>

<style scoped>
.organization-tree {
    display: block;
    margin-top: 4rem;
}

.tree-flow {
    padding: 1.4rem;
    background: #f8f4ec;
    border: 1px solid #ded7c9;
    border-radius: 1rem;
}

.tree-root {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.2rem;
    margin: 0;
    padding: 2rem 0 0;
    list-style: none;
}

@media (max-width: 700px) {
    .tree-flow {
        padding: 1rem;
    }

    .tree-root {
        grid-template-columns: 1fr;
        padding-top: 1rem;
    }
}
</style>
