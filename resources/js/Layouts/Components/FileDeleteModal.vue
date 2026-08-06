<template>
    <BaseModal @closed="closeModal" v-if="show" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                    {{$t('Delete document')}}
                </div>
                <PropertyIcon name="IconX" stroke-width="1.5" @click="closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-text-subtle absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-sm/5 text-danger">
                    {{$t('Are you sure you want to delete this document from the system?')}}
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton :text="$t('Delete')"
                               @click="destroy" />
                    <div class="flex my-auto">
                            <span @click="closeModal"
                                  class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{$t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";
import JetDialogModal from '@/Jetstream/DialogModal.vue'
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "ProjectFileDeleteModal",
    mixins: [Permissions],
    components: {
        PropertyIcon,
        BaseModal,
        FormButton,
        JetDialogModal,
        IconX
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
