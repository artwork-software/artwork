<template>
    <ArtworkBaseModal
        :title="preset ? 'Edit shift template' : 'New shift template'"
        :description="preset ? '' : 'What kind of shift template would you like to create?'"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <form id="addShiftPresetForm" @submit.prevent="savePreset">
            <BaseInput
                id="shiftPresetName"
                v-model="presetForm.name"
                label="Template name"
                required
                :error="presetForm.errors.name"
                autofocus
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="presetForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="addShiftPresetForm"
                :label="preset ? 'Save template' : 'Create template'"
                variant="primary"
                :icon="preset ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="presetForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from 'vue'
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default defineComponent({
    name: "AddShiftPresetModal",
    components: {
        ArtworkBaseModal,
        BaseInput,
        BaseUIButton,
    },
    emits: ['closed'],
    props: ['event_types', 'eventId', 'preset', 'event_type_id'],
    data() {
        return {
            presetForm: useForm({
                name: this.preset ? this.preset.name : null,
                event_type_id: null,
            })
        }
    },
    computed: {
        canSubmit() {
            return Boolean(this.presetForm.name?.trim()) && !this.presetForm.processing;
        }
    },
    methods: {
        closeModal() {
            if (this.presetForm.processing) {
                return;
            }
            this.$emit('closed')
        },
        savePreset() {
            if (!this.canSubmit) {
                return;
            }
            const options = {
                preserveScroll: true,
                onSuccess: () => {
                    this.$emit('closed')
                }
            };
            if (this.preset?.id !== null && this.preset?.id !== undefined) {
                this.presetForm.patch(route('update.shift.preset', {shiftPreset: this.preset.id}), options)
            } else {
                this.presetForm.post(route('empty.presets.store'), options)
            }
        },
    },
})
</script>
