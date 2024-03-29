<template>
    <jet-dialog-modal :show="props.show" @close="props.closeModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_new.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Entry & registration') }}
                </div>
                <XIcon @click="props.closeModal"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary">
                    {{ $t('Define modalities for entry, registration and more. You can assign these to all public events that are assigned to this project.') }}
                </div>
                <input :placeholder="numOfGuests !== undefined && numOfGuests !== null && numOfGuests !== '' ? numOfGuests : $t('How many people are allowed/expected to attend?')"
                       id="num_of_guests"
                       v-model="numOfGuests"
                       class="mt-10 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <input :placeholder="entryFee !== undefined && entryFee !== null && entryFee !== '' ? entryFee : $t('How much is the entrance fee?')"
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
                    <div class="text-sm text-secondary ml-2">{{ $t('Registration required') }}</div>
                </div>
                <div v-if="registrationRequired" class="mt-4">
                <textarea :placeholder="registerBy !== '' && registerBy !== null && registerBy !== undefined ? registerBy : $t('How can people register?')"
                          id="description"
                          v-model="registerBy"
                          rows="4"
                          class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                <input :placeholder="registrationDeadline !== '' && registrationDeadline !== null && registrationDeadline !== undefined ? registrationDeadline : $t('Is there a registration deadline? (Until when?)')"
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
                    <div class="text-sm text-secondary ml-2">{{ $t('Closed society') }}</div>
                </div>
            </div>
            <div class="justify-center flex w-full mt-6 mb-3">
                <FormButton :text="$t('Save')"
                           @click="updateProjectEntranceData"/>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import {useForm} from "@inertiajs/inertia-vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    show: Boolean,
    closeModal: Function,
    project: Object
})
const numOfGuests = ref(props.project.num_of_guests)
const entryFee = ref(props.project.entry_fee)
const registrationRequired = ref(props.project.registration_required)
const closedSociety = ref(props.project.closed_society)
const registerBy = ref(props.project.register_by)
const registrationDeadline = ref(props.project.registration_deadline)

const entranceForm = useForm({
    num_of_guests: null,
    entry_fee: null,
    registration_required: false,
    closed_society: false,
    register_by: null,
    registration_deadline: null
})

const updateProjectEntranceData = () => {
    if (!registrationRequired.value) {
        registerBy.value = "";
        registrationDeadline.value = "";
        entranceForm.register_by = "";
        entranceForm.registration_deadline = "";
    } else {
        entranceForm.register_by = registerBy.value;
        entranceForm.registration_deadline = registrationDeadline.value;
    }
    entranceForm.num_of_guests = numOfGuests.value;
    entranceForm.entry_fee = entryFee.value;
    entranceForm.registration_required = registrationRequired.value;
    entranceForm.closed_society = closedSociety.value;

    entranceForm.patch(`/projects/${props.project.id}/entrance`)

    props.closeModal()
}
</script>
