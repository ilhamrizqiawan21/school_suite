<script setup lang="ts">
import { computed } from 'vue';

interface OrganizationMember {
    id: number;
    name: string;
    position: string;
    department?: string | null;
    photo_url?: string | null;
    depth: number;
    children?: OrganizationMember[];
}

const props = defineProps<{ member: OrganizationMember }>();
const initial = computed(() => props.member.name.trim().charAt(0).toUpperCase() || '?');
</script>

<template>
    <li class="tree-node">
        <article class="organization-card" :class="{ 'organization-card-lead': member.depth === 0 }">
            <div class="node-avatar">
                <img v-if="member.photo_url" :src="member.photo_url" :alt="'Foto ' + member.name" />
                <span v-else>{{ initial }}</span>
            </div>
            <div class="node-content">
                <span class="node-position">{{ member.position }}</span>
                <h2>{{ member.name }}</h2>
                <p v-if="member.department">{{ member.department }}</p>
            </div>
        </article>

        <ul v-if="member.children?.length" class="tree-children">
            <PublicOrganizationTreeNode v-for="child in member.children" :key="child.id" :member="child" />
        </ul>
    </li>
</template>

<style scoped>
.tree-node {
    position: relative;
    display: flex;
    min-width: 0;
    flex-direction: column;
    align-items: center;
}

.organization-card {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    gap: .85rem;
    align-items: center;
    width: min(330px, 100%);
    padding: 1rem 1.1rem;
    color: #173044;
    background: #fff;
    border: 1px solid #ded7c9;
    border-top: 4px solid #d4a74b;
    border-radius: .75rem;
    box-shadow: 0 12px 28px #183b5d0c;
}

.organization-card-lead {
    width: min(380px, 100%);
    border-top-color: #173044;
    box-shadow: 0 16px 34px #183b5d16;
}

.node-avatar {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    overflow: hidden;
    color: #173044;
    background: #e7d39e;
    border-radius: 50%;
    font-family: var(--font-display);
    font-size: 1.3rem;
}

.node-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.node-content {
    min-width: 0;
}

.node-position {
    display: block;
    overflow: hidden;
    color: #9a7329;
    font-size: .65rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.node-content h2 {
    margin: .25rem 0 0;
    font-family: var(--font-display);
    font-size: 1.18rem;
    font-weight: 400;
    line-height: 1.15;
}

.node-content p {
    margin: .25rem 0 0;
    color: #6d7c82;
    font-size: .74rem;
    line-height: 1.35;
}

.tree-children {
    position: relative;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.2rem;
    width: 100%;
    margin: 1.7rem 0 0;
    padding: 1.5rem 0 0;
    list-style: none;
}

.tree-children::before {
    position: absolute;
    top: -1.7rem;
    left: 50%;
    width: 2px;
    height: 1.7rem;
    background: #d8c9ad;
    content: '';
}

.tree-children::after {
    position: absolute;
    top: 0;
    right: 10%;
    left: 10%;
    border-top: 2px solid #d8c9ad;
    content: '';
}

.tree-children:has(> .tree-node:only-child)::after {
    display: none;
}

.tree-children > .tree-node::before {
    position: absolute;
    top: -1.5rem;
    left: 50%;
    width: 2px;
    height: 1.5rem;
    background: #d8c9ad;
    content: '';
}

@media (max-width: 680px) {
    .tree-children {
        grid-template-columns: 1fr;
        gap: .8rem;
        width: calc(100% - 1.2rem);
        margin-left: 1.2rem;
        padding: 0 0 0 1.2rem;
        border-left: 2px solid #d8c9ad;
    }

    .tree-children::before,
    .tree-children::after {
        display: none;
    }

    .tree-children > .tree-node {
        align-items: stretch;
    }

    .tree-children > .tree-node::before {
        top: 50%;
        left: -1.2rem;
        width: 1.2rem;
        height: 2px;
        background: #d8c9ad;
    }
}
</style>
