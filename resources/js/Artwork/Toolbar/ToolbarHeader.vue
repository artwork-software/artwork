<template>
    <div class="-mx-2 sm:mx-0">
        <div class="rounded-2xl border border-zinc-200/70 bg-white/85 backdrop-blur px-3 py-3 sm:px-5 sm:py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                <!-- Brand/Icon + Titel -->
                <div class="flex items-center gap-3 mr-auto min-w-0">
                    <div
                        class="size-9 rounded-xl flex items-center justify-center shrink-0"
                        :class="iconBgClass"
                    >
                        <component v-if="icon" :is="icon" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-zinc-900 text-xl sm:text-2xl font-semibold tracking-tight truncate">
                            {{ $t(title) }}
                        </div>
                        <div v-if="$slots.subtitle || description" class="text-xs text-zinc-500">
                            <slot name="subtitle">
                                {{ $t(description) }}
                            </slot>
                        </div>
                    </div>
                </div>

                <!-- Quick-Search (Icon -> Input) -->
                <div v-if="searchEnabled" class="relative flex items-center gap-2">
                    <!-- Wichtig: kein <button> um ToolTipComponent herum, da ToolTipComponent selbst ein <button> rendert (sonst verschachtelte Buttons) -->
                    <div
                        v-if="!showSearchbar"
                        class="inline-flex"
                        role="button"
                        tabindex="0"
                        aria-label="Search"
                        @click="openSearchbar"
                        @keydown.enter.prevent="openSearchbar"
                        @keydown.space.prevent="openSearchbar"
                    >
                        <ToolTipComponent :icon="IconSearch" icon-size="size-6" :tooltip-text="searchTooltip" direction="bottom" classes-button="ui-button"/>
                    </div>

                    <div v-else class="w-72 sm:w-96 flex items-center justify-end gap-2">
                        <BaseInput
                            type="text"
                            ref="searchBarInput"
                            :id="searchInputId"
                            :label="searchLabel"
                            :placeholder="searchPlaceholder"
                            :model-value="modelValue"
                            @update:model-value="$emit('update:modelValue', $event)"
                        />
                        <button
                            type="button"
                            class="shrink-0 rounded-xl border border-transparent px-1.5 py-1.5 hover:bg-zinc-100 transition"
                            @click="closeSearchbar"
                            aria-label="Close search"
                        >
                            <IconX class="size-5 text-zinc-500" />
                        </button>
                    </div>
                </div>

                <!-- Actions Slot (Filter, Sort, Buttons, …) -->
                <slot name="actions" />
            </div>

            <slot name="extra" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue'

// Externe UI/Icons aus deinem Projekt
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { IconSearch, IconX } from '@tabler/icons-vue'
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

type IconType = any

const props = defineProps<{
    title: string
    description?: string
    icon?: IconType
    iconBgClass?: string     // z.B. "bg-blue-600/10 text-blue-700"
    searchEnabled?: boolean  // Quick-Search anzeigen?
    modelValue?: string      // v-model für die Suche
    searchLabel?: string
    searchPlaceholder?: string
    searchTooltip?: string
    searchInputId?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'search-opened'): void
    (e: 'search-closed'): void
}>()

const showSearchbar = ref(false)
const searchBarInput = ref<{ focus?: () => void; select?: () => void } | null>(null)

const icon = props.icon ?? null
const iconBgClass = props.iconBgClass ?? 'bg-blue-600/10 text-blue-700'
const searchInputId = props.searchInputId ?? 'toolbar-search'
const searchLabel = props.searchLabel ?? 'Search'
const searchPlaceholder = props.searchPlaceholder ?? ''
const searchTooltip = props.searchTooltip ?? 'Search'
const searchEnabled = props.searchEnabled ?? true

function openSearchbar() {
    showSearchbar.value = true
    emit('search-opened')
}

function focusSearchInput() {
    // nextTick reicht oft, aber durch Tooltips/Overlays/DOM-Reflow kann der Fokus sonst „verloren“ gehen.
    nextTick(() => {
        requestAnimationFrame(() => {
            searchBarInput.value?.focus?.()
            // Optional: direkt selektieren, falls schon Text drin ist
            searchBarInput.value?.select?.()
        })
    })
}

watch(showSearchbar, (isOpen) => {
    if (isOpen) focusSearchInput()
})

function closeSearchbar() {
    showSearchbar.value = false
    // Beim Schließen über das äußere X ebenfalls den Suchstring leeren,
    // damit die Eltern-Komponente wieder die vollständige Liste lädt.
    emit('update:modelValue', '')
    emit('search-closed')
}

onMounted(() => {
    // kein Auto-Open, aber Platz für zukünftige Logik
})
</script>
