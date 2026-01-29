<template>
    <div class="relative" v-bind="$attrs">
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="focused ? placeholder : (hasLabel ? ' ' : placeholder)"
            @input="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true"
            @blur="focused = !!modelValue"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            class="peer block w-full shadow-sm border border-gray-200 rounded-md px-4 pt-6 pb-2 text-sm placeholder-transparent font-lexend focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create resize-none"
            :class="disabled ? 'bg-gray-100 cursor-not-allowed' : bgColor"
        />
        <label v-if="hasLabel" :for="id" class="absolute left-4 top-1.5 text-gray-500 text-xs transition-all duration-300 peer-placeholder-shown:top-[18px] font-lexend peer-placeholder-shown:text-xs peer-placeholder-shown:text-gray-500 peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-artwork-buttons-create">
            {{ $t(label) }}
        </label>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

defineOptions({
    inheritAttrs: false
})

const props = defineProps({
    modelValue: String,
    label: { type: String, default: '' },
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
    },
    bgColor: {
        type: String,
        default: 'bg-white'
    }
})

const emit = defineEmits(['update:modelValue'])

const focused = ref(false)

const hasLabel = computed(() => typeof props.label === 'string' && props.label.trim().length > 0)

watch(
    () => props.modelValue,
    (val) => {
        focused.value = !!val
    },
    { immediate: true }
)
</script>
