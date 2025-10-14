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
                    <button
                        v-if="!showSearchbar"
                        @click="openSearchbar"
                        type="button"
                        aria-label="Search"
                    >
                        <ToolTipComponent :icon="IconSearch" icon-size="h-6 w-6" :tooltip-text="searchTooltip" direction="bottom" classes-button="ui-button"/>
                    </button>

                    <div v-else class="w-72 sm:w-96 flex items-center justify-end gap-2">
                        <BaseInput
                            type="text"
                            ref="searchBarInput"
                            :id="searchInputId"
                            :label="searchLabel"
                            :model-value="modelValue"
                            @update:model-value="$emit('update:modelValue', $event)"
                        />
                        <button
                            type="button"
                            class="shrink-0 rounded-xl border border-transparent px-1.5 py-1.5 hover:bg-zinc-100 transition"
                            @click="closeSearchbar"
                            aria-label="Close search"
                        >
                            <IconX class="h-6 w-6 text-zinc-500" />
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
import { ref, onMounted, nextTick } from 'vue'

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
    searchTooltip?: string
    searchInputId?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'search-opened'): void
    (e: 'search-closed'): void
}>()

const showSearchbar = ref(false)
const searchBarInput = ref<HTMLInputElement | null>(null)

const icon = props.icon ?? null
const iconBgClass = props.iconBgClass ?? 'bg-blue-600/10 text-blue-700'
const searchInputId = props.searchInputId ?? 'toolbar-search'
const searchLabel = props.searchLabel ?? 'Search'
const searchTooltip = props.searchTooltip ?? 'Search'
const searchEnabled = props.searchEnabled ?? true

function openSearchbar() {
    showSearchbar.value = true
    nextTick(() => {
        // Fokus ins Input, falls vorhanden
        (searchBarInput.value as any)?.focus?.()
    })
    emit('search-opened')
}

function closeSearchbar() {
    showSearchbar.value = false
    emit('search-closed')
}

onMounted(() => {
    // kein Auto-Open, aber Platz für zukünftige Logik
})
</script>
