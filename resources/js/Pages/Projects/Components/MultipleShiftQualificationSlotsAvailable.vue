<template>
    <jet-dialog-modal :show="this.show" @close="this.close">
        <template #content>
            <img src="/Svgs/Overlays/illu_user_invite.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <XIcon @click="this.close"
                       class="h-5 w-5 flex text-secondary cursor-pointer absolute right-0 mr-10"
                       aria-hidden="true"/>
                <div class="mt-8 headline1">
                    Qualifikation wählen
                </div>
                <div class="xsLight my-6">
                    In welcher Qualifikation soll der/die Nutzer*in hinzugefügt werden?
                </div>
                <div class="grid grid-cols-2 w-full gap-4">
                    <input v-for="availableShiftQualificationSlot in this.availableShiftQualificationSlots"
                           type="button"
                           :value="'Als ' + availableShiftQualificationSlot.name + ' einsetzen'"
                           class="cursor-pointer bg-buttonBlue text-sm flex py-2 px-12 items-center border border-transparent rounded-full shadow-sm text-white focus:outline-none hover:bg-buttonHover"
                           @click="this.close(null, this.droppedUser, availableShiftQualificationSlot.id)"
                    />
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import {defineComponent} from 'vue';
import ShiftQualificationIconCollection from "@/Layouts/Components/ShiftQualificationIconCollection.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon} from "@heroicons/vue/outline";

export default defineComponent({
    components: {
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
