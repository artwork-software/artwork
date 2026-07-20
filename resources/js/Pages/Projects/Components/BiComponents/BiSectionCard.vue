<template>
    <section class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
            @click="open = !open"
        >
            <span class="flex items-center gap-2 min-w-0">
                <component :is="icon" v-if="icon" class="size-4 text-gray-400 shrink-0" />
                <span class="text-sm font-semibold text-gray-900 truncate">{{ title }}</span>
                <span
                    v-if="completeness"
                    class="rounded-full px-2 py-0.5 text-[10px] font-medium whitespace-nowrap"
                    :class="completeness.filled >= completeness.total
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-amber-100 text-amber-700'"
                >
                    {{ completeness.filled }}/{{ completeness.total }} {{ $t('filled in') }}
                </span>
            </span>
            <IconChevronDown
                class="size-4 text-gray-400 transition-transform shrink-0 print:hidden"
                :class="{ '-rotate-90': !open }"
            />
        </button>
        <div v-show="open" class="px-4 pb-4 print:!block">
            <slot />
        </div>
    </section>
</template>

<script setup>
import { ref } from 'vue';
import { IconChevronDown } from '@tabler/icons-vue';

const props = defineProps({
    title: { type: String, required: true },
    icon: { type: [Object, Function], default: null },
    // { filled: number, total: number } | null
    completeness: { type: Object, default: null },
    defaultOpen: { type: Boolean, default: true },
});

const open = ref(props.defaultOpen);

// Erlaubt dem Datenqualitäts-Banner, die Karte gezielt aufzuklappen
defineExpose({
    expand: () => { open.value = true; },
});
</script>
