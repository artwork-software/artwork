<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="manufacturer ? $t('Edit Manufacturer') : $t('Create Manufacturer')"
                :description="manufacturer ? $t('Edit manufacturer details') : $t('Create a new manufacturer')"
            />
        </div>

        <form @submit.prevent>
            <div class="grid gird-cols-1 gap-4">
                <div>
                    <TextInputComponent
                        id="name"
                        v-model="manufacturerForm.name"
                        :label="$t('Name*')"
                        required
                    />
                </div>
                <div>
                    <TextInputComponent
                        id="address"
                        v-model="manufacturerForm.address"
                        :label="$t('Address')"
                    />
                    <div class="text-xs text-artwork-messages-info mt-1 font-lexend">
                        <p>
                            {{ $t('Please enter the address of the manufacturer, e.g: Maxmustermann Str. 1234, 12345 Musterdorf') }}
                        </p>
                    </div>
                </div>
                <div>
                    <TextInputComponent
                        id="contact_person"
                        v-model="manufacturerForm.contact_person"
                        :label="$t('Contact Person')"
                    />
                </div>
                <div>
                    <TextInputComponent
                        id="phone"
                        v-model="manufacturerForm.phone"
                        :label="$t('Phone')"
                    />
                </div>
                <div>
                    <TextInputComponent
                        id="email"
                        v-model="manufacturerForm.email"
                        :label="$t('Email')"
                    />
                </div>
                <div>
                    <TextInputComponent
                        id="website"
                        v-model="manufacturerForm.website"
                        :label="$t('Website')"
                    />
                </div>
                <div>
                    <TextInputComponent
                        id="customer_number"
                        v-model="manufacturerForm.customer_number"
                        :label="$t('Customer Number')"
                    />
                </div>
            </div>

            <div>
                <div class="flex justify-center mt-5">
                    <FormButton
                        type="submit"
                        :text="manufacturer ? $t('Update Manufacturer') : $t('Create Manufacturer')"
                        @click="submit"
                    />
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    manufacturer: {
        type: Object,
        required: false,
        default: null
    }
})

const emits = defineEmits(['close'])

const manufacturerForm = useForm({
    id: props.manufacturer ? props.manufacturer.id : null,
    name: props.manufacturer ? props.manufacturer.name : '',
    address: props.manufacturer ? props.manufacturer.address : '',
    contact_person: props.manufacturer ? props.manufacturer.contact_person : '',
    phone: props.manufacturer ? props.manufacturer.phone : '',
    email: props.manufacturer ? props.manufacturer.email : '',
    website: props.manufacturer ? props.manufacturer.website : '',
    customer_number: props.manufacturer ? props.manufacturer.customer_number : '',
})

const submit = () => {
    if (props.manufacturer) {
        manufacturerForm.patch(route('manufacturers.update', {manufacturer: props.manufacturer.id}), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    } else {
        manufacturerForm.post(route('manufacturers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    }
}

</script>

<style scoped>

</style>