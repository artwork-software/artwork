<template>
    <div class="w-full my-4">
        <div class="overflow-x-auto">
            <!-- v2 »Bühnenlicht«: Segmented Control statt Unterstrich-Tabs -->
            <nav class="inline-flex gap-[2px] rounded-[8px] bg-border-subtle p-[3px]">
                <template v-for="tab in tabs" :key="tab.name">
                    <component
                        v-if="tab.permission"
                        :is="tabTag"
                        :href="navigationMode === 'links' ? tab.href : undefined"
                        :type="navigationMode === 'buttons' ? 'button' : undefined"
                        :aria-current="tab.current ? 'page' : undefined"
                        :class="[
                            tab.current
                                ? 'bg-surface shadow-raised text-text'
                                : 'text-text hover:bg-white/60',
                            'inline-flex h-[26px] items-center gap-2 whitespace-nowrap rounded-[6px] px-3 text-[12.5px] font-semibold cursor-pointer',
                            'transition-[background-color] duration-150 ease-out motion-reduce:transition-none',
                            'focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600'
                        ]"
                        @click="onTabActivate(tab)"
                    >
                        <PropertyIcon v-if="tab.icon" :name="tab.icon" class="size-4" stroke-width="1.5" />
                        {{ useTranslation ? $t(tab.name) : tab.name }}
                        <span
                            v-if="tab.count"
                            :class="[
                                tab.current ? 'bg-accent-50 text-accent-700' : 'bg-white/70 text-text-muted',
                                'hidden rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums md:inline-block'
                            ]"
                        >{{ tab.count }}</span>
                    </component>
                </template>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
    navigationMode: {
        type: String,
        default: 'links',
        validator: (value) => ['links', 'buttons', 'events'].includes(value),
    },
    useTranslation: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['tab-click', 'tab-select'])

const tabTag = computed(() => {
    if (props.navigationMode === 'links') return Link
    if (props.navigationMode === 'buttons') return 'button'
    return 'div'
})

function onTabActivate(tab) {
    if (props.navigationMode === 'buttons') {
        emit('tab-click', tab)
    } else if (props.navigationMode === 'events') {
        emit('tab-select', tab)
    }
    // links: Inertia <Link> handles navigation itself
}
</script>
