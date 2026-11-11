<template>
    <ArtworkBaseModal
        :title="manufacturer ? 'Edit Manufacturer' : 'Create Manufacturer'"
        :description="manufacturer ? 'Edit manufacturer details' : 'Create a new manufacturer'"
        modal-size="sm:max-w-lg"
        @close="$emit('close')"
    >
        <form id="manufacturerForm" class="grid grid-cols-1 gap-4" @submit.prevent="submit">
            <BaseInput
                id="manufacturer-name"
                v-model="manufacturerForm.name"
                label="Name"
                required
                :error="manufacturerForm.errors.name"
                autofocus
            />
            <div>
                <BaseInput
                    id="manufacturer-address"
                    v-model="manufacturerForm.address"
                    label="Address"
                    :error="manufacturerForm.errors.address"
                />
                <p class="mt-1 px-1 text-xs text-text-muted">
                    {{ $t('Please enter the address of the manufacturer, e.g: Maxmustermann Str. 1234, 12345 Musterdorf') }}
                </p>
            </div>
            <BaseInput
                id="manufacturer-contact-person"
                v-model="manufacturerForm.contact_person"
                label="Contact Person"
                :error="manufacturerForm.errors.contact_person"
            />
            <BaseInput
                id="manufacturer-phone"
                v-model="manufacturerForm.phone"
                type="tel"
                label="Phone"
                :error="manufacturerForm.errors.phone"
            />
            <BaseInput
                id="manufacturer-email"
                v-model="manufacturerForm.email"
                type="email"
                label="Email"
                :error="manufacturerForm.errors.email"
            />
            <BaseInput
                id="manufacturer-website"
                v-model="manufacturerForm.website"
                label="Website"
                :error="manufacturerForm.errors.website"
            />
            <BaseInput
                id="manufacturer-customer-number"
                v-model="manufacturerForm.customer_number"
                label="Customer Number"
                :error="manufacturerForm.errors.customer_number"
            />
        </form>

        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="manufacturerForm.processing"
                @click="$emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="manufacturerForm"
                :label="manufacturer ? 'Update Manufacturer' : 'Create Manufacturer'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="manufacturerForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'CreateOrUpdateManufacturerModal'})

const props = defineProps({
    manufacturer: {
        type: Object,
        required: false,
        default: null,
    },
})

const emit = defineEmits(['close'])

const manufacturerForm = useForm({
    id: props.manufacturer?.id ?? null,
    name: props.manufacturer?.name ?? '',
    address: props.manufacturer?.address ?? '',
    contact_person: props.manufacturer?.contact_person ?? '',
    phone: props.manufacturer?.phone ?? '',
    email: props.manufacturer?.email ?? '',
    website: props.manufacturer?.website ?? '',
    customer_number: props.manufacturer?.customer_number ?? '',
})

const canSubmit = computed(() => String(manufacturerForm.name ?? '').trim() !== '' && !manufacturerForm.processing)

function submit() {
    if (!canSubmit.value) {
        return
    }
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    }
    if (props.manufacturer) {
        manufacturerForm.patch(route('manufacturers.update', {manufacturer: props.manufacturer.id}), options)
    } else {
        manufacturerForm.post(route('manufacturers.store'), options)
    }
}
</script>
