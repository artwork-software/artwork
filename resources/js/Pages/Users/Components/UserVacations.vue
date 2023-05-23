<template>
    <h3 class="sDark">Nicht verfügbar</h3>

    <div class="my-5">
        <div v-for="vacation in vacations">
            <SingleUserVacation :vacation="vacation" :user="user" />
        </div>
    </div>

    <div>
        <PlusCircleIcon class="h-5 w-5 text-white bg-[#3017AD] rounded-full cursor-pointer" @click="showAddEditVacationsModal = true" />
    </div>


    <AddEditVacationsModal v-if="showAddEditVacationsModal" @closed="showAddEditVacationsModal = false" :user="user" />
</template>

<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import SingleUserVacation from "@/Pages/Users/Components/SingleUserVacation.vue";

export default defineComponent({
    name: "UserVacations",
    components: {
        SingleUserVacation,
        AddEditVacationsModal,
        PlusCircleIcon
    },
    props: ['user', 'vacations'],
    data(){
        return {
            showAddEditVacationsModal: false
        }
    }
})
</script>

<style scoped>

</style>
