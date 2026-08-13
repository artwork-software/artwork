<template>
    <ArtworkBaseModal
        :title="$t('Confirm return')"
        :description="$t('Confirm the return of this external material issue. You can add a description of the return.')"
        @close="$emit('close')"
    >
        <BaseTextarea
            id="notification_return_remarks"
            :label="$t('Return description')"
            v-model="returnForm.return_remarks"
            :placeholder="$t('Enter return remarks')"
        />

        <div class="flex items-center justify-between mt-4">
            <BaseUIButton is-cancel-button :label="$t('Cancel')" @click="$emit('close')"/>
            <BaseUIButton is-add-button :label="$t('Confirm return')" :disabled="returnForm.processing" @click="submit"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    externalIssueId: {
        type: Number,
        required: true,
    },
})

const emits = defineEmits(['close'])

const returnForm = useForm({
    return_remarks: '',
})

const submit = () => {
    // Leere Eingabe: Key weglassen, sonst würde das Backend ($remarksProvided)
    // bereits gespeicherte Rückgabe-Bemerkungen mit null überschreiben.
    returnForm
        .transform((data) => data.return_remarks?.trim() ? data : {})
        .post(route('extern-issue-of-material.return', props.externalIssueId), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            },
        })
}
</script>
