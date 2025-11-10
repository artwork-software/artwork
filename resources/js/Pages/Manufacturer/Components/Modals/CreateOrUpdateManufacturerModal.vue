<template>
    <ArtworkBaseModal @close="$emit('close')" :title="manufacturer ? $t('Edit Manufacturer') : $t('Create Manufacturer')"
                      :description="manufacturer ? $t('Edit manufacturer details') : $t('Create a new manufacturer')">

        <form @submit.prevent>
            <div class="grid gird-cols-1 gap-4">
                <div>
                    <BaseInput
                        id="name"
                        v-model="manufacturerForm.name"
                        :label="$t('Name*')"
                        required
                    />
                </div>
                <div>
                    <BaseInput
                        id="address"
                        v-model="manufacturerForm.address"
                        :label="$t('Address')"
                    />
                    <div class="text-xs text-blue-400 mt-1 px-1">
                        <p>
                            {{ $t('Please enter the address of the manufacturer, e.g: Maxmustermann Str. 1234, 12345 Musterdorf') }}
                        </p>
                    </div>
                </div>
                <div>
                    <BaseInput
                        id="contact_person"
                        v-model="manufacturerForm.contact_person"
                        :label="$t('Contact Person')"
                    />
                </div>
                <div>
                    <BaseInput
                        id="phone"
                        v-model="manufacturerForm.phone"
                        :label="$t('Phone')"
                    />
                </div>
                <div>
                    <BaseInput
                        id="email"
                        v-model="manufacturerForm.email"
                        :label="$t('Email')"
                    />
                </div>
                <div>
                    <BaseInput
                        id="website"
                        v-model="manufacturerForm.website"
                        :label="$t('Website')"
                    />
                </div>
                <div>
                    <BaseInput
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
    </ArtworkBaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {router, useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

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
