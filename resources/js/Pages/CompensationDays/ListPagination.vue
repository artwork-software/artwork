<template>
    <div v-if="paginator && paginator.last_page > 1" class="mt-3 flex items-center justify-between">
        <div class="text-[11px] text-text-subtle tabular-nums">
            {{ paginator.from ?? 0 }} – {{ paginator.to ?? 0 }} {{ $t('of') }} {{ paginator.total }}
        </div>
        <div class="flex items-center gap-1.5">
            <button
                v-for="(link, index) in links"
                :key="index"
                type="button"
                class="rounded px-2.5 py-1 text-[11px] font-medium transition-colors"
                :class="link.active ? 'bg-accent-700 text-white'
                    : link.url
                        ? 'bg-surface-sunken text-text-muted hover:bg-border-subtle'
                        : 'bg-surface-sunken text-text-subtle cursor-not-allowed'"
                :disabled="!link.url || link.active"
                @click="emitPage(link)"
                v-html="link.label"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Kleine Seitennavigation für die drei Dashboard-Listen (offen/überfällig/gewährt): liest die
 * Seitennummer aus dem Paginator-Link (sprachneutral) und meldet sie nach oben — die Seite
 * hängt sie unter ihrem eigenen Query-Parameter (z. B. open_page) an, damit die Listen
 * unabhängig blättern und die Filter erhalten bleiben.
 */
const props = defineProps({
    // Laravel LengthAwarePaginator (data, links, current_page, last_page, from, to, total)
    paginator: { type: Object, required: true },
    // Query-Parameter dieser Liste (z. B. 'open_page'); die Links tragen alle Seitenparameter
    pageName: { type: String, default: 'page' },
});

const emit = defineEmits(['page']);

const links = computed(() => (props.paginator?.links || []).filter((link) => link.url || link.active));

function emitPage(link) {
    if (!link.url) return;
    try {
        const url = new URL(link.url, window.location.origin);
        const pageParam = url.searchParams.get(props.pageName);
        const page = pageParam !== null ? Number(pageParam) : null;
        if (page && !Number.isNaN(page)) {
            emit('page', page);
        }
    } catch (e) {
        // unbrauchbare URL: nichts tun
    }
}
</script>
