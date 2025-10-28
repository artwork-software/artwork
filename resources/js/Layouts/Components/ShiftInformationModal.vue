<template>
    <ArtworkBaseModal @close="$emit('closeModal')" v-if="show" :title="$t('Shift information')" :description="$t('There is space for general information here. You can provide separate information for the individual shifts.')">

            <BaseTextarea
                :label="$t('What do I need to bear in mind with the shifts?')"
                id="description"
                v-model="this.project.shiftDescription"
                rows="4"
            />
            <div class="flex justify-end mt-2">
                <BaseUIButton :label="$t('Save')" is-add-button @click="changeShiftDescription"/>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    mixins: [Permissions],
    name: "ShiftInformationModal",
    props: {
        show: Boolean,
        project: Object
    },
    components: {
        BaseUIButton,
        BaseTextarea,
        ArtworkBaseModal,
        BaseModal,
        FormButton,
        JetDialogModal,
        JetInputError,
        XIcon,
        DownloadIcon
    },
    emits: ['closeModal'],
    data() {
        return {}
    },
    methods: {
        changeShiftDescription() {
            this.$inertia.patch(route('projects.update.shift_description', {project: this.project.id}), {
                shiftDescription: this.project.shiftDescription
            });
            this.$emit('closeModal')
        }
    }
}
</script>
