<template>
    <div class="-mx-2 sm:mx-0">
        <div
            class="rounded-lg border px-3 py-3 sm:px-5 sm:py-4"
            :class="band
                ? 'border-transparent bg-surface-inverse shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]'
                : 'border-border-subtle/70 bg-surface shadow-raised'"
        >
            <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                <!-- Brand/Icon + Titel -->
                <div class="flex items-center gap-3 mr-auto min-w-0">
                    <div
                        class="size-9 rounded-lg flex items-center justify-center shrink-0"
                        :class="iconBgClass"
                    >
                        <component v-if="icon" :is="icon" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <h1
                            class="font-lexend font-bold text-2xl truncate"
                            :class="band ? 'text-text-inverse' : 'text-text'"
                        >
                            {{ $t(title) }}
                        </h1>
                        <div
                            v-if="$slots.subtitle || description"
                            class="text-xs"
                            :class="band ? 'text-text-inverse-muted' : 'text-text-muted'"
                        >
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
                        <ToolTipComponent
                            :icon="IconSearch"
                            :icon-size="band ? 'size-5' : 'size-6'"
                            :icon-color="band ? 'text-text-inverse' : ''"
                            :tooltip-text="searchTooltip"
                            direction="bottom"
                            :classes-button="band ? bandIconButtonClasses : 'ui-button'"
                        />
                    </div>

                    <div
                        v-else
                        class="w-72 sm:w-96 flex items-end justify-end gap-2"
                        :class="band ? '[&_label]:text-text-inverse-muted!' : ''"
                    >
                        <BaseInput
                            type="text"
                            ref="searchBarInput"
                            :id="searchInputId"
                            :label="searchLabel"
                            :placeholder="searchPlaceholder"
                            :input-classes="band ? 'bg-white/10! border-white/16! text-text-inverse! placeholder:text-text-inverse-muted!' : ''"
                            :model-value="modelValue"
                            @update:model-value="$emit('update:modelValue', $event)"
                        />
                        <button
                            type="button"
                            class="shrink-0 transition"
                            :class="band
                                ? 'size-[30px] mb-[1px] inline-flex items-center justify-center rounded-md bg-white/8 hover:bg-white/16'
                                : 'mb-[1px] rounded-lg border border-transparent px-1.5 py-1.5 hover:bg-surface-sunken'"
                            @click="closeSearchbar"
                            aria-label="Close search"
                        >
                            <IconX class="size-5" :class="band ? 'text-text-inverse' : 'text-text-subtle'" />
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
    iconBgClass?: string     // z.B. "bg-accent-50 text-accent-700"
    searchEnabled?: boolean  // Quick-Search anzeigen?
    modelValue?: string      // v-model für die Suche
    searchLabel?: string
    searchPlaceholder?: string
    searchTooltip?: string
    searchInputId?: string
    /** Band-Variante (Design-Basis v2 »Bühnenlicht«): dunkles Toolbar-Band auf bg-surface-inverse.
     *  Default false = bisherige helle Optik (Settings bleiben bandfrei). */
    band?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'search-opened'): void
    (e: 'search-closed'): void
}>()

const showSearchbar = ref(false)
const searchBarInput = ref<{ focus?: () => void; select?: () => void } | null>(null)

const icon = props.icon ?? null
const band = props.band ?? false
// Explizit gesetzte iconBgClass gewinnt in beiden Modi; nur der Default unterscheidet sich.
const iconBgClass = props.iconBgClass
    ?? (band ? 'bg-[rgba(48,115,174,0.35)] text-accent-200' : 'bg-accent-50 text-accent-700')
// Icon-/Aktionsbuttons auf dem Band: 30px-Kachel, weiß-transluzent (Spec §3).
// Aufrufer nutzen dieselben Klassen für eigene Icon-Buttons im actions-Slot;
// Trenner zwischen Gruppen: <span class="w-px h-5 bg-white/16" />.
const bandIconButtonClasses =
    'select-none size-[30px] min-h-0 p-0 inline-flex items-center justify-center rounded-md ' +
    'bg-white/8 hover:bg-white/16 cursor-pointer transition-[background-color] duration-150 ease-out'
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
