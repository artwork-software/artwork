<template>
    <h3 class="sDark mt-1">Nicht verfügbar</h3>

    <div class="my-5">
        <div v-for="vacation in vacations">
            <SingleUserVacation :type="type" :vacation="vacation" :user="user" />
        </div>
    </div>

    <div v-if="$can('can manage workers') || hasAdminRole()">
        <PlusCircleIcon class="h-5 w-5 text-white bg-[#3017AD] rounded-full cursor-pointer" @click="showAddEditVacationsModal = true" />
    </div>


    <AddEditVacationsModal :type="type" v-if="showAddEditVacationsModal" @closed="showAddEditVacationsModal = false" :user="user" />
</template>

<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import SingleUserVacation from "@/Pages/Users/Components/SingleUserVacation.vue";
import Permissions from "@/mixins/Permissions.vue";

export default defineComponent({
    name: "UserVacations",
    mixins: [Permissions],
    components: {
        SingleUserVacation,
        AddEditVacationsModal,
        PlusCircleIcon
    },
    props: ['user', 'vacations','type'],
    data(){
        return {
            showAddEditVacationsModal: false
        }
    }
})
</script>

<style scoped>

</style>
