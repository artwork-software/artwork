<template>
    <BaseModal @closed="closeDeclineRequestModal" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text my-2">
                    {{ $t('Cancel booking')}}
                </div>
                <div class="flex flex-wrap w-full items-center">
                    <div class="w-full items-center flex-wrap">
                        <pre>
                            {{ requestToDecline }}
                        </pre>
                    </div>
                </div>
                <div class="flex justify-between mt-6">
                    <FormButton
                        @click="declineRequest"
                        :text="$t('Cancellations')"
                        class="inline-flex items-center"
                    />
                    <div class="flex my-auto">
                            <span @click="closeDeclineRequestModal"
                                  class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {IconAdjustments, IconX} from "@tabler/icons-vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Button from "@/Jetstream/Button.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: "NotificationDeclineEvent",
    mixins: [IconLib],
    components: {
        BaseModal,
        FormButton,
        NewUserToolTip,
        IconAdjustments,
        Button,
        UserTooltip,
        JetDialogModal,
        IconX
    },
    emits: ['closed'],
    props: ['requestToDecline', 'eventTypes'],
    data(){
        return {
            declineEvent: useForm({
                eventId: this.requestToDecline.id,
                comment: ''
            })
        }
    },
    methods: {
        closeDeclineRequestModal(bool = true){
            this.$emit('closed', bool)
        },
        declineRequest(){
            this.declineEvent.put(route('events.decline', this.requestToDecline.id), {
                preserveScroll: true,
                onSuccess: () => {
                    this.closeDeclineRequestModal();
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
