<script setup>
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {useForm, usePage} from "@inertiajs/inertia-vue3";
import {watch} from "vue";

const userForm = useForm({
    can_master: usePage().props.value.user.can_master,
})

watch(() => userForm.can_master, (value) => {

    userForm.post(route('user.update.can_master', usePage().props.value.user.id), {
        _method: 'post',
        can_master: value,
    })

});

</script>

<template>
    <div class="w-full mt-24">
        <div>
            <SwitchGroup as="div" class="flex items-center">
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
    </div>
</template>

<style scoped>

</style>
