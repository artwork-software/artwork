<template>
    <ArtworkBaseModal
        title="Decline Shift Commit Request"
        description="Are you sure you want to decline this shift commit request?"
        @close="$emit('close')"
    >

        <form>
            <div>
                <BaseTextarea id="decline_message" v-model="form.reason" name="decline_message" label="Decline Message" placeholder="Enter your message here..." />
            </div>

            <div class="flex items-center justify-between mt-5">
                <ArtworkBaseModalButton type="submit" class="mt-4" variant="secondary" @click="$emit('close')">
                    {{ $t('Cancel') }}
                </ArtworkBaseModalButton>

                <ArtworkBaseModalButton type="button" variant="danger" class="mt-4" @click="declineRequest">
                    {{ $t('Decline') }}
                </ArtworkBaseModalButton>
            </div>
        </form>

    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {router, useForm} from "@inertiajs/vue3";

const props = defineProps({
    requestId: {
        type: Number,
        default: null
    }
})


const emit = defineEmits(['close']);

const form = useForm({
    reason: ''
});

const declineRequest = () => {
    form.patch(route('shifts.commit-requests.decline', props.request.id), {
        onSuccess: () => {
            emit('close');
        },
    })
}
</script>

<style scoped>

</style>
