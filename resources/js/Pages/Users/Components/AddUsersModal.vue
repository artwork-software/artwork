<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('New user') }}
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    {{ $t('Who would you like to add to artwork? You can add users with artwork access or external employees, as well as service providers (companies) without artwork access.')}}
                                </p>

                                <div class="mt-10">
                                    <div class="px-6">
                                        <fieldset class="mt-4">
                                            <div class="space-y-4">
                                                <div v-for="addMethod in addMethods" :key="addMethod.id" class="flex items-center">
                                                    <input v-model="selectedMethod" :key="addMethod.id" :value="addMethod" :id="addMethod.id" name="notification-method" type="radio" :checked="addMethod.id === 'intern'" class="h-4 w-4 border-gray-300 text-artwork-buttons-create focus:ring-indigo-600" />
                                                    <label :for="addMethod.id" class="ml-3 block text-sm font-medium leading-6 text-gray-900">{{ addMethod.title }}</label>
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-5">
                                <FormButton
                                    :text="$t('Add')"
                                    @click="addUsers"
                                />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
<script>
import {defineComponent} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import Input from "@/Jetstream/Input.vue";
import {ChevronDownIcon, PlusCircleIcon} from "@heroicons/vue/outline";
import ConfirmationModal from "@/Jetstream/ConfirmationModal.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle, Listbox,
    ListboxButton,
    ListboxOption, ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default defineComponent({
    name: "AddUsersModal",
    components: {
        FormButton,
        ConfirmationModal,
        CheckIcon, ChevronDownIcon,
        Input,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel, PlusCircleIcon, ListboxButton, ListboxOption, ListboxOptions, Listbox
    },
    data(){
        return {
            open: true,
            addMethods: [
                { id: 'intern', title: this.$t('Internal user') },
                { id: 'extern', title: this.$t('External employee') },
                { id: 'service_provider', title: this.$t('Service provider (company)') },
            ],
            selectedMethod: { id: 'intern', title: this.$t('Internal user') },
        }
    },
    emits: ['closeModal', 'openUserModal'],
    methods: {
        closeModal(){
            this.$emit('closeModal', true)
        },
        addUsers(){
            if(this.selectedMethod.id === 'intern'){
                this.closeModal();
                this.$emit('openUserModal', true)
            } else if (this.selectedMethod.id === 'extern'){
                this.$inertia.post(route('freelancer.add'))
            } else {
                this.$inertia.post(route('service_provider.add'))
            }
        }
    }
})
</script>


<style scoped>

</style>
