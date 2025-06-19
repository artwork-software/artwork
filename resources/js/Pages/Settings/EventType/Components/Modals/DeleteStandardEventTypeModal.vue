<template>
    <BaseModal @closed="$emit('closed')">
        <div class="mx-4">
            <div class="headline1">
                {{$t('Delete event type')}}
            </div>
            <div class="errorText">
                {{$t('The event type {eventType} cannot be deleted because it is the standard event type.', {eventType: eventType.name})}}
            </div>
            <div class="flex justify-between mt-6">
                <div class="flex my-auto">
                    <span @click="$emit('closed')"
                          class="text-secondary subpixel-antialiased cursor-pointer">{{$t('Ok')}}</span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    components: {
        BaseModal
    },
    props: {
        eventType: {
            type: Object,
            required: true
        }
    },
    emits: ['closed']
}
</script>
