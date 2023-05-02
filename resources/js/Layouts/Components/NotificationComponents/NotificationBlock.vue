<template>
    <div class="flex justify-between items-start my-5">
        <div class="flex items-start gap-3">
            <img :src="'/Svgs/IconSvgs/icon_notification_' + notification.data.icon + '.svg'" alt="">
            <div class="">
                <div class="flex items-center gap-4">
                    <h4 class="sDark">{{ notification.data.title }}</h4>
                    <div class="flex items-center gap-2 xxsLight" v-if="notification.data.created_by">
                        {{ notification.data.created_at }}
                        von
                        <NewUserToolTip :id="notification?.id" :user="notification.data?.created_by" height="5" width="5"/>
                    </div>
                </div>
                <p v-if="notification.data.room">
                    <a href="">{{ notification.data.room.name }}</a>  |  {{ this.eventTypes.find(type => type.id === notification.data.event.event_type_id)?.name }}, {{ notification.data.event.eventName }}  |  {Projekt}  |  {dd.mm.jjjj, hh:mm} - {dd.mm.jjjj, hh:mm}
                </p>
                <pre>
                    {{ eventTypes }}
                </pre>
                <pre>
                    {{ notification.data }}
                </pre>
                <p v-for="comment in notification.data?.eventComments">
                    <span v-if="comment.is_admin_comment">
                        {{ comment.comment }}
                    </span>
                </p>
                <p>
                    <!-- comments, event infos, project info -->

                </p>
                <div class="" v-if="notification.data.showHistory">
                    <div @click="openHistory" class="xxsLight cursor-pointer items-center flex text-buttonBlue">
                        <ChevronRightIcon class="h-3 w-3" />
                        <span>
                             Verlauf ansehen
                        </span>
                    </div>
                </div>
                <NotificationButtons
                    :buttons="notification.data.buttons"
                    @openDeclineModal="showDeclineModal = true"
                />
            </div>
        </div>
        <div class="">
            <img v-show="notification.hovered"
                 v-if="notification.data?.changeType !== 'BUDGET_VERIFICATION_REQUEST'"
                 src="/Svgs/IconSvgs/icon_archive_white.svg"
                 class="h-6 w-6 p-1 ml-1 flex cursor-pointer bg-buttonBlue rounded-full"
                 aria-hidden="true"/>
        </div>
    </div>

    <DeclineEventModal
        v-if="showDeclineModal"
        :request-to-decline="notification.data?.event"
        @closed="showDeclineModal = false"
    />
</template>

<script>
import NotificationButtons from "@/Layouts/Components/NotificationComponents/NotificationButtons.vue";
import {ChevronRightIcon} from "@heroicons/vue/solid";
import {Inertia} from "@inertiajs/inertia";
import DeclineEventModal from "@/Layouts/Components/DeclineEventModal.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";

export default {
    name: "NotificationBlock",
    components: {
        NewUserToolTip,
        DeclineEventModal,
        NotificationButtons, ChevronRightIcon
    },
    props: ['notification', 'eventTypes'],
    data(){
        return {
            showDeclineModal: false
        }
    },
    methods: {
        openHistory(){
            Inertia.reload({
                data: {
                    showHistory: true,
                    historyType: this.notification.data?.historyType,
                    modelId: this.notification.data?.modelId,
                }
            })
        }
    }
}
</script>

<style scoped>

</style>
