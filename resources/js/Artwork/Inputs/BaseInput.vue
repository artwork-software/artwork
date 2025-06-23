<template>
    <div class="relative w-full">
        <input
            :id="id"
            :type="type"
            v-model="model"
            :placeholder="focused ? placeholder : ' '"
            @focus="focused = true"
            @blur="onBlur"
            :disabled="disabled"
            :required="required"
            :step="type === 'number' ? step : undefined"
            :class="[
        'peer block w-full font-lexend shadow-sm border border-gray-200 rounded-md placeholder-transparent focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
        disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
        label ? isSmall ? 'px-2 pt-3 pb-1 text-xs' : 'px-4 pt-6 pb-2 text-sm' : 'px-4 py-3 text-sm',
        type === 'number' ? 'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none' : ''
      ]"
        />

        <div v-if="model?.length > 0 && type !== 'date' && type !== 'time' && type !== 'number' && !showLoading" class="absolute right-1 top-0 bottom-0 flex items-center pr-2">
            <button
                type="button"
                @click="model = ''"
                class="text-gray-500 hover:text-artwork-messages-error transition duration-200 ease-in-out"
            >
                <component is="IconX" class="size-4" />
            </button>
        </div>

        <div v-if="model?.length > 0 && type !== 'date' && type !== 'time' && type !== 'number' && showLoading" class="absolute right-1 top-0 bottom-0 flex items-center pr-2">
            <div class="animate-spin">
                <component is="IconLoader" class="size-4 text-gray-500" />
            </div>
        </div>
        <label v-if="label"
            :for="id"
            :class="[
        'absolute text-gray-500 text-[9px] transition-all duration-300 font-lexend peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-500 peer-focus:text-[9px] peer-focus:text-artwork-buttons-create',
        isSmall
          ? 'top-0 left-2 peer-placeholder-shown:top-[7px] peer-focus:top-0'
          : 'top-1.5 peer-focus:top-1.5 left-4 peer-placeholder-shown:top-[17px]'
      ]"
        >
            {{ withoutTranslation ? label : $t(label) }}
        </label>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

// 📦 v-model compatibility using defineModel
const model = defineModel()

const props = defineProps({
    label: String,
    type: { type: String, default: 'text' },
    id: { type: String, required: true },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    isSmall: { type: Boolean, default: false },
    withoutTranslation: { type: Boolean, default: false },
    step: { type: Number, default: 1 },
    showLoading: { type: Boolean, default: false }
})

const focused = ref(false)

const onBlur = () => {
    setTimeout(() => {
        focused.value = !!model
    }, 100) // Verzögert damit Button-Klicks zuerst verarbeitet werden
}

watch(
    () => model,
    (val) => {
        focused.value = !!val
    },
    { immediate: true }
)
</script>
