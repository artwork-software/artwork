<template>
    <app-layout :title="$t('Occupancy requests')">
        <div class="">
            <div class="max-w-screen-lg mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap">
                            <h2 class="headline1 flex w-full">{{ $t('Occupancy requests')}}</h2>
                            <div class="text-secondary subpixel-antialiased flex mt-2">
                                {{ $t('Here you can see all room occupancy requests at a glance and manage them.')}}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap">
                        <div v-for="eventRequest in event_requests" class="flex flex-wrap w-full items-center">
                            <div class="flex w-full items-center flex-wrap">
                                <div class="flex items-center w-full mt-4">
                                    <div class=" w-1/4 flex mDark">
                                        {{ eventRequest.room?.name }}:
                                    </div>
                                    <div class="flex items-center w-full">
                                        <div>
                                            <div class="block w-6 h-6 rounded-full" :style="{'backgroundColor' : eventRequest.event_type?.hex_code }" />
                                        </div>
                                        <div
                                            class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900 mr-8">
                                            {{ eventRequest.event_type?.name }}
                                            <img src="/Svgs/IconSvgs/icon_public.svg" v-if="eventRequest.audience"
                                                 class="h-5 w-5 ml-2 my-auto"/>
                                            <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="eventRequest.is_loud"
                                                 class="h-5 w-5 ml-2  my-auto"/>
                                        </div>

                                        <div class="flex w-full xsDark whitespace-nowrap ml-4"
                                             v-if="eventRequest.start_time.split(',')[0] === eventRequest.end_time.split(',')[0]">
                                            {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }}, {{
                                                eventRequest.start_time.split(',')[0]
                                            }},{{ eventRequest.start_time.split(',')[1] }}
                                            - {{ eventRequest.end_time.split(',')[1] }}
                                        </div>
                                        <div class="flex w-full xsDark whitespace-nowrap ml-3" v-else>
                                            {{ getGermanWeekdayAbbreviation(eventRequest.start_time_weekday) }},
                                            {{ eventRequest.start_time }} -
                                            {{ getGermanWeekdayAbbreviation(eventRequest.end_time_weekday) }},
                                            {{ eventRequest.end_time }}
                                        </div>
                                        <div class="flex w-full xsDark whitespace-nowrap ml-4">
                                            {{eventRequest.name}}
                                        </div>
                                        <button v-if="this.hasAdminRole()" @click="openApproveRequestModal(eventRequest)" type="button"
                                                class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-artwork-buttons-create  focus:outline-none hover:bg-success">
                                            <CheckIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                        </button>
                                        <button v-if="this.hasAdminRole()" @click="openDeclineRequestModal(eventRequest)" type="button"
                                                class="flex my-auto ml-6 p-0.5 items-center border border-transparent rounded-full shadow-sm text-white bg-artwork-buttons-create focus:outline-none hover:bg-error">
                                            <XIcon class="h-4 w-4 flex flex-shrink" aria-hidden="true"/>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center w-full ml-44 justify-between">
                                    <div v-if="eventRequest.project" class="w-80">
                                        <div class="ml-16 xsLight flex items-center">
                                            {{ $t('assigned to')}}
                                            <Link
                                                :href="route('projects.tab',{project: eventRequest.project.id, projectTab: this.first_project_calendar_tab_id})"
                                                class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                                {{ eventRequest.project?.name }}
                                            </Link>
                                        </div>

                                        <div v-for="projectLeader in eventRequest.project.project_managers">
                                            <UserPopoverTooltip :user="projectLeader" :id="projectLeader.id" height="7" width="7"/>
                                        </div>

                                    </div>
                                    <div class="xsLight w-64 ml-16" v-else>
                                        {{$t('Not assigned to a project')}}
                                    </div>
                                    <div class="flex xsLight items-center">
                                        {{ $t('requested')}}:
                                        <UserPopoverTooltip :user="eventRequest.created_by" :id="eventRequest.created_by.id" height="7" width="7" class="ml-2"/>
                                        <span class="ml-2 xsLight"> {{ eventRequest.created_at }}</span>
                                    </div>

                                    <div>

                                    </div>
                                </div>

                                <div class="flex ml-60 mt-2 xsLight items-center w-full"
                                     v-if="eventRequest.description">
                                    {{ eventRequest.description }}
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Approve Request Modal -->
        <BaseModal @closed="closeApproveRequestModal" v-if="showApproveRequestModal" modal-image="/Svgs/Overlays/illu_success.svg" >
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{ $t('Confirm room occupancy')}}
                    </div>
                    <div class="successText">
                        {{ $t('Are you sure you want to accept the room allocation?')}}
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <div>
                                        <div class="block w-6 h-6 rounded-full" :style="{'backgroundColor' : requestToApprove.event_type?.hex_code }" />
                                    </div>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToApprove.event_type.name }}
                                        <AdjustmentsIcon v-if="requestToApprove.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToApprove.audience"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToApprove.is_loud"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full xsDark whitespace-nowrap ml-3"
                                         v-if="requestToApprove.start_time.split(',')[0] === requestToApprove.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }}, {{
                                            requestToApprove.start_time.split(',')[0]
                                        }},{{ requestToApprove.start_time.split(',')[1] }}
                                        - {{ requestToApprove.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full xsDark whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.start_time_weekday) }},
                                        {{ requestToApprove.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToApprove.end_time_weekday) }},
                                        {{ requestToApprove.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToApprove.project" class="w-80">
                                    <div class="ml-16 xsLight flex items-center">
                                        {{$t('assigned to')}}
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToApprove.project.name }}
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
                                <div class="xsLight ml-10" v-else>
                                    {{ $t('Not assigned to a project')}}
                                </div>
                                <div class="flex xsLight items-center">
                                    {{ $t('requested')}}:
                                    <UserPopoverTooltip :user="requestToApprove.created_by" :id="requestToApprove.created_by.id" height="7" width="7" class="ml-2"/>
                                    <span class="ml-2 xsLight"> {{ requestToApprove.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 xsLight items-center w-full"
                                 v-if="requestToApprove.description">
                                {{ requestToApprove.description }}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="approveRequest">
                            {{$t('Commitments')}}
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeApproveRequestModal"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
        <!-- Decline Request Modal -->
        <BaseModal @closed="closeDeclineRequestModal" v-if="showDeclineRequestModal" modal-image="/Svgs/Overlays/illu_warning.svg" >
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Cancel room reservation')}}
                    </div>
                    <div class="errorText">
                        {{$t('Are you sure you want to cancel the room reservation?')}}
                    </div>
                    <div class="flex flex-wrap w-full items-center">
                        <div class="flex w-full items-center flex-wrap">

                            <div class="flex items-center w-full mt-4">
                                <div class="flex items-center ml-12 w-full">
                                    <div>
                                        <div class="block w-6 h-6 rounded-full" :style="{'backgroundColor' : requestToDecline.event_type?.hex_code }" />
                                    </div>
                                    <div
                                        class="whitespace-nowrap ml-2 text-lg flex leading-6 font-bold font-lexend text-gray-900">
                                        {{ requestToDecline.event_type?.name }}
                                        <AdjustmentsIcon v-if="requestToDecline.occupancy_option"
                                                         class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_public.svg" v-if="requestToDecline.audience"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                        <img src="/Svgs/IconSvgs/icon_loud.svg" v-if="requestToDecline.is_loud"
                                             class="h-5 w-5 ml-2 my-auto"/>
                                    </div>

                                    <div class="flex w-full xsDark whitespace-nowrap ml-3"
                                         v-if="requestToDecline.start_time.split(',')[0] === requestToDecline.end_time.split(',')[0]">
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }}, {{
                                            requestToDecline.start_time.split(',')[0]
                                        }},{{ requestToDecline.start_time.split(',')[1] }}
                                        - {{ requestToDecline.end_time.split(',')[1] }}
                                    </div>
                                    <div class="flex w-full xsDark whitespace-nowrap ml-3" v-else>
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.start_time_weekday) }},
                                        {{ requestToDecline.start_time }} -
                                        {{ getGermanWeekdayAbbreviation(requestToDecline.end_time_weekday) }},
                                        {{ requestToDecline.end_time }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center w-full ml-2 justify-between">
                                <div v-if="requestToDecline.project" class="w-80">
                                    <div class="ml-16 xsLight flex items-center">
                                        {{$t('assigned to')}}
                                        <div class="text-secondary font-black leading-3 subpixel-antialiased ml-2">
                                            {{ requestToDecline.project?.name }}
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
                                <div class="xsLight ml-10" v-else>
                                    {{ $t('Not assigned to a project')}}
                                </div>
                                <div class="flex xsLight items-center">
                                    {{$t('requested')}}:
                                    <UserPopoverTooltip :user="requestToDecline.created_by" :id="requestToDecline.created_by.id" height="7" width="7" class="ml-2"/>
                                    <span class="ml-2"> {{ requestToDecline.created_at }}</span>
                                </div>
                                <div>

                                </div>
                            </div>
                            <div class="flex ml-12 mt-2 xsLight items-center w-full"
                                 v-if="requestToDecline.description">
                                {{ requestToDecline.description }}
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
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                        </div>
                    </div>
                </div>
        </BaseModal>
    </app-layout>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    DotsVerticalIcon,
    ChevronDownIcon,
    AdjustmentsIcon,
} from '@heroicons/vue/outline'
import {ChevronUpIcon, PlusSmIcon, CheckIcon, XCircleIcon, XIcon} from '@heroicons/vue/solid'

