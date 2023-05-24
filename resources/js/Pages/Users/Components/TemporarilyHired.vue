<template>
    <SwitchGroup as="div" class="flex items-center">
        <Switch v-model="userEdit.temporary" :class="[userEdit.temporary ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
            <span aria-hidden="true" :class="[userEdit.temporary ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
        </Switch>
        <SwitchLabel as="span" class="ml-3 text-sm">
            <span class="font-medium text-gray-900">Temporär angestellt</span>
        </SwitchLabel>
    </SwitchGroup>

    <div v-if="userEdit.temporary" class="mt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <div class="w-full flex">
                    <input v-model="userEdit.employStart"
                           id="startDate"
                           type="date"
                           required
                           class="border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow"/>
                </div>
            </div>
            <div>
                <div class="w-full flex">
                    <input v-model="userEdit.employEnd"
                           id="endDate"
                           type="date"
                           required
                           class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none flex-grow"/>
                </div>
            </div>
        </div>
    </div>

    <AddButton text="Speichern" class="!ml-0 mt-5" @click="updateTemporaryEmploy" />
</template>
<script>
import {defineComponent} from 'vue'
import { Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton.vue";
export default defineComponent({
    name: "TemporarilyHired",
    components: {
        AddButton,
        Switch, SwitchGroup, SwitchLabel
    },
    props: ['user'],
    data(){
        return {
            userEdit: useForm({
                temporary: this.user.temporary,
                employStart: this.user.employStart,
                employEnd: this.user.employEnd
            })
        }
    },
    methods: {
        updateTemporaryEmploy(){
            if(!this.userEdit.temporary){
                this.userEdit.employEnd = null;
                this.userEdit.employStart = null;
            }
            this.userEdit.patch(route('update.user.temporary', this.user.id), {
                preserveState: true, preserveScroll: true
            })
        }
    }
})
</script>
<style scoped>

</style>
