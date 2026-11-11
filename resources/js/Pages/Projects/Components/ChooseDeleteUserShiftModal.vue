<template>
    <ArtworkBaseModal
        title="Delete shift staffing"
        description="You want to delete the occupation of a repeat shift. Specify whether this only applies to one or all further shifts."
        modal-size="sm:max-w-lg"
        @close="closed"
    >
        <SwitchDualLabel
            v-model="removeFromSingleShift"
            :left-label="$t('For this and all subsequent shifts')"
            :right-label="$t('Only for this shift')"
            :icon="IconRepeat"
        />
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                @click="closed"
            />
            <BaseUIButton
                label="Delete"
                variant="danger"
                icon="IconTrash"
                @click="returnBuffer"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from 'vue'
import {IconRepeat} from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SwitchDualLabel from '@/Artwork/Toggles/SwitchDualLabel.vue'

defineOptions({name: 'ChooseDeleteUserShiftModal'})

const emit = defineEmits(['close-modal', 'returnBuffer'])

const removeFromSingleShift = ref(false)

function closed() {
    emit('close-modal')
}

function returnBuffer() {
    emit('returnBuffer', removeFromSingleShift.value)
}
</script>
