<template>
    <BaseModal @closed="closeModal">
        <div>
            <div>
                <div class=" headline1">
                    {{dayServiceToEdit ? $t('Day Service edit') :  $t('Day Service create') }}
                </div>
                <div class="xsLight my-6">
                    {{
                        dayServiceToEdit ?
                            $t('Here you can edit the day service "{0}".', [this.dayServiceToEdit?.name]):
                            $t('You can create a new day service here.')
                    }}
                </div>


                <div class="flex items-center gap-x-3">
                    <IconSelector @update:modelValue="addIconToForm" :current-icon="dayServiceForm ? dayServiceForm.icon : null" />
                    <div class="w-full">
                        <BaseInput
                            id="name"
                            no-margin-top
                            v-model="this.dayServiceForm.name"
                            :label="$t('Name of the day service')"
                        />
                    </div>
                    <div>
                        <ColorPickerComponent @updateColor="addColor" :color="dayServiceForm.hex_color" />
                    </div>
                </div>

                <div class="w-full text-center mb-6">
                    <FormButton
                        @click="createOrUpdate"
                        :disabled="this.dayServiceForm.icon === null || this.dayServiceForm.name === null || this.dayServiceForm.hex_color === null"
                        :text="dayServiceToEdit ? $t('Save') : $t('Create')"
                        class="mt-8 inline-flex items-center"
                    />
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot
} from '@headlessui/vue'
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Label from "@/Jetstream/Label.vue";
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import {useForm} from "@inertiajs/vue3";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import IconSelector from "@/Components/Icon/IconSelector.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default {
    name: "AddEditDayServiceModal",
    mixins: [Permissions, IconLib],
    components: {
        BaseInput,
        TextInputComponent,
        ModalHeader,
        BaseModal,
        IconSelector,
        ColorPickerComponent,
        ShiftQualificationIconCollection, Label,
        FormButton,
        Dialog,
        DialogTitle,
        DialogPanel,
        TransitionChild,
        TransitionRoot,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        ChevronDownIcon,
    },
    data(){
        return {
            open: true,
            dayServiceForm: useForm({
                id: this.dayServiceToEdit ? this.dayServiceToEdit.id : null,
                name: this.dayServiceToEdit ? this.dayServiceToEdit.name : '',
                icon: this.dayServiceToEdit ? this.dayServiceToEdit.icon : '',
                hex_color: this.dayServiceToEdit ? this.dayServiceToEdit.hex_color : '#0d6be3'
            }),
            selectedColor: '#0d6be3'
        }
    },
    props: ['iconList', 'dayServiceToEdit'],
    emits: ['closed'],
    methods: {
        addIconToForm(icon){
            this.dayServiceForm.icon = icon;
        },
        addColor(color){
            this.dayServiceForm.hex_color = color;
        },
        closeModal(bool){
            this.$emit('closed', bool)
        },
        openColorPicker(){
            this.$refs.colorPicker.click();
        },
        createOrUpdate(){
            if (this.dayServiceToEdit?.id){
                this.dayServiceForm.patch(route('day-service.update', {dayService: this.dayServiceToEdit.id}), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false)
                    }
                })
            } else {
                this.dayServiceForm.post(route('day-service.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false)
                    }
                })
            }
        }
    }
}
</script>
