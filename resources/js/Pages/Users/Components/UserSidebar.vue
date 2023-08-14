<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {useForm, usePage} from "@inertiajs/inertia-vue3";
import {ref, watch} from "vue";
import {PencilAltIcon} from "@heroicons/vue/outline";
import UserSidebarEditModal from "@/Pages/Users/Components/UserSidebarEditModal.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";

const props = defineProps({
    user: Object,
    type: String
})

const showEditModal = ref(false);

const openEditModal = () => {
    console.log(showEditModal.value)
    showEditModal.value = true;
}

const userForm = useForm({
    can_master: props.user.can_master,
    can_work_shifts: props.user.can_work_shifts,
})

watch(() => userForm.can_master, (value) => {

    let backend_route;

    if(props.type === "user") {
        backend_route = 'user.update.can_master'
    }
    else if(props.type === "serviceProvider") {
        backend_route = 'service_provider.update.can_master'
    }
    else {
        backend_route = 'freelancer.update.can_master'
    }

    userForm.post(route(backend_route, props.user.id), {
        _method: 'post',
        can_master: value,
    })

});

watch(() => userForm.can_work_shifts, (value) => {

    let backend_route = 'user.update.can_work_shifts'


    userForm.post(route(backend_route, props.user.id), {
        _method: 'post',
        can_work_shifts: value,
    })

});

</script>

<template>
    <div class="w-full mt-24">
        <div>
            <div class="flex items-center">
                <div class="text-lg text-secondary">Profil</div>
                <div class="ml-auto rounded-full bg-darkGray p-1" @click="openEditModal">
                    <PencilAltIcon class="h-4 w-4 text-white cursor-pointer"/>
                </div>
            </div>
            <div class="text-secondary mt-4">{{ props.user.work_name || "Noch keine Bezeichnung angegeben" }}</div>
            <div class="text-secondary mt-2">{{ props.user.work_description || "Noch keine Beschreibung angegeben." }}</div>
            <div class="mt-8 text-secondary">GEWERKE</div>
            <span class="flex">
                <TagComponent v-for="craft in props.user.crafts" :tag="craft" :key="craft.id" type="gray" :displayed-text="craft.name" hide-x="true"/>
            </span>
            <SwitchGroup v-if="props.type === 'user'" as="div" class="flex items-center mt-2">
                <Switch v-model="userForm.can_work_shifts"
                        :class="[userForm.can_work_shifts ? 'bg-indigo-600' : 'bg-secondary', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                            <span aria-hidden="true"
                                                  :class="[userForm.can_work_shifts ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="text-secondary">Darf zu Schichten eingeteilt werden</span>
                </SwitchLabel>
            </SwitchGroup>
            <SwitchGroup as="div" class="flex items-center mt-2">
                <Switch v-model="userForm.can_master"
                        :class="[userForm.can_master ? 'bg-indigo-600' : 'bg-secondary', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                            <span aria-hidden="true"
                                                  :class="[userForm.can_master ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                </Switch>
                <SwitchLabel as="span" class="ml-3 text-sm">
                    <span class="text-secondary">Als Meister einsetzbar</span>
                </SwitchLabel>
            </SwitchGroup>
        </div>
        <UserSidebarEditModal
            @closed="showEditModal = false"
            :show="showEditModal"
            :user="props.user"
            :type="props.type"
        />
    </div>
</template>

<style scoped>

</style>
