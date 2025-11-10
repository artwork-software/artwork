<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="artist ? 'Edit artist' : 'Create artist'"
        :description="artist ? 'Edit the artist information' : 'Create a new artist'"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <BaseInput id="name" label="Name" v-model="artistForm.name" :error="artistForm.errors.name" required />
            </div>

            <div>
                <BaseInput id="civil_name" label="Civil name" use v-model="artistForm.civil_name" :error="artistForm.errors.civil_name" />
            </div>

            <div>
                <BaseInput id="phone_number" label="Phone number" v-model="artistForm.phone_number" :error="artistForm.errors.phone_number" />
            </div>

            <div>
                <BaseInput id="position" label="Position" v-model="artistForm.position" :error="artistForm.errors.position" />
            </div>

            <div class="flex items-center justify-between mt-5">
                <BaseUIButton :label="artist ? $t('Update') : $t('Create')" is-add-button type="submit"/>
                <BaseUIButton :label="$t('Cancel')" is-cancel-button @click="$emit('close')"/>

            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

interface artist {
    id: number;
    name: string;
    civil_name?: string;
    phone_number?: string;
    position?: string;
}

const props = defineProps<{
    artist?: artist;
}>()

const emits = defineEmits<{
    (e: 'close'): void;
}>();

const artistForm = useForm({
    id: props.artist?.id ?? null,
    name: props.artist?.name ?? '',
    civil_name: props.artist?.civil_name ?? '',
    phone_number: props.artist?.phone_number ?? '',
    position: props.artist?.position ?? '',
})

const submit = () => {
    if (artistForm.id) {
        artistForm.patch(route('artist.update', artistForm.id), {
            onSuccess: () => emits('close'),
        })
    } else {
        artistForm.post(route('artist.store'), {
            onSuccess: () => emits('close'),
        })
    }
}
</script>

<style scoped>

</style>
