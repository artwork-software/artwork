<template>
    <div :id="modal_id" aria-hidden="true"
         class="hidden overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full md:inset-0 md:h-full">
        <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
            <!-- Modal content -->
            <div class="relative bg-surface border border-border rounded-lg shadow-overlay">
                <!-- Modal header -->
                <div class="flex justify-between items-start gap-x-6 px-4 py-3 rounded-t-lg border-b border-border-subtle">
                    <h2 class="font-lexend font-semibold text-lg text-text">
                        {{ title }}
                    </h2>

                    <button @click="close" type="button"
                            class="ml-auto inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-muted transition-colors duration-150 motion-reduce:transition-none hover:bg-surface-sunken hover:text-text"
                            :aria-label="$t('Close Window')"
                            :data-modal-toggle="modal_id">
                        <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true"/>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="p-4 space-y-6">
                    <slot/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    emits: ['close'],
    props: ['modal_id', 'title'],
    name: "FlowbiteModal",
    components: {PropertyIcon},
    methods: {
        close() {
            this.$emit('close')
        },
    }
}
</script>

<style scoped>

</style>
