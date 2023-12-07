<template>
    <div class="grid grid-cols-1 sm:grid-cols-9 group flex items-center mb-7 pb-7 border-b">
        <div class="col-span-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <input type="text" v-model="contactData.first_name" name="first_name" id="first_name" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" placeholder="Vorname" />
                </div>
                <div class="col-span-1">
                    <input type="text" v-model="contactData.last_name" name="last_name" id="last_name" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" placeholder="Nachname" />
                </div>
                <div class="col-span-1">
                    <input type="email" v-model="contactData.email" name="email" id="email" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" placeholder="Email" />
                </div>
                <div class="col-span-1">
                    <input type="text" v-model="contactData.phone_number" name="phone_number" id="phone_number" class="block w-full border-0 py-2.5 text-gray-900 shadow-sm ring-2 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-8" placeholder="Telefonnummer" />
                </div>
            </div>
        </div>
        <div class="col-span-1 hidden group-hover:block mx-auto">
           <div class="flex items-center justify-center gap-2">
               <div class="rounded-full border-4 border-green-500 hover:bg-green-200 p-1 cursor-pointer">
                   <CheckIcon class="h-5 w-5 text-green-500" @click="updateContact"/>
               </div>
               <div class="rounded-full border-4 border-red-500 hover:bg-red-200 p-1 cursor-pointer">
                   <XIcon class="h-5 w-5 cursor-pointer text-red-500" @click="deleteContact"/>
               </div>

           </div>
        </div>
    </div>

    <SuccessModal v-if="showSuccessModal" @close-modal="showSuccessModal = false" title="Dienstleister Kontakt erfolgreich gespeichert" description="Die Änderungen wurden erfolgreich gespeichert." button="Ok" />
</template>

<script>
import {defineComponent} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import {Inertia} from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";

export default defineComponent({
    name: "SingleContact",
    props: ['contact'],
    components: {
        SuccessModal,
        XIcon, CheckIcon
    },
    data(){
       return {
            contactData: useForm({
                first_name: this.contact.first_name,
                last_name: this.contact.last_name,
                email: this.contact.email,
                phone_number: this.contact.phone_number
            }),
           showSuccessModal: false
       }
    },
    methods: {
        deleteContact(){
            Inertia.delete(route('service-provider.contact.delete', this.contact.id), {
                preserveState: true, preserveScroll: true, onSuccess: () => this.openSuccessModal()
            });
        },
        updateContact(){
            this.contactData.patch(route('service-provider.contact.update', this.contact.id), {
                preserveScroll: true, preserveState: true, onSuccess: () => this.openSuccessModal()
            })
        },
        openSuccessModal() {
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal() {
            this.showSuccessModal = false;
        },
    }

})
</script>

<style scoped>

</style>
