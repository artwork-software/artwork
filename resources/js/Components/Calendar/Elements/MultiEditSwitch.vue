<template>
    <div v-if="!roomMode" class="inline-flex items-center">
        <Switch
            v-model="model"
            :class="[
        model ? 'bg-blue-600 hover:bg-blue-600/95' : 'bg-gray-200',
        'relative inline-flex h-7 w-14 cursor-pointer rounded-full p-0.5 transition-colors duration-300 ease-out',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500',
        'shadow-inner'
      ]"
        >
            <span class="sr-only">Multi-Edit</span>

            <!-- Knopf -->
            <span
                aria-hidden="true"
                :class="[
          model ? 'translate-x-7' : 'translate-x-0',
          'inline-flex h-6 w-6 transform items-center justify-center rounded-full bg-white ring-1 ring-black/5 shadow transition duration-300 ease-out'
        ]"
            >
        <!-- Tooltip-Komponente bleibt -->
        <ToolTipComponent
            direction="bottom"
            :tooltip-text="model ? ($t?.('Multi-Edit aktiv') ?? 'Multi-Edit aktiv') : ($t?.('Edit') ?? 'Edit')"
            :icon="model ? IconChecks : IconPencil"
            icon-size="h-4 w-4"
        />
      </span>
        </Switch>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Switch } from '@headlessui/vue'
import { IconPencil, IconChecks } from '@tabler/icons-vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

const props = defineProps({
    multiEdit: { type: Boolean, required: true },
    roomMode: { type: Boolean, default: false }
})

const emit = defineEmits(['update:multiEdit'])

/** v-model Proxy */
const model = computed({
    get: () => props.multiEdit,
    set: (val) => emit('update:multiEdit', val)
})
</script>

<style scoped>
/* optional: weicher Mini-Shadow ist schon per utility drin */
</style>
