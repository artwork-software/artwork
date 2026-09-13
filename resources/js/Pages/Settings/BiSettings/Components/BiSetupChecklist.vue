<template>
    <section class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h3 class="text-sm font-semibold text-text">{{ $t('Setup checklist') }}</h3>
                <p class="text-xs text-text-subtle">
                    {{ $t('Everything the BI module needs, in one place — status is read live.') }}
                </p>
            </div>
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="openItems === 0 ? 'bg-success-surface text-success' : 'bg-warning-surface text-warning'"
            >
                {{ openItems === 0 ? $t('Everything set up') : `${openItems} ${$t('open')}` }}
            </span>
        </div>

        <ol class="divide-y divide-border-subtle">
            <li v-for="item in items" :key="item.key" class="flex items-start gap-3 py-3">
                <span
                    class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
                    :class="statusClass(item)"
                >
                    <IconCheck v-if="item.done" class="size-3.5" />
                    <IconMinus v-else-if="item.soft" class="size-3.5" />
                    <IconAlertTriangle v-else class="size-3.5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-text">{{ $t(item.title) }}</p>
                    <p class="text-xs text-text-subtle">{{ $t(item.detail) }}</p>
                </div>
                <Link
                    v-if="item.href && item.actionLabel"
                    :href="item.href"
                    class="shrink-0 text-xs font-medium text-accent-600 hover:underline flex items-center gap-1"
                >
                    {{ $t(item.actionLabel) }}
                    <IconArrowRight class="size-3.5" />
                </Link>
            </li>
        </ol>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { IconCheck, IconMinus, IconAlertTriangle, IconArrowRight } from '@tabler/icons-vue';

const props = defineProps({
    // [{ key, title, done, detail, href, actionLabel, soft? }] — aus BiComponentSettingsController::setupChecklist()
    items: { type: Array, default: () => [] },
});

const openItems = computed(() => props.items.filter(item => !item.done && !item.soft).length);

// grün = erledigt, grau = teilweise (z. B. nicht jeder Raum hat Publikum), gelb = offen
const statusClass = (item) => {
    if (item.done) return 'bg-success-surface text-success';
    if (item.soft) return 'bg-surface-sunken text-text-subtle';
    return 'bg-warning-surface text-warning';
};
</script>
