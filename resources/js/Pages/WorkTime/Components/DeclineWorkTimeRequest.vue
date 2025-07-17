<template>
    <ArtworkBaseModal
        title="Decline Work Time Request"
        description="Are you sure you want to decline this work time request?"
        @close="$emit('close')"
        >

        <form>
            <div>
                <BaseTextarea id="decline_message" v-model="form.decline_message" name="decline_message" label="Decline Message" placeholder="Enter your message here..." />
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
    decline_message: ''
});

const declineRequest = () => {
    form.post(route('worktime.change-request.decline', props.requestId), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
        onError: (error) => {
            console.error('Error declining request:', error);
        }
    });
}
</script>

<style scoped>

</style>
