<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Delete document')}}
                </div>
                <IconX stroke-width="1.5" @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="errorText">
                    {{$t('Are you sure you want to delete this document from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton :text="$t('Delete')"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import {XIcon} from "@heroicons/vue/outline";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "ProjectFileDeleteModal",
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        JetDialogModal,
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
