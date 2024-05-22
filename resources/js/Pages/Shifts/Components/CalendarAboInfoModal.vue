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
            const url = this.$page.props.user.shift_calendar_abo.calendar_abo_url;
            navigator.clipboard.writeText(url).then(() => {
                this.copyText = ' Kopiert';
                setTimeout(() => {
                    this.copyText = '';
                }, 2000);
            });
        },
        downloadICSFile() {
            const url = this.$page.props.user.shift_calendar_abo.calendar_abo_url;
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Schichtkalender.ics';
            a.click();
        }
    },
    data(){
        return {
            copyText: '',
            instructions: [
                {
                    system: 'Google Kalender',
                    description: 'So abonnierst du einen Kalender in Google Kalender:',
                    steps: [
                        'Öffne Google Kalender.',
                        'Klicke auf das Pluszeichen neben "Weitere Kalender" und wähle "Per URL".',
                        'Gib die URL (Siehe Textbox) ein und klicke auf "Kalender hinzufügen".',
                    ]
                },
                {
                    system: 'Apple Kalender',
                    description: 'So abonnierst du einen Kalender in der Apple Kalender-App:',
                    steps: [
                        'Öffne die Kalender-App.',
                        'Gehe zu "Datei" > "Neues Kalenderabonnement".',
                        'Gib die URL (Siehe Textbox) ein und klicke auf "Abonnieren".',
                    ]
                },
                {
                    system: 'Thunderbird Lightning',
                    description: 'So abonnierst du einen Kalender in Thunderbird Lightning:',
                    steps: [
                        'Öffne Thunderbird und gehe zu "Kalender".',
                        'Klicke mit der rechten Maustaste auf das Kalender-Panel und wähle "Neuer Kalender".',
                        'Wähle "Im Netzwerk" und klicke auf "Weiter".',
                        'Wähle "iCalendar (ICS)" und gib die URL (Siehe Textbox) ein. Klicke auf "Weiter".',
                        'Gib dem Kalender einen Namen und wähle eine Farbe. Klicke auf "Weiter" und dann auf "Fertig".',
                    ]
                },
                {
                    system: 'Yahoo Kalender',
                    description: 'So abonnierst du einen Kalender in Yahoo Kalender:',
                    steps: [
                        'Öffne Yahoo Kalender.',
                        'Klicke auf das Zahnradsymbol und wähle "Kalender abonnieren".',
                        'Gib die URL (Siehe Textbox) in das Feld "iCal-Adressse" ein und klicke auf "Kalender abonnieren".',
                    ]
                },
                {
                    system: 'Zoho Kalender',
                    description: 'So abonnierst du einen Kalender in Zoho Kalender:',
                    steps: [
                        'Öffne Zoho Kalender.',
                        'Klicke auf das Pluszeichen neben "Meine Kalender" und wähle "Kalender abonnieren".',
                        'Wähle "Über URL" und gib die URL (Siehe Textbox) ein. Klicke auf "Abonnieren".',
                    ]
                },
                {
                    system: 'Android Kalender',
                    description: 'So abonnierst du einen Kalender in der Android Kalender-App:',
                    steps: [
                        'Öffne die Kalender-App auf deinem Android-Gerät.',
                        'Gehe zu "Einstellungen" und wähle "Kalender hinzufügen".',
                        'Wähle "Über URL hinzufügen" und gib die URL (Siehe Textbox) ein. Klicke auf "Abonnieren".',
                    ]
                },
                {
                    system: 'Outlook.com',
                    description: 'So abonnierst du einen Kalender in Outlook.com:',
                    steps: [
                        'Öffne Outlook.com und gehe zu "Kalender".',
                        'Klicke auf "Kalender hinzufügen" und wähle "Aus dem Internet".',
                        'Gib die URL (Siehe Textbox) ein und klicke auf "Kalender importieren".',
                    ]
                }
            ]
        }
    }
}
</script>

<template>
    <BaseModal v-if="true" @closed="closeModal(true)">


        <div class="w-full">
            <h2 class="headline1 mb-6">
                Anleitung zum Abonnieren des Kalenders
            </h2>
           <p class="text-secondary subpixel-antialiased text-sm">
               Hier finden Sie detaillierte Anleitungen, wie Sie den Kalender in verschiedenen Anwendungen abonnieren können. Folgen Sie den entsprechenden Links für Ihre bevorzugte Kalenderanwendung, um den Abonnementprozess abzuschließen und immer auf dem neuesten Stand Ihrer Termine zu bleiben.
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
                <label for="calendar-url" class="block text-sm font-bold text-gray-700 ">Kalender-URL</label>
                <p class="text-secondary subpixel-antialiased text-sm mb-2">Nutzen Sie die folgende URL, um den Kalender manuell in Ihre Kalenderanwendung zu importieren:</p>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input @click="$event.target.select()" type="text" name="calendar-url" id="calendar-url" class="focus:ring-primary focus:border-primary block w-full pr-10 sm:text-sm border-gray-300 rounded-md" :value="$page.props.user.shift_calendar_abo.calendar_abo_url" readonly>
                    <button type="button" class="absolute flex items-center inset-y-0 right-0 px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-hover focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75" @click="copyAboUrlToClipboard">
                        <IconCircleCheck class="h-4 w-4 mr-1" v-if="copyText"/> {{ copyText ? copyText : 'Kopieren' }}
                    </button>
                </div>
                <span class="text-xs flex items-start gap-x-1 mt-1 text-artwork-buttons-create">
                    <IconInfoCircle class="h-5 w-5" stroke-width="1.5" /> Klicken Sie auf "Kopieren", um die URL in Ihre Zwischenablage zu kopieren und in die gewünschte Kalenderanwendung einzufügen.
                </span>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mt-4">
                <div>
                    <div class="text-secondary subpixel-antialiased text-sm mb-2">
                        Laden Sie die ICS-Datei herunter, um den Kalender direkt in Ihre Anwendung zu importieren:
                    </div>
                    <div class="flex items-center text-xs text-artwork-buttons-hover underline cursor-pointer" @click="downloadICSFile">
                        ICS Datei herunterladen
                    </div>
                </div>

                <FormButton @click="closeModal(true)" text="Schließen">
                    Schließen
                </FormButton>
            </div>
        </div>

    </BaseModal>
</template>

<style scoped>

</style>
