<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });
const props = defineProps<{ title?: string }>();
const page = usePage();
const metadata = computed(() => {
    const blocks = (page.props.contentBlocks ?? page.props.publicContentBlocks ?? {}) as Record<string, Record<string, string>>;
    return blocks['seo.current'] ?? {};
});
const title = computed(() => metadata.value.title || props.title || '');
const preview = computed(() => Boolean(page.props.isDraftPreview) || new URL(page.url, 'https://preview.invalid').searchParams.get('preview_draft') === '1');
</script>

<template>
    <Head :title="title">
        <meta v-if="metadata.description" head-key="description" name="description" :content="metadata.description" />
        <meta v-if="title" head-key="og:title" property="og:title" :content="title" />
        <meta v-if="metadata.description" head-key="og:description" property="og:description" :content="metadata.description" />
        <meta v-if="metadata.image_url" head-key="og:image" property="og:image" :content="metadata.image_url" />
        <link v-if="metadata.canonical_url && !preview" head-key="canonical" rel="canonical" :href="metadata.canonical_url" />
        <meta v-if="preview" head-key="robots" name="robots" content="noindex, nofollow" />
        <slot />
    </Head>
</template>
