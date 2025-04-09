<template>
    <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" class="pointer-events-auto flex w-full max-w-md rounded-lg bg-white ring-1 shadow-lg ring-black/5">
            <div class="w-0 flex-1 p-4">
                <div class="flex items-start">
                    <div class="shrink-0 pt-0.5">
                        <img class="size-10 rounded-full object-cover" :src="avatar" alt="Avatar" />
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900 font-lexend">{{ name }}</p>
                        <p class="mt-1 text-xs text-gray-500 font-lexend">{{ message }}</p>
                    </div>
                </div>
            </div>
            <div class="flex border-l border-gray-200">
                <button @click="$emit('click')" class="flex w-full items-center justify-center rounded-none rounded-r-lg border border-transparent p-4 text-sm text-artwork-buttons-create hover:text-artwork-buttons-hover font-lexend">
                    Öffnen
                </button>
            </div>
        </div>
    </transition>
</template>

<script setup>
import {ref, onMounted} from 'vue'

const props = defineProps({
    avatar: String,
    name: String,
    message: String,
})
const visible = ref(true)

onMounted(() => {
    setTimeout(() => {
        visible.value = false
    }, 5000) // 5 Sekunden sichtbar
})
</script>
