<template>
    <ArtworkBaseModal title="Enter return" description="Enter the return of an external material issue" @close="$emit('close')">
        <BaseTextarea id="return_remarks" label="Defects after return" v-model="externalIssueForm.return_remarks" placeholder="Enter return remarks" />

        <div class="flex items-center justify-between mt-4">
            <ArtworkBaseModalButton type="button" variant="danger" class="mr-2" @click="$emit('close')">
                {{ $t('Cancel') }}
            </ArtworkBaseModalButton>
            <ArtworkBaseModalButton type="submit" variant="primary" @click="submit">
                {{ $t('Save') }}
            </ArtworkBaseModalButton>

        </div>
    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import {useForm} from "@inertiajs/vue3";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

const props = defineProps({
    externalIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
        })
    },
})

const emits = defineEmits(['close'])

const externalIssueForm = useForm({
    return_remarks: props.externalIssue.return_remarks,
})

const submit = () => {
    externalIssueForm.post(route('extern-issue-of-material.return', props.externalIssue.id), {
        onSuccess: () => {
            emits('close')
        },
        onError: () => {
            console.log('Error')
        }
    })
}
</script>

<style scoped>

</style>