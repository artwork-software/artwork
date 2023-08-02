<template>
    <div class="bg-secondary rounded flex text-sm group relative mb-2">
        <div class="hidden group-hover:block" v-if="$can('can manage workers') || hasAdminRole()">
            <div class="absolute w-full h-full rounded-lg flex justify-center align-middle items-center gap-2">
                <button type="button" @click="showEditVacationModal = true"
                        class="rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </button>
                <button type="button" @click="showDeleteConfirmModal = true"
                        class="rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="py-4 px-3 text-secondaryHover ">
            {{ dayjs(vacation.from).locale('de').format('dd, DD.MM.YYYY ') }} - {{ dayjs(vacation.until).locale('de').format('dd, DD.MM.YYYY ') }}
        </div>

        <AddEditVacationsModal :edit-vacation="vacation" :user="user" v-if="showEditVacationModal" @closed="showEditVacationModal = false" />
        <ConfirmDeleteModal v-if="showDeleteConfirmModal" title="Urlaub Löschen?" description="Bist du sicher, dass du den ausgewählten Urlaub löschen möchtest? " @closed="showDeleteConfirmModal = false" @delete="deleteVacation" />
    </div>
</template>
<script>
import {defineComponent} from 'vue'
import Button from "@/Jetstream/Button.vue";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import dayjs from "dayjs";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";
require('dayjs/locale/de')
export default defineComponent({
    name: "SingleUserVacation",
    mixins: [Permissions],
    components: {ConfirmDeleteModal, AddEditVacationsModal, Button},
    props: ['vacation', 'user','type'],
    data(){
        return {
            showEditVacationModal: false,
            showDeleteConfirmModal: false
        }
    },
    methods: {
        dayjs,
        deleteVacation(){
            if(this.type === 'freelancer'){
                Inertia.delete(route('freelancer.vacation.delete', this.vacation.id), {
                    onFinish: () => {
                        this.showDeleteConfirmModal = false
                    }
                })
            }else{
                Inertia.delete(route('user.vacation.delete', this.vacation.id), {
                    onFinish: () => {
                        this.showDeleteConfirmModal = false
                    }
                })
            }

        }
    },
})
</script>
<style scoped>

</style>
