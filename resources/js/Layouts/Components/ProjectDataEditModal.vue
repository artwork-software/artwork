<template>
    <jet-dialog-modal :show="show" @close="closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Basisdaten bearbeiten
                </div>
                <XIcon @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <input :placeholder="name"
                          id="title"
                          v-model="name"
                          class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
            </div>
            <div class="justify-center flex w-full my-6">
                <AddButton text="Speichern" mode="modal" class="px-6 py-3" :disabled="name.length < 1"
                           @click="updateProjectData"/>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import JetInputError from "@/Jetstream/DialogModal";
import AddButton from "@/Layouts/Components/AddButton";
import {DownloadIcon, XIcon} from "@heroicons/vue/outline";

export default {
    name: "ProjectDataEditModal",
    props: {
        show: Boolean,
        closeModal: Function,
        project: Object,
    },
    components: {
        JetDialogModal,
        JetInputError,
        AddButton,
        XIcon,
        DownloadIcon
    },
    data () {
        return {
            name: this.project.name
        }
    },
    methods: {
        updateProjectData() {

        }
    }
}
</script>

<style scoped>

</style>
