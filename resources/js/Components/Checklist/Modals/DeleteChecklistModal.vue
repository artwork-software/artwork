<template>
    <BaseModal @closed="$emit('closed')" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="font-black font-lexend text-text text-3xl my-2">
                {{ $t('Delete checklist') }}
            </div>
            <div class="text-danger subpixel-antialiased">
                {{ $t('Are you sure you want to delete the checklist?', [checklistToDelete.name]) }}
            </div>
            <div class="flex justify-between mt-6">
                <button class="bg-accent-600 hover:bg-accent-700 rounded-full focus:outline-none my-auto inline-flex items-center px-14 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-white"
                        @click="$emit('deleteChecklist')">
                    {{ $t('Delete') }}
                </button>
                <div class="flex my-auto">
                        <span @click="$emit('closed')" class="text-sm/5 font-bold text-text-subtle cursor-pointer">
                            {{ $t('No, not really') }}
                        </span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    checklistToDelete: {
        type: Object,
        required: true
    }
})

const emits = defineEmits([
    'closed',
    'deleteChecklist'
])

</script>

<style scoped>

</style>