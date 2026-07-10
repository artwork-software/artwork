<template>
    <div>
        <h3 class="text-sm font-semibold mb-3" :class="darkText ? 'text-gray-900' : 'text-white'">{{ label }}</h3>
        <div v-if="items?.length > 0" class="grid gap-x-4 gap-y-2" :class="singleColumn ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2'">
            <label v-for="item in items"
                   :key="item.id"
                   class="flex items-center gap-3 cursor-pointer">
                <div class="group grid size-4 shrink-0 grid-cols-1">
                    <input type="checkbox"
                           :checked="modelValue.includes(item.id)"
                           @change="toggle(item.id)"
                           class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 forced-colors:appearance-auto"/>
                    <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white" viewBox="0 0 14 14" fill="none">
                        <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="flex items-baseline gap-2 text-sm truncate" :class="darkText ? 'text-gray-900' : 'text-white'">
                    <span class="truncate">{{ item.name }}</span>
                    <span v-if="item.hint" class="text-xs shrink-0" :class="darkText ? 'text-gray-400' : 'text-gray-300'">{{ item.hint }}</span>
                </span>
            </label>
        </div>
        <p v-else class="text-xs" :class="darkText ? 'text-gray-500' : 'text-gray-300'">{{ emptyText }}</p>
    </div>
</template>

<script setup>
const props = defineProps({
    label: {type: String, required: true},
    items: {type: Array, default: () => []},
    modelValue: {type: Array, default: () => []},
    emptyText: {type: String, default: ''},
    darkText: {type: Boolean, default: true},
    singleColumn: {type: Boolean, default: false},
})

const emit = defineEmits(['update:modelValue'])

const toggle = (id) => {
    emit(
        'update:modelValue',
        props.modelValue.includes(id)
            ? props.modelValue.filter((selectedId) => selectedId !== id)
            : [...props.modelValue, id]
    )
}
</script>
