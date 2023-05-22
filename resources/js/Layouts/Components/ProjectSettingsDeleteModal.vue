<template>
    <jet-dialog-modal :show="show" @close="$emit('closeModal')">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="font-bold text-primary text-2xl my-2">
                    {{ title }}
                </div>
                <XIcon @click="$emit('closeModal')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-error subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-between mt-6">
                    <AddButton class="px-20 py-4" @click="$emit('delete')" text="Löschen" mode="modal"/>
                    <div class="flex my-auto">
                            <span @click="$emit('closeModal')"
                                  class="xsLight cursor-pointer">Nein, doch nicht</span>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from "@heroicons/vue/outline"
import AddButton from "@/Layouts/Components/AddButton.vue";
import Permissions from "@/mixins/Permissions.vue";
export default {
    mixins: [Permissions],
    name: "ProjectSettingsDeleteModal",
    props: {
        show: Boolean,
        title: String,
        description: String
    },
    components: {
        AddButton,
        JetDialogModal,
        XIcon
    }
}
</script>

<style scoped>

</style>
