<template>
    <ArtworkBaseModal
        :title="title || 'Edit'"
        :description="description || ''"
        modal-size="sm:max-w-lg"
        @close="$emit('close')"
    >
        <form id="editProjectSettingsForm" class="flex items-end gap-x-3" @submit.prevent="update">
            <div class="shrink-0">
                <span class="mb-1 block font-lexend text-xs font-medium text-text-muted">{{ $t('Color') }}</span>
                <ColorPickerComponent :color="itemCopy.color" @update-color="updateColor"/>
            </div>
            <BaseInput
                id="editProjectSettingsName"
                v-model="itemCopy.name"
                label="Name"
                required
                class="flex-1"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                @click="$emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="editProjectSettingsForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :disabled="!itemCopy.name?.trim()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";

export default {
    name: "EditProjectSettingsModal",
    components: {ArtworkBaseModal, BaseInput, BaseUIButton, ColorPickerComponent},
    props: {
        item: Object,
        title: String,
        description: String,
    },
    emits: ['close', 'update'],
    data() {
        return {
            itemCopy: {...this.item},
        }
    },
    methods: {
        updateColor(color) {
            this.itemCopy.color = color
        },
        update() {
            if (!this.itemCopy.name?.trim()) {
                return
            }
            this.$emit('update', this.itemCopy);
            this.$emit('close')
        },
    }
}
</script>
