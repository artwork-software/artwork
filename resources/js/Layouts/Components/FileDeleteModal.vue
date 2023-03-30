<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Dokument löschen
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="errorText">
                    Bist du sicher, dass du dieses Dokument aus dem
                    System löschen möchtest?
                </div>
                <div class="flex justify-between mt-6">
                    <AddButton text="Löschen" mode="modal" class="px-20 py-3"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                    </div>
                </div>
            </div>

        </template>

    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import AddButton from "@/Layouts/Components/AddButton";
import {XIcon} from "@heroicons/vue/outline";

export default {
    name: "ProjectFileDeleteModal",
    components: {
        JetDialogModal,
        AddButton,
        XIcon
    },
    props: {
        file: Object,
        show: Boolean,
        closeModal: Function,
        type: {
            type: String,
            validator(value) {
                // The value must match one of these strings
                return ['project', 'room'].includes(value)
            }
        }
    },
    methods: {
        destroy() {
            if (this.type === "project") {
                this.$inertia.delete(`/project_files/${this.file.id}`);
            }
            else {
                this.$inertia.delete(`/room_files/${this.file.id}`);
            }
            this.closeModal()
        },
    }
}
</script>

<style scoped>

</style>
