<template>
    <div v-if="!roomMode" class="inline-flex items-center">
        <!-- v-tooltip (PrimeVue-Direktive) braucht ein echtes DOM-Element — auf der
             Headless-UI-Switch-Komponente wird die Direktive nicht angewendet.
             Deshalb trägt dieser Wrapper-Span den Tooltip (wie in SwitchIconTooltip).
             :key erzwingt beim Umschalten ein Neu-Mounten des Wrappers: der offene
             Tooltip wird sauber entfernt statt mit dem neuen (längeren) Text an der
             alten Position weiterzuleben — PrimeVue positioniert bei reaktiven
             Text-Updates nicht neu, der Tooltip hing dann verschoben im Kalender. -->
        <span class="inline-flex" :key="multiEdit ? 'multi-edit-on' : 'multi-edit-off'" v-tooltip.bottom="tooltipBinding">
        <Switch
            v-model="model"
            :aria-label="tooltipText"
            :class="[
        model ? 'bg-accent-600/95 hover:bg-accent-600/95' : 'bg-border',
        'relative inline-flex h-7 w-14 cursor-pointer rounded-full p-0.5 transition-colors duration-300 ease-out',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
        'shadow-inner'
      ]"
        >
            <span class="sr-only">{{ tooltipText }}</span>

            <!-- Knopf -->
            <span
                aria-hidden="true"
                :class="[
          model ? 'translate-x-7' : 'translate-x-0',
          'inline-flex h-6 w-6 transform items-center justify-center rounded-full bg-white ring-1 ring-black/5 shadow transition duration-300 ease-out'
        ]"
            >
                <component
                    :is="model ? IconChecks : IconPencil"
                    class="h-4 w-4 text-text-muted"
                    stroke-width="1.5"
                    aria-hidden="true"
                />
      </span>
        </Switch>
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Switch } from '@headlessui/vue'
import { IconPencil, IconChecks } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    multiEdit: { type: Boolean, required: true },
    roomMode: { type: Boolean, default: false }
})

const emit = defineEmits(['update:multiEdit'])

const tooltipText = computed(() => props.multiEdit
    ? $t('Multi-edit active: select events to edit or delete them together. Click to leave multi-edit.')
    : $t('Multi-edit: select multiple events to edit or delete them together.'))

const tooltipBinding = computed(() => ({
    value: tooltipText.value,
    appendTo: 'body',
    useTranslation: false,
    class: 'aw-tooltip'
}))

/** v-model Proxy */
const model = computed({
    get: () => props.multiEdit,
    set: (val) => emit('update:multiEdit', val)
})
</script>

<style scoped>
/* optional: weicher Mini-Shadow ist schon per utility drin */
</style>
