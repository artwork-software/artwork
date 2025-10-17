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
                <BaseUIButton type="button" is-cancel-button class="mt-4" variant="secondary" @click="$emit('close')" :label="$t('Cancel')" />

                <BaseUIButton type="submit" is-delete-button icon="IconX" class="mt-4" @click="declineRequest" :label="$t('Decline')" />
            </div>
        </form>

    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {router, useForm} from "@inertiajs/vue3";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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
