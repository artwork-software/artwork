<template>
    <div class="w-full my-4">
        <div class="overflow-x-auto">
            <nav class="flex gap-2">
                <template v-for="tab in tabs" :key="tab.name">
                    <component
                        v-if="tab.permission"
                        :is="tabTag"
                        :href="navigationMode === 'links' ? tab.href : undefined"
                        :type="navigationMode === 'buttons' ? 'button' : undefined"
                        :aria-current="tab.current ? 'page' : undefined"
                        :class="[
                            tab.current
                                ? 'bg-surface border-border border-b-accent-600 text-text'
                                : 'border-transparent border-b-transparent text-text-muted hover:bg-surface-sunken hover:text-text',
                            'inline-flex h-[30px] items-center gap-2 whitespace-nowrap rounded-t-md border border-b-2 px-3 text-[13px] font-medium cursor-pointer transition-colors duration-150 motion-reduce:transition-none'
                        ]"
                        @click="onTabActivate(tab)"
                    >
                        <PropertyIcon v-if="tab.icon" :name="tab.icon" class="size-4" stroke-width="1.5" />
                        {{ useTranslation ? $t(tab.name) : tab.name }}
                        <span
                            v-if="tab.count"
                            :class="[
                                tab.current ? 'bg-accent-50 text-accent-700' : 'bg-surface-sunken text-text-muted',
                                'ml-2 hidden rounded-full px-2 py-0.5 text-xs font-medium md:inline-block'
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