import {
    Listbox,
    ListboxButton, ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems, Switch
} from '@headlessui/vue'
import Button from "@/Jetstream/Button.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import JetSecondaryButton from "@/Jetstream/SecondaryButton.vue";
import Checkbox from "@/Layouts/Components/Checkbox.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Link} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";


export default defineComponent({
    mixins: [Permissions],
    components: {
        BaseModal,
        FormButton,
        UserPopoverTooltip,
        ListboxLabel,
        SvgCollection,
        Button,
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetButton,
        JetDialogModal,
        JetInput,
        JetInputError,
        JetSecondaryButton,
        ChevronDownIcon,
        ChevronUpIcon,
        Checkbox,
        XIcon,
        XCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Link,
        AdjustmentsIcon,
        Switch,
        UserTooltip
    },
    props: ['event_requests', 'first_project_calendar_tab_id'],
    methods: {
        usePage,
        getGermanWeekdayAbbreviation(englishWeekday) {
            switch (englishWeekday) {
                case 'Monday':
                    return 'Mo';
                case 'Tuesday':
                    return 'Di';
                case 'Wednesday':
                    return 'Mi';
                case 'Thursday':
                    return 'Do';
                case 'Friday':
                    return 'Fr';
                case 'Saturday':
                    return 'Sa';
                case 'Sunday':
                    return 'So';
            }
        },
        openApproveRequestModal(eventRequest) {
            this.requestToApprove = eventRequest;
            this.showApproveRequestModal = true;
        },
        closeApproveRequestModal() {
            this.showApproveRequestModal = false;
            this.requestToApprove = null;
        },
        openDeclineRequestModal(eventRequest) {
            this.requestToDecline = eventRequest;
            this.showDeclineRequestModal = true;
        },
        closeDeclineRequestModal() {
            this.showDeclineRequestModal = false;
            this.requestToDecline = null;
        },
        approveRequest() {
            this.answerRequestForm.accepted = true;
            this.answerRequestForm.put(route('events.accept', {event: this.requestToApprove.id}));
            this.closeApproveRequestModal();
        },
        declineRequest() {
            this.answerRequestForm.accepted = false;
            this.answerRequestForm.put(route('events.accept', {event: this.requestToDecline.id}));
            this.closeDeclineRequestModal();
        }
    },
    data() {
        return {
            showApproveRequestModal: false,
            requestToApprove: null,
            showDeclineRequestModal: false,
            requestToDecline: null,
            answerRequestForm: useForm({
                accepted: false,
            }),
        }
    },
})
</script>
