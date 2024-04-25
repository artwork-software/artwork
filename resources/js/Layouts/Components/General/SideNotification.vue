<script>
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "SideNotification",
    emits: ['close'],
    mixins: [IconLib],
    data() {
        return {
            show: true,
        }
    },
    props: ['text', 'type'],
    methods: {
        close() {
            this.$emit('close');
        }
    }
}
</script>

<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 mt-10 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
            <transition enter-active-class="transform ease-out duration-300 transition" enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="show" class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <IconCircleX stroke-width="1.5" class="h-6 w-6 text-red-500" aria-hidden="true" v-if="type === 'error'" />
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5 text-sm">
                               {{ text }}
                            </div>
                            <div class="ml-4 flex flex-shrink-0">
                                <button type="button" @click="close" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<style scoped>

</style>
