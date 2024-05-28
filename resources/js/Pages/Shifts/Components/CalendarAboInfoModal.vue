<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
export default {
    name: "CalendarAboInfoModal",
    mixins: [IconLib],
    components: {
        FormButton,
        BaseModal, Disclosure, DisclosureButton, DisclosurePanel
    },
    methods: {
        closeModal(bool) {
            this.$emit('close', bool)
        },
        copyAboUrlToClipboard() {
            if (!navigator.clipboard) {
                return;
            }
            let url = '';
            if (this.is_shift_calendar_abo) {
                url = this.$page.props.user.shift_calendar_abo.calendar_abo_url;
            } else {
                url = this.$page.props.user.calendar_abo.calendar_abo_url;
            }
            navigator.clipboard.writeText(url).then(() => {
                this.copyText = this.$t('Copied');
                setTimeout(() => {
                    this.copyText = '';
                }, 2000);
            });
        },
        downloadICSFile() {
            let url = '';
            if (this.is_shift_calendar_abo) {
                url = this.$page.props.user.shift_calendar_abo.calendar_abo_url;
            } else {
                url = this.$page.props.user.calendar_abo.calendar_abo_url;
            }
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Schichtkalender.ics';
            a.click();
        }
    },
    props: {
        is_shift_calendar_abo: {
            type: Boolean,
            required: false
        }
    },
    data(){
        return {
            copyText: '',
            instructions: [
                {
                    system: this.$t('Google Calendar'),
                    description: this.$t('How to subscribe to a calendar in Google Calendar:'),
                    steps: [
                        this.$t('Open Google Calendar.'),
                        this.$t('Click on the plus sign next to “More calendars” and select “By URL”.'),
                        this.$t('Enter the URL (see text box) and click on “Add calendar”.'),
                    ]
                },
                {
                    system: this.$t('Apple Calendar'),
                    description: this.$t('How to subscribe to a calendar in Apple Calendar:'),
                    steps: [
                        this.$t('Open the Calendar app.'),
                        this.$t('Go to "File" > "New Calendar Subscription".'),
                        this.$t('Enter the URL (see text box) and click on "Subscribe".'),
                    ]
                },
                {
                    system: this.$t('Thunderbird Lightning'),
                    description: this.$t('How to subscribe to a calendar in Thunderbird Lightning:'),
                    steps: [
                        this.$t('Open Thunderbird and go to "Calendar".'),
                        this.$t('Right-click on the calendar panel and select "New Calendar".'),
                        this.$t('Select "On the Network" and click "Next".'),
                        this.$t('Select "iCalendar (ICS)" and enter the URL (see text box). Click "Next".'),
                        this.$t('Give the calendar a name and choose a color. Click "Next" and then "Finish".'),
                    ]
                },
                {
                    system: this.$t('Yahoo Calendar'),
                    description: this.$t('How to subscribe to a calendar in Yahoo Calendar:'),
                    steps: [
                        this.$t('Open Yahoo Calendar.'),
                        this.$t('Click on the gear icon and select "Subscribe to Calendar".'),
                        this.$t('Enter the URL (see text box) in the "iCal Address" field and click "Subscribe to Calendar".'),
                    ]
                },
                {
                    system: this.$t('Zoho Calendar'),
                    description: this.$t('How to subscribe to a calendar in Zoho Calendar:'),
                    steps: [
                        this.$t('Open Zoho Calendar.'),
                        this.$t('Click on the plus sign next to "My Calendars" and select "Subscribe to Calendar".'),
                        this.$t('Select "By URL" and enter the URL (see text box). Click "Subscribe".'),
                    ]
                },
                {
                    system: this.$t('Android Calendar'),
                    description: this.$t('How to subscribe to a calendar in the Android Calendar app:'),
                    steps: [
                        this.$t('Open the calendar app on your Android device.'),
                        this.$t('Go to "Settings" and select "Add calendar".'),
                        this.$t('Select "Add by URL" and enter the URL (see text box). Click "Subscribe".'),
                    ]
                },
                {
                    system: this.$t('Outlook.com'),
                    description: this.$t('How to subscribe to a calendar in Outlook.com:'),
                    steps: [
                        this.$t('Open Outlook.com and go to "Calendar".'),
                        this.$t('Click on "Add calendar" and select "From internet".'),
                        this.$t('Enter the URL (see text box) and click on "Import calendar".'),
                    ]
                }
            ]

        }
    }
}
</script>

