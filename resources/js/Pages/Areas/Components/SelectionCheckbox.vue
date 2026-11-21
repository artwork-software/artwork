<template>
    <!-- Auswahlkästchen für Listenzeilen; "indeterminate" zeigt eine Teilauswahl -->
    <label class="group grid size-4 shrink-0 cursor-pointer grid-cols-1" :title="label">
        <input type="checkbox"
               :checked="checked"
               :indeterminate.prop="indeterminate"
               :aria-label="label"
               @change="$emit('toggle')"
               class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-white checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 forced-colors:appearance-auto"/>
        <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
            <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </label>
</template>

<script setup>
defineOptions({ name: 'SelectionCheckbox' })

defineProps({
    checked: { type: Boolean, default: false },
    indeterminate: { type: Boolean, default: false },
    label: { type: String, default: '' },
})

defineEmits(['toggle'])
</script>
