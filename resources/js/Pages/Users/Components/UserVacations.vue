<template>
    <h3 class="sDark">Nicht verfügbar</h3>



    <div>
        <PlusCircleIcon class="h-5 w-5 text-white bg-[#3017AD] rounded-full cursor-pointer" @click="showAddEditVacationsModal = true" />
    </div>


    <AddEditVacationsModal v-if="showAddEditVacationsModal" @closed="showAddEditVacationsModal = false" :user="user" />
</template>

<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";

export default defineComponent({
    name: "UserVacations",
    components: {
        AddEditVacationsModal,
        PlusCircleIcon
    },
    props: ['user'],
    data(){
        return {
            showAddEditVacationsModal: false
        }
    }
})
</script>

<style scoped>

</style>
