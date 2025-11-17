<template>
    <ArtworkBaseModal @close="close(false)" v-if="show" :title="$t('Qualification assignment')"
                      :description="$t('Shift') + ' ' + this.getCurrentShiftCount() + '/' + this.getMaxShiftCount()">
            <div class="mx-4">
                <div class="-mt-8 mb-4 xsLight">
                    {{ $t('In which qualification should')}}
                    <img class="inline h-5 w-5 object-cover rounded-full"
                         :src="this.user.profile_photo_url"
                         :alt="'Profilfoto ' + this.user.display_name"
                    />
                    {{ $t('{0} be used in the following layer?', this.user.display_name)}}
                </div>
                <div class="xsLight my-3 flex flex-col">
                    <span>
                        {{ $t('Shift')}}:
                        {{ this.currentShiftToAssign.shift.craft.name }}
                        ({{ this.currentShiftToAssign.shift.craft.abbreviation }})
                        &vert;
                        {{ this.currentShiftToAssign.shift.start }}
                        -
                        {{ this.currentShiftToAssign.shift.end }}
                    </span>
                </div>
                <div class="flex flex-col">
                    <div class="grid grid-cols-2 w-full gap-4">
                        <AddButtonSmall v-for="availableShiftQualificationSlot in this.currentShiftToAssign.availableSlots"
                               :text="$t('Insert as {0}',[availableShiftQualificationSlot.name])"
                                        no-icon
                               @click="this.handleShift(this.currentShiftToAssign.shift.id, availableShiftQualificationSlot.id)"
                        />
                    </div>
                    <div class="w-full mt-4">
                        <AddButtonSmall
                            :text="$t('Skip assignment')"
                            no-icon
                            @click="this.skipShift"
                            class="w-full bg-gray-600"
                        />
                    </div>
                </div>
            </div>
    </ArtworkBaseModal>
</template>

<script>
import {defineComponent} from "vue";
import {XIcon} from "@heroicons/vue/outline";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";

export default defineComponent({
    name: 'ShiftsQualificationsAssignmentModal',
    mixins: [IconLib],
    components: {
        ArtworkBaseModal,
        AddButtonSmall,
        ModalHeader,
        FormButton,
        BaseModal,
        UserPopoverTooltip,
        XIcon,
        JetDialogModal
    },
    props: [
        'show',
        'user',
        'shifts'
    ],
    emits: [
        'close'
    ],
    data () {
        return {
            currentShiftToAssignIndex: 0,
            shiftsToAssign: []
        }
    },
    computed: {
        currentShiftToAssign() {
            return this.shifts[this.currentShiftToAssignIndex];
        }
    },
    methods: {
        getMaxShiftCount() {
            return this.shifts.length;
        },
        getCurrentShiftCount() {
            return (this.currentShiftToAssignIndex + 1);
        },
        isLastShiftToAssign() {
            return this.getCurrentShiftCount() === this.getMaxShiftCount();
        },
        nextShift() {
            this.currentShiftToAssignIndex++;
        },
        handleShift(shiftId, shiftQualificationId) {
            this.shiftsToAssign.push({
                shiftId: shiftId,
                shiftQualificationId: shiftQualificationId
            });

            if (this.isLastShiftToAssign()) {
                this.close(true);
                return;
            }

            this.nextShift();
        },
        skipShift() {
            if (this.isLastShiftToAssign()) {
                this.close(true);
                return;
            }

            this.nextShift();
        },
        close(closedForAssignment) {
            this.$emit('close', closedForAssignment, this.shiftsToAssign);
        }
    }
});
</script>
