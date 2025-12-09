<template>
    <transition
        enter-active-class="duration-300 ease-out"
        enter-from-class="transform opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="transform opacity-0"
    >
        <div class="rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="shrink-0">
                    <PropertyIcon name="IconXboxX" class="size-5 text-red-400" aria-hidden="true" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ $t('Unfortunately there were {0} errors during transmission.', [errorCount])}}</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            <li v-for="error in errors">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>

import {IconXboxX} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    errors: {
        type: [Object, Array],
        required: true
    },
    uploadErrorMessages: {
        type: Boolean,
        required: false,
        default: false
    },
    errorCount: {
        type: Number,
        required: false,
        default: 0
    }
})

</script>

<style scoped>

</style>
