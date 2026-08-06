<template>
    <BaseModal @closed="$emit('closed')">
        <div class="mx-4">
            <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                {{$t('Delete event type')}}
            </div>
            <div class="text-sm/5 text-danger">
                {{$t('Are you sure you want to delete the event type {eventType} from the system? All events that are currently assigned to this type will be reassigned to the first event type.', {eventType: eventType.name})}}
            </div>
            <div class="flex justify-between mt-6">
                <button class="bg-accent-600 hover:bg-accent-700 focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                    text-base font-bold uppercase shadow-sm text-white"
                        @click="$emit('delete')">
                    {{$t('Delete')}}
                </button>
                <div class="flex my-auto">
                    <span @click="$emit('closed')"
                          class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{$t('No, actually not')}}</span>
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
    emits: ['closed', 'delete']
}
</script>
