<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-bold text-primary text-2xl my-2">
                    {{ title }}
                </div>
                <div class="text-error subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton @click="$emit('delete')" :text="$t('Delete')"/>
                    <div class="flex my-auto">
                            <span @click="$emit('closeModal')"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline"
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
export default {
    mixins: [Permissions],
    name: "ProjectSettingsDeleteModal",
    props: {
        show: Boolean,
        title: String,
        description: String
    },
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
        XIcon
    }
}
</script>

<style scoped>

</style>
