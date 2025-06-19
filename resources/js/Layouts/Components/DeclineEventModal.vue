<template>
    <BaseModal @closed="closeDeclineRequestModal" v-if="true" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ $t('Cancel booking') }}
            </div>
            <div class="flex flex-wrap w-full items-center">
                <div class="w-full items-center flex-wrap">

                    <div class="flex items-center w-full mt-4 mb-3">
                        <div class="flex items-center w-full">
                            <div class="flex w-1/2">
                                <div>
                                    <div class="block w-6 h-6 rounded-full"
                                         :style="{'backgroundColor' : eventTypes[requestToDecline?.eventTypeId]?.hex_code }"/>
                                </div>
                                <div
                                    class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                    {{ requestToDecline?.eventTypeName }}
                                    <span class="mx-2" v-if="requestToDecline?.eventName">
                                         -
                                        </span>
                                    {{ requestToDecline?.eventName }}
                                    <IconAdjustmentsAlt stroke-width="1.5" v-if="requestToDecline?.occupancy_option"
                                                        class="h-5 w-5 ml-2 my-auto"/>
                                    <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToDecline?.audience"
                                         class="h-5 w-5 ml-2 my-auto"/>
                                    <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToDecline?.is_loud"
                                         class="h-5 w-5 ml-2 my-auto"/>
                                </div>
                            </div>
                            <div class="flex items-start text-xs">
                                {{ $t('Created by') }}
                                <UserPopoverTooltip class="ml-2" :user="requestToDecline?.created_by"
                                                    :id="requestToDecline?.created_by?.id ?? 'deletedUserTooltip'" height="4" width="4"/>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div v-if="requestToDecline?.project">
                            <div class="xxsLight flex items-center">
                                {{ $t('assigned to') }}
                                <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                    {{ requestToDecline?.project?.name }}
                                </div>
                            </div>
                            <!--
                            <div v-for="projectLeader in requestToApprove.project.project_managers">
                                <img :data-tooltip-target="projectLeader.id"
                                     :src="projectLeader.profile_photo_url"
                                     :alt="projectLeader.name"
                                     class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip :user="projectLeader"/>
                            </div>
                            -->
                        </div>
                        <div class="xxsLight" v-else>
                            {{ $t('No project assignment') }}
                        </div>
                    </div>
                    <div class="mb-3 xsDark">
                        {{ requestToDecline?.roomName }} - {{ dayjs(requestToDecline?.start).format('DD.MM.YYYY HH:mm') }}
                        - {{ dayjs(requestToDecline?.end).format('DD.MM.YYYY HH:mm') }}
                    </div>
                    <div class="mb-3 xsDark">
                        {{ $t('Event info') }} {{ requestToDecline?.description }}
                    </div>

                    <div class="pt-2">
                        <TextareaComponent
                            :label="$t('Would you like to enter a reason?')"
                            id="adminComment"
                            v-model="declineEvent.comment"
                            rows="4"
                        />
                    </div>
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
                                  class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script>
import {XIcon} from "@heroicons/vue/solid";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Button from "@/Jetstream/Button.vue";
import {AdjustmentsIcon} from "@heroicons/vue/outline";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import {router, useForm} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";

export default {
    name: "DeclineEventModal",
    mixins: [Permissions, IconLib],
    computed: {
        dayjs() {
            return dayjs
        }
    },
    components: {
        TextareaComponent,
        BaseModal,
        FormButton,
        UserPopoverTooltip,
        NewUserToolTip,
        AdjustmentsIcon,
        Button,
        UserTooltip,
        JetDialogModal,
        XIcon
    },
    emits: ['closed', 'declined'],
    props: ['requestToDecline', 'eventTypes'],
    data() {
        return {
            declineEvent: useForm({
                eventId: this.requestToDecline?.id,
                comment: ''
            })
        }
    },
    methods: {
        closeDeclineRequestModal(bool = true) {
            this.$emit('closed', bool)
        },
        declineRequest() {
            router.put(route('events.decline', this.requestToDecline?.id), {
                comment: this.declineEvent.comment
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({
                        only: ['eventsWithoutRoom']
                    })
                },
                onFinish: () => {
                    this.closeDeclineRequestModal()
                    this.$emit('declined')
                }
            });


            /*axios.put(
                route('events.decline', this.requestToDecline?.id),
                {
                    comment: this.declineEvent.comment
                }
            ).finally(() => {
                    this.closeDeclineRequestModal();
                    this.$emit(
                        'declined',
                        this.requestToDecline?.roomId,
                        this.requestToDecline?.start,
                        this.requestToDecline?.end
                    );
                }
            );*/
        }
    }
}
</script>