<template>
    <BaseModal v-if="true" @closed="closeModal(true)" modal-image="/Svgs/Overlays/illu_appointment_new.svg">

        <div class="h-[calc(100%-10rem)] overflow-scroll">
            <div class="w-full">
                <h2 class="headline1 mb-6">
                    {{ $t('Instructions for subscribing to the calendar') }}
                </h2>
                <p class="text-secondary subpixel-antialiased text-sm">
                    {{ $t('Here you will find detailed instructions on how to subscribe to the calendar in various applications. Follow the appropriate links for your favorite calendar application to complete the subscription process and stay up to date with your appointments. You can also manually import the calendar into your calendar application by downloading the ICS file.') }}
                </p>
                <div class="mx-auto w-full my-5">
                    <Disclosure v-for="instruction in instructions" as="div" class="mt-2" v-slot="{ open }">
                        <DisclosureButton
                            class="flex w-full justify-between rounded-lg bg-artwork-buttons-create/20 px-4 py-2 text-left text-sm font-medium text-artwork-buttons-create hover:bg-artwork-buttons-create/30 focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75">
                            <span>{{ instruction.system }}</span>
                            <ChevronUpIcon :class="open ? 'rotate-180 transform' : ''" class="h-5 w-5 text-purple-500"/>
                        </DisclosureButton>
                        <DisclosurePanel class="px-4 pb-2 pt-4 text-sm text-gray-500">
                            <p class="mb-3">
                                {{ instruction.description }}
                            </p>
                            <ul class="list-disc list-inside">
                                <li v-for="step in instruction.steps">
                                    {{ step }}
                                </li>
                            </ul>
                        </DisclosurePanel>
                    </Disclosure>
                </div>
            </div>

            <div>
                <!-- Textbox to copy the calendar url -->
                <div class="my-10">
                    <label for="calendar-url" class="block text-sm font-bold text-gray-700 ">{{ $t('Calendar URL') }}</label>
                    <p class="text-secondary subpixel-antialiased text-sm mb-2">
                        {{ $t('Use the following URL to import the calendar into your calendar application and stay up to date:') }}
                    </p>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input @click="$event.target.select()" type="text" name="calendar-url" id="calendar-url" class="focus:ring-primary focus:border-primary block w-full pr-10 sm:text-sm border-gray-300 rounded-md" :value="is_shift_calendar_abo ? $page.props.user.shift_calendar_abo.calendar_abo_url : $page.props.user.calendar_abo.calendar_abo_url" readonly>
                        <button type="button" class="absolute flex items-center inset-y-0 right-0 px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-hover focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75" @click="copyAboUrlToClipboard">
                            <IconCircleCheck class="h-4 w-4 mr-1" v-if="copyText"/> {{ copyText ? copyText : $t('Copy') }}
                        </button>
                    </div>
                    <span class="text-xs flex items-center gap-x-1 mt-1 text-artwork-buttons-create">
                    <IconInfoCircle class="h-5 w-5" stroke-width="1.5" /> {{ $t('Click on “Copy” to copy the URL to your clipboard and paste it into the desired calendar application.') }}
                </span>
                </div>
            </div>

            <div>
                <div class="flex items-start gap-x-3 justify-between mt-4">
                    <div>
                        <div class="text-secondary subpixel-antialiased text-sm mb-2">
                            {{ $t('Download the ICS file to manually import the calendar directly into your application:') }}
                        </div>
                        <div class="flex items-center text-xs text-artwork-buttons-hover underline cursor-pointer" @click="downloadICSFile">
                            {{ $t('Download ICS file') }}
                        </div>

                    </div>

                    <FormButton @click="closeModal(true)" :text="$t('Close')">
                        {{ $t('Close') }}
                    </FormButton>
                </div>
                <div class="text-artwork-messages-error subpixel-antialiased text-xs mt-2">
                    {{ $t('Attention - The ICS file is not updated automatically. You must re-import the file regularly to keep it up to date.') }}
                </div>
            </div>
        </div>


    </BaseModal>
</template>

<style scoped>

</style>
