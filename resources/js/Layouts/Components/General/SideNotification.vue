<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 mt-10 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
            <transition enter-active-class="transition-enter-active"
                        enter-from-class="transition-enter-from"
                        enter-to-class="transition-enter-to"
                        leave-active-class="transition-leave-active"
                        leave-from-class="transition-leave-from"
                        leave-to-class="transition-leave-to">
                <div v-if="show" class="pointer-events-auto w-full max-w-sm overflow-hidden card glassy p-4">
                    <div class="card white">
                        <div class="p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <IconCircleX stroke-width="1.5" class="h-6 w-6 text-red-500" aria-hidden="true" v-if="type === 'error'" />
                                    <IconGeometry stroke-width="1.5" class=" w-8 h-8 border bg-gray-100 rounded-full p-0.5" v-if="type === 'project_create_success'"/>
                                </div>
                                <div class="ml-3 w-0 flex-1 pt-0.5 text-sm">
                                    <template v-if="type === 'project_create_success'">
                                        {{ $t('Project created successfully') }}
                                    </template>
                                    <template v-else>
                                        {{ text }}
                                    </template>
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
                </div>
            </transition>
        </div>
    </div>
</template>

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
        },
    }
}
</script>


