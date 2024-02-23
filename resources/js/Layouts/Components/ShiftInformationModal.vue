<template>
    <jet-dialog-modal :show="show" @close="$emit('closeModal')">
        <template #content>
            <img alt="Schichtinformation festlegen" src="/Svgs/Overlays/illu_appointment_edit.svg"
                 class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="$emit('closeModal')"
                   class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="headline1">
                {{ $t('Shift information') }}
            </div>
            <div class="xsLight my-4">
                {{ $t('There is space for general information here. You can provide separate information for the individual shifts.') }}
            </div>
            <textarea :placeholder="$t('What do I need to bear in mind with the shifts?')"
                      id="description"
                      v-model="this.project.shiftDescription"
                      rows="4"
                      class="mt-2 border-2 placeholder:xsLight placeholder:subpixel-antialiased focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
            <div class="flex justify-center mt-2">
                <AddButton mode="modal" :text="$t('Save')" @click="changeShiftDescription"/>
            </div>
        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon, DownloadIcon} from "@heroicons/vue/outline";
import Permissions from "@/mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "ShiftInformationModal",
    props: {
        show: Boolean,
        project: Object
    },
    components: {
        JetDialogModal,
        JetInputError,
        AddButton,
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
