<template>
    <transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="visible" class="pointer-events-auto w-full max-w-md">
            <div
                class="flex items-start gap-3 rounded-xl border border-border-subtle bg-white p-3 shadow-lg ring-0 cursor-pointer"
                @click="$emit('click')"
            >
                <img class="size-10 rounded-full object-cover shrink-0" :src="avatar" alt="Avatar" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-text truncate">{{ name }}</p>
                        <span class="ml-3 text-[9px] text-text-subtle shrink-0">{{ time }}</span>
                    </div>
                    <div class="">
                        <div class="inline-block max-w-full">
                            <p class="text-xs break-words">{{ message }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-1 text-xs font-medium text-accent-600 hover:text-accent-700"
                        @click.stop="$emit('click')"
                    >
                        Öffnen
                    </button>
                </div>
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
    time: {
        type: String,
        default: ''
    }
})

const visible = ref(true)

onMounted(() => {
    setTimeout(() => {
        visible.value = false
    }, 5000) // 5 Sekunden sichtbar
})
</script>
