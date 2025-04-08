<template>

    <BaseModal @closed="closeModal(false)">
        <div class="relative">
            <div class="font-black font-lexend text-primary text-3xl my-2 mb-6">
                {{ title }}
            </div>


            <BaseAlertComponent :message="description" type="error" />
        </div>
        <div>
            <div class="flex justify-between mt-5 items-center pr-4" v-if="!is_budget && !isSeriesDelete">
                <FormButton class="bg-red-500 hover:bg-red-600"
                    @click="deleteElement(true)"
                    :text="buttonText" />
                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal(false)">
                    {{ $t('No, not really') }}
                </p>
            </div>
            <div v-if="isSeriesDelete" class="flex justify-between mt-5 items-center pr-4">
                <FormButton
                    @click="deleteElement(true)"
                    :text="buttonText" />
                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="complete_delete(true)">
                    {{ $t('Delete series entry completely') }}
                </p>
            </div>
            <div v-if="is_budget && !isSeriesDelete" class="flex justify-between mt-5 pl-4 items-center pr-4">
                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal(false)">{{ $t('Continue without saving') }}</p>
                <FormButton
                    type="button"
                    @click="deleteElement(true)"
                    :text="buttonText" />

            </div>
        </div>
    </BaseModal>
</template>

<script>
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

export default {
    name: "ConfirmDeleteModal",
    mixins: [Permissions, IconLib],
    components: {
        BaseAlertComponent,
        BaseModal,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel
    },
    data(){
        return {
            open: true,
            buttonText: this.button ? this.button : this.$t('Delete')
        }
    },
    props: ['title', 'description', 'button', 'is_budget', 'isSeriesDelete'],
    emits: ['closed', 'delete', 'complete_delete'],
    methods: {
        closeModal(bool){
            this.$emit('closed', bool)
        },
        deleteElement(bool){
            this.$emit('delete', bool)
        },
        complete_delete(bool){
            this.$emit('complete_delete', bool)
        }
    }
}
</script>
