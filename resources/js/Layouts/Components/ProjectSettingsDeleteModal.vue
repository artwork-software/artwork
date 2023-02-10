<template>
    <jet-dialog-modal :show="show" @close="$emit('closeModal')">
        <template #content>
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
                    <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="$emit('delete')">
                        Löschen
                    </button>
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
export default {
    name: "ProjectSettingsDeleteModal",
    props: {
        show: Boolean,
        title: String,
        description: String
    },
    components: {
        JetDialogModal,
        XIcon
    }
}
</script>

<style scoped>

</style>
