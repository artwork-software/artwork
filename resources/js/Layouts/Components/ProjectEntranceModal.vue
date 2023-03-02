
<template>
    <jet-dialog-modal :show="props.show" @close="props.closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_new.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Eintritt & Anmeldung
                </div>
                <XIcon @click="props.closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary">Lege Modalitäten für den Eintritt, Anmeldung und mehr fest.
                    Diese kannst du auf alle öffentlichen Termine, die diesem Projekt zugeordnet sind zuweisen.</div>

                <input placeholder="Wie viele Personen dürfen teilnehmen/werden erwartet?"
                       id="num_of_guests"
                       v-model="numOfGuests"
                       class="mt-10 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <input placeholder="Wie hoch ist der Eintritt?"
                       id="entry_fee"
                       v-model="entryFee"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <div class="flex items-center mt-4">
                    <label for="registration-required"
                           class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox"
                               v-model="registrationRequired"
                               id="registration-required"
                               class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                        </div>
                    </label>
                    <div class="text-sm text-secondary ml-2">Anmeldung erforderlich</div>
                </div>
                <div v-if="registrationRequired" class="mt-4">
                <textarea placeholder="Wie können sich die Personen anmelden?"
                          id="description"
                          v-model="registerBy"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <input placeholder="Gibt es eine Anmeldefrist? (Bis wann?)"
                       id="entry_fee"
                       v-model="registrationDeadline"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                </div>

                <div class="flex items-center mt-4">
                    <label for="closed-society"
                           class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox"
                               v-model="closedSociety"
                               id="closed-society"
                               class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 rounded-full
                            peer-checked:after:translate-x-full peer-checked:after:border-white
                            after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300
                            after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                        </div>
                    </label>
                    <div class="text-sm text-secondary ml-2">Geschlossene Gesellschaft</div>
                </div>
            </div>
            <div class="justify-center flex w-full mt-6 mb-3">
                <AddButton text="Speichern" mode="modal" class="px-12 py-3"
                           @click="updateProjectEntranceData"/>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {useForm} from "@inertiajs/inertia-vue3";

const props = defineProps({
    show: Boolean,
    closeModal: Function,
    project: Object
})

const numOfGuests = ref(null)
const entryFee = ref(null)
const registrationRequired = ref(props.project.registration_required)
const closedSociety = ref(props.project.closed_society)
const registerBy = ref(null)
const registrationDeadline = ref(null)

const entranceForm = useForm({
    num_of_guests: "",
    entry_fee: "",
    registration_required: false,
    closed_society: false,
    register_by: "",
    registration_deadline: ""
})

const updateProjectEntranceData = () => {
    entranceForm.num_of_guests = numOfGuests.value !== null ? numOfGuests.value : props.project.num_of_guests;
    entranceForm.entry_fee = entryFee.value !== null ? entryFee.value : props.project.entry_fee;
    entranceForm.registration_required = registrationRequired.value;
    entranceForm.closed_society = closedSociety.value;
    entranceForm.register_by = registerBy.value !== null ? registerBy.value : props.project.register_by;
    entranceForm.registration_deadline = registrationDeadline.value !== null ? registrationDeadline.value : props.project.registration_deadline;

    entranceForm.patch(`/projects/${props.project.id}/entrance`)

    numOfGuests.value = null
    entryFee.value = null
    registerBy.value = null
    registrationDeadline.value = null

    props.closeModal()
}

</script>

<style scoped>

</style>
