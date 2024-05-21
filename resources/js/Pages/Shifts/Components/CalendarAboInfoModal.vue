<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
export default {
    name: "CalendarAboInfoModal",
    components: {
        FormButton,
        BaseModal, Disclosure, DisclosureButton, DisclosurePanel
    },
    methods: {
        closeModal(bool) {
            this.$emit('close', bool)
        },
    },
    data(){
        return {
            instructions: [
                {
                    system: 'Outlook',
                    description: 'So abonnierst du einen Kalender in Outlook:',
                    steps: [
                        'Öffne Outlook',
                        'Gehe zu "Kalender" und klicke auf "Kalender öffnen" > "Aus dem Internet".',
                        'Gib die URL (Siehe Textbox) ein und klicke auf "OK".',
                    ]
                },
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
            <h4 class="font-bold text-base">
                Anleitung zum Abonnieren des Kalenders
            </h4>
            <div class="mx-auto w-full">
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
            <div class="mt-4">
                <label for="calendar-url" class="block text-sm font-medium text-gray-700">Kalender-URL</label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="text" name="calendar-url" id="calendar-url" class="focus:ring-primary focus:border-primary block w-full pr-10 sm:text-sm border-gray-300 rounded-md" :value="$page.props.user.shift_calendar_abo.calendar_abo_url" readonly disabled>
                    <button type="button" class="absolute inset-y-0 right-0 px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-hover focus:outline-none focus-visible:ring focus-visible:ring-purple-500/75">
                        Kopieren
                    </button>
                </div>
            </div>
        </div>

        <div>
            <div class="flex justify-end mt-4">
                <FormButton @click="closeModal(true)" text="Schließen">
                    Schließen
                </FormButton>
            </div>
        </div>

    </BaseModal>
</template>

<style scoped>

</style>
