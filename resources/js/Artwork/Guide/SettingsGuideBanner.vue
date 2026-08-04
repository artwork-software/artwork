<template>
    <!-- sidebar: fixed card for side columns, never collapsible -->
    <div v-if="variant === 'sidebar'" class="rounded-2xl border border-accent-100 bg-accent-50 p-5">
        <div class="flex items-center gap-2 mb-3">
            <PropertyIcon :name="icon" class="h-5 w-5 text-accent-600 shrink-0" />
            <span class="text-sm font-semibold text-text">{{ $t(title) }}</span>
        </div>
        <div class="space-y-2">
            <p v-for="(paragraph, index) in paragraphs" :key="index" class="text-xs text-text-muted">{{ $t(paragraph) }}</p>
        </div>
        <slot />
        <p v-if="footnote" class="mt-3 text-xs text-text-subtle">{{ $t(footnote) }}</p>
    </div>

    <!-- static: non-collapsible box, for modals and short in-place notes -->
    <div v-else-if="variant === 'static'" class="rounded-xl border border-accent-100 bg-accent-50 px-4 py-3">
        <div class="flex gap-x-3">
            <PropertyIcon :name="icon" class="h-5 w-5 text-accent-600 shrink-0 mt-0.5" />
            <div class="min-w-0">
                <p v-if="title" class="text-sm font-semibold text-text mb-1">{{ $t(title) }}</p>
                <div class="space-y-1.5">
                    <p v-for="(paragraph, index) in paragraphs" :key="index" class="text-xs text-text-muted">{{ $t(paragraph) }}</p>
                </div>
                <slot />
                <p v-if="footnote" class="mt-2 text-xs text-text-subtle">{{ $t(footnote) }}</p>
            </div>
        </div>
    </div>

    <!-- banner (tab level) / inline (card level): collapsible, state remembered per browser -->
    <div v-else class="rounded-2xl border border-accent-100 bg-accent-50" :class="variant === 'inline' ? 'rounded-xl' : ''">
        <button
            type="button"
            class="w-full flex items-center gap-3 text-left"
            :class="variant === 'inline' ? 'px-4 py-2.5' : 'px-5 py-4'"
            @click="toggleCollapsed"
        >
            <PropertyIcon :name="icon" class="text-accent-600 shrink-0" :class="variant === 'inline' ? 'h-4 w-4' : 'h-5 w-5'" />
            <span class="font-semibold text-text" :class="variant === 'inline' ? 'text-xs' : 'text-sm'">{{ $t(title) }}</span>
            <PropertyIcon
                name="IconChevronDown"
                class="h-4 w-4 text-text-subtle ml-auto transition-transform shrink-0"
                :class="collapsed ? '' : 'rotate-180'"
            />
        </button>
        <div v-if="!collapsed" :class="variant === 'inline' ? 'px-4 pb-3' : 'px-5 pb-5'">
            <div v-if="steps.length" class="grid gap-3" :class="stepGridClass">
                <div v-for="(step, index) in steps" :key="index" class="rounded-xl bg-surface border border-border-subtle p-4 flex flex-col">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="flex items-center justify-center size-6 rounded-full bg-accent-600 text-white text-xs font-bold shrink-0">{{ index + 1 }}</span>
                        <span class="text-sm font-medium text-text">{{ $t(step.title) }}</span>
                    </div>
                    <p class="text-xs text-text-subtle grow">{{ $t(step.text) }}</p>
                    <button
                        v-if="step.action"
                        type="button"
                        class="mt-3 text-xs font-medium text-accent-600 hover:text-accent-700 text-left flex items-center gap-1"
                        @click="step.action()"
                    >
                        <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" />
                        {{ $t(step.actionLabel) }}
                    </button>
                    <Link
                        v-else-if="step.href"
                        :href="step.href"
                        class="mt-3 text-xs font-medium text-accent-600 hover:text-accent-700 flex items-center gap-1"
                    >
                        <PropertyIcon name="IconArrowRight" class="h-3.5 w-3.5" />
                        {{ $t(step.actionLabel) }}
                    </Link>
                </div>
            </div>
            <div v-if="paragraphs.length" class="space-y-1.5" :class="steps.length ? 'mt-3' : ''">
                <p v-for="(paragraph, index) in paragraphs" :key="index" class="text-xs text-text-muted">{{ $t(paragraph) }}</p>
            </div>
            <slot />
            <p v-if="footnote" class="mt-3 text-xs text-text-subtle">{{ $t(footnote) }}</p>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from 'vue'
import {Link} from '@inertiajs/vue3'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    /**
     * banner  – collapsible tab-level guide below the tab navigation (default)
     * inline  – compact collapsible guide inside a card / section
     * static  – non-collapsible box, e.g. inside modals
     * sidebar – non-collapsible card for side columns
     */
    variant: {
        type: String,
        default: 'banner',
        validator: (value) => ['banner', 'inline', 'static', 'sidebar'].includes(value),
    },
    /**
     * Full localStorage key remembering the collapsed state per browser
     * (convention: 'settings-guide.<area>.<tab>'). '1' = collapsed.
     * Without a key the state is not persisted. Default is expanded.
     */
    storageKey: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: 'IconInfoCircle',
    },
    /**
     * Numbered workflow steps: [{ title, text, actionLabel?, action?|href? }]
     * action is a callback (e.g. opens a modal), href an Inertia target.
     */
    steps: {
        type: Array,
        default: () => [],
    },
    /** Explanatory paragraphs, rendered below the steps (or alone). */
    paragraphs: {
        type: Array,
        default: () => [],
    },
    /** Small print for restrictions/exceptions. */
    footnote: {
        type: String,
        default: null,
    },
})

const collapsed = ref(
    props.storageKey ? localStorage.getItem(props.storageKey) === '1' : false
)

const toggleCollapsed = () => {
    collapsed.value = !collapsed.value
    if (props.storageKey) {
        localStorage.setItem(props.storageKey, collapsed.value ? '1' : '0')
    }
}

const stepGridClass = computed(() => {
    switch (props.steps.length) {
        case 2:
            return 'md:grid-cols-2'
        case 3:
            return 'md:grid-cols-3'
        default:
            return 'md:grid-cols-4'
    }
})
</script>
