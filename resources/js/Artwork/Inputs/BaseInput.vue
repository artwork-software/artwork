<template>
    <div class="relative w-full">
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="focused ? placeholder : ' '"
            @input="$emit('update:modelValue', $event.target.value)"
            @focus="focused = true"
            @blur="focused = !!modelValue"
            :disabled="disabled"
            :required="required"
            :class="['peer block w-full shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
                       disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
                       isSmall ? 'px-2 pt-3 pb-1 text-xs' : 'px-4 pt-6 pb-2 text-sm',
                       type === 'number' ? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none' : ''
            ]"
        />
        <div v-if="modelValue?.length > 0 && type !== 'date' && type !== 'time'" class="absolute right-1 top-0 bottom-0 flex items-center pr-2">
            <button @click="$emit('update:modelValue', '')" class="text-gray-500 hover:text-artwork-messages-error transition duration-200 ease-in-out">
                <component is="IconX" class="size-4" />
            </button>
        </div>
        <label :for="id" :class="isSmall ? 'top-0 left-2 peer-placeholder-shown:top-[7px] peer-focus:top-0 ' : 'top-1.5 peer-focus:top-1.5 left-4 peer-placeholder-shown:top-[17px]'" class="absolute text-gray-500 text-xs transition-all duration-300 peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-500 peer-focus:text-xs peer-focus:text-artwork-buttons-create">
            {{ withoutTranslation ? label : $t(label) }}
        </label>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: String,
    label: String,
    type: {
        type: String,
        default: 'text'
    },
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
        default: false,
        required: false
    },
    required: {
        type: Boolean,
        default: false
    },
    isSmall: {
        type: Boolean,
        default: false
    },
    withoutTranslation: {
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
