<template>
    <div class="relative">
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="focused ? placeholder : ' '"
            @input="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true"
            @blur="focused = !!modelValue"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            class="peer block w-full shadow-sm border border-gray-200 rounded-md px-4 pt-6 pb-2 text-sm placeholder-transparent font-lexend focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create resize-none"
            :class="disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'"
        />
        <label :for="id" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all duration-300 peer-placeholder-shown:top-[18px] font-lexend peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-500 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-artwork-buttons-create">
            {{ $t(label) }}
        </label>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: String,
    label: String,
    id: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    rows: {
        type: [Number, String],
        default: 4
    },
    required: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:modelValue'])

const focused = ref(false)

watch(
    () => props.modelValue,
    (val) => {
        focused.value = !!val
    },
    { immediate: true }
)
</script>
