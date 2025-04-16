<template>
    <ArtworkBaseModal @close="$emit('close')" :title="title" :description="description">
        <form @submit.prevent="createOrUpdateContact" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full">
                    <BaseInput
                        id="contact_name"
                        v-model="contactForm.name"
                        label="Name"
                    />
                </div>
                <div class="col-span-full">
                    <BaseInput
                        id="contact_street"
                        v-model="contactForm.street"
                        label="Street"
                    />
                </div>
                <div class="col-span-1">
                    <BaseInput
                        id="contact_zip_code"
                        v-model="contactForm.zip_code"
                        label="Zip code"
                    />
                </div>
                <div class="col-span-1">
                    <BaseInput
                        id="contact_location"
                        v-model="contactForm.location"
                        label="Location"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-dashed pt-6">
                <div class="col-span-1">
                    <BaseInput
                        id="contact_email"
                        v-model="contactForm.email"
                        label="Email"
                        type="email"
                    />
                </div>
                <div class="col-span-1">
                    <BaseInput
                        id="contact_phone"
                        v-model="contactForm.phone"
                        label="Phone number"
                    />
                </div>
                <div class="col-span-1">
                    <BaseInput
                        id="contact_mobile"
                        v-model="contactForm.mobile"
                        label="Mobile number"
                    />
                </div>
                <div class="col-span-1">
                    <BaseInput
                        id="contact_fax"
                        v-model="contactForm.fax"
                        label="Fax number"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between mt-5">
                <ArtworkBaseButton size="md" variant="primary" type="submit">
                    {{ props.contact.id ? $t('Update') : $t('Create') }}
                </ArtworkBaseButton>
                <ArtworkBaseButton size="md" variant="danger" type="button" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </ArtworkBaseButton>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseButton from "@/Artwork/Buttons/ArtworkBaseButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    model: {
        type: String,
        required: false,
    },
    modelId: {
        type: Number,
        required: false,
    },
    contact: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            email: '',
            street: '',
            zip_code: '',
            location: '',
            phone: '',
            mobile: '',
            fax: '',
            is_primary: false,
        })
    }
})

const emits = defineEmits(['close'])

const contactForm = useForm({
    id: props.contact.id,
    name: props.contact.name,
    email: props.contact.email,
    street: props.contact.street,
    zip_code: props.contact.zip_code,
    location: props.contact.location,
    phone: props.contact.phone,
    mobile: props.contact.mobile,
    fax: props.contact.fax,
    is_primary: props.contact.is_primary
})

const createOrUpdateContact = () => {
    if (contactForm.id) {
        contactForm.patch(route('contact.update', contactForm.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                contactForm.reset();
                emits('close');
            },
            onError: () => {
                console.log('Error updating contact');
            }
        });
    } else {
        contactForm.post(route('contact.store', {model: props.model, modelId: props.modelId}), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                contactForm.reset();
                emits('close');
            },
            onError: () => {
                console.log('Error creating contact');
            }
        });
    }
}
</script>

<style scoped>

</style>