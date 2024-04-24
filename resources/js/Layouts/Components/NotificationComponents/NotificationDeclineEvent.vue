<template>
    <jet-dialog-modal :show="true" @close="closeDeclineRequestModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Cancel booking')}}
                </div>
                <IconX stroke-width="1.5" @click="closeDeclineRequestModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
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
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import {XIcon} from "@heroicons/vue/solid";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Button from "@/Jetstream/Button.vue";
import {AdjustmentsIcon} from "@heroicons/vue/outline";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "NotificationDeclineEvent",
    mixins: [IconLib],
    components: {
        FormButton,
        NewUserToolTip,
        AdjustmentsIcon,
        Button,
        UserTooltip,
        JetDialogModal,
        XIcon
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
