<template>
    <BaseModal @closed="$emit('closed')">
        <div class="mx-4">
            <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                {{$t('Delete event type')}}
            </div>
            <div class="text-sm/5 text-danger">
                {{$t('The event type {eventType} cannot be deleted because it is the standard event type.', {eventType: eventType.name})}}
            </div>
            <div class="flex justify-between mt-6">
                <div class="flex my-auto">
                    <span @click="$emit('closed')"
                          class="text-text-subtle subpixel-antialiased cursor-pointer">{{$t('Ok')}}</span>
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
