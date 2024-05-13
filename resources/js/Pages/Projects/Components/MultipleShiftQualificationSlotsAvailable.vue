<template>
    <BaseModal @closed="close" v-if="show" modal-image="/Svgs/Overlays/illu_user_invite.svg">
            <div class="mx-4">
                <div class="mt-8 headline1">
                    {{ $t('Select qualification') }}
                </div>
                <div class="xsLight my-6">
                    {{ $t('In which qualification should the user be added?') }}
                </div>
                <div class="grid grid-cols-2 w-full gap-4">
                    <input v-for="availableShiftQualificationSlot in this.availableShiftQualificationSlots"
                           type="button"
                           :value="$t('Use as {0}', [availableShiftQualificationSlot.name])"
                           class="text-wrap cursor-pointer bg-artwork-buttons-create text-sm flex py-2 px-12 items-center border border-transparent rounded-full shadow-sm text-white focus:outline-none hover:bg-artwork-buttons-hover"
                           @click="this.close(null, this.droppedUser, availableShiftQualificationSlot.id)"
                    />
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {defineComponent} from 'vue';
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default defineComponent({
    components: {
        BaseModal,
        XIcon,
        JetDialogModal,
        ShiftQualificationIconCollection
    },
    props: [
        'show',
        'availableShiftQualificationSlots',
        'droppedUser'
    ],
    emits: [
        'close'
    ],
    methods: {
        close(event, droppedUser = null, shiftQualificationId = null) {
            this.$emit('close', droppedUser, shiftQualificationId);
        }
    }
});
</script>
