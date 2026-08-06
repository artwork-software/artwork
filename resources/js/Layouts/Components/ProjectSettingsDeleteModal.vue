<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-bold text-text text-2xl my-2">
                    {{ title }}
                </div>
                <div class="text-danger subpixel-antialiased">
                    {{ description }}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton @click="$emit('delete')" :text="$t('Delete')"/>
                    <div class="flex my-auto">
                            <span @click="$emit('closeModal')"
                                  class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
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
        IconX
    }
}
</script>

<style scoped>

</style>
