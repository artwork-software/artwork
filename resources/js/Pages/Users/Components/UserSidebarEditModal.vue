<script setup>
import {XIcon} from "@heroicons/vue/solid";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import Input from "@/Jetstream/Input.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {onMounted} from "vue";

const props = defineProps({
    show: Boolean,
    user: Object,
    type: String
})

onMounted(() => {
    console.log(props.user)
})

const emits = defineEmits(['closed']);

const userForm = useForm({
    work_name: props.user.work_name,
    work_description: props.user.work_description,
})


const updateWorkData = () => {
    let backend_route;

    if(props.type === "user") {
        backend_route = 'user.update.work_data'
    }
    else if(props.type === "serviceProvider") {
        backend_route = 'service_provider.update.work_data'
    }
    else {
        backend_route = 'freelancer.update.work_data'
    }

    console.log(userForm)

    userForm.post(route(backend_route, props.user.id), {
        _method: 'post',
        work_name: userForm.work_name,
        work_description: userForm.work_description
    })

    emits('closed')
}


</script>

<template>
    <jet-dialog-modal :show="props.show" @close="$emit('closed')">
        <template #content>
            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    Work Daten bearbeiten
                </div>
                <XIcon @click="$emit('closed')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <div class="text-secondary text-sm my-6">
                    Bearbeite die Work Daten des Nutzers hier.
                </div>
                <input type="text"
                       placeholder="Arbeitsname"
                       v-model="userForm.work_name"
                       class="h-10 mb-4 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                       required
                />
                <textarea placeholder="Arbeitsbeschreibung"
                          id="description"
                          v-model="userForm.work_description"
                          rows="4"
                          class="inputMain resize-none xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                <div class="flex items-center justify-center mt-5">
                    <AddButton mode="modal" text="Speichern" class="!ml-0" @click="updateWorkData"/>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<style scoped>

</style>
