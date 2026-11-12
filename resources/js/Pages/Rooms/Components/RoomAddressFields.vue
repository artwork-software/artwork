<template>
    <fieldset class="space-y-3">
        <legend class="font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Address') }}</legend>
        <p class="-mt-2 text-xs text-text-muted">{{ $t('Appears in the event plan and the artist plan. External places such as hotels or restaurants can also be created as rooms with an address.') }}</p>
        <BaseInput
            :id="`${idPrefix}Street`"
            v-model="form.street"
            label="Street and house number"
            autocomplete="off"
            :error="form.errors.street"
        />
        <div class="grid grid-cols-3 gap-3">
            <BaseInput
                :id="`${idPrefix}ZipCode`"
                v-model="form.zip_code"
                label="Zip code"
                autocomplete="off"
                :error="form.errors.zip_code"
            />
            <BaseInput
                :id="`${idPrefix}City`"
                v-model="form.city"
                class="col-span-2"
                label="City"
                autocomplete="off"
                :error="form.errors.city"
            />
        </div>
    </fieldset>
</template>

<script setup>
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

defineOptions({name: 'RoomAddressFields'})

defineProps({
    /** Inertia-Formular mit street, zip_code und city */
    form: {
        type: Object,
        required: true,
    },
    idPrefix: {
        type: String,
        required: true,
    },
})
</script>
