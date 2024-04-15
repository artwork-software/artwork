<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-40" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-40 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Craft')}}
                                </div>
                                <p class="subpixel-antialiased">{{ $t('Define the specifications of your trade.')}}</p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-5">

                                    <input type="text"
                                           :placeholder="$t('Name of the craft') + '*'"
                                           v-model="craft.name"
                                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                           required
                                    />
                                    <input type="text"
                                           :placeholder="$t('Abbreviation') + '*'"
                                           v-model="craft.abbreviation"
                                           maxlength="3"
                                           required
                                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>

                                <div class="mt-3">
                                    <SwitchGroup as="div" class="flex items-center gap-2">
                                        <SwitchLabel as="span" class="mr-3 text-sm">
                                            <span class="font-medium text-gray-900" :class="enabled ? '!text-gray-400' : ''">{{ $t('Allocable to a limited extent')}}</span>
                                        </SwitchLabel>
                                        <Switch v-model="enabled" :class="[enabled ? 'bg-indigo-600' : 'bg-gray-200', 'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2']">
                                            <span aria-hidden="true" :class="[enabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                        </Switch>
                                        <SwitchLabel as="span" class="ml-3 text-sm">
                                            <span class="font-medium text-gray-900" :class="!enabled ? '!text-gray-400' : ''">{{ $t('Can be scheduled by all shift planners')}}</span>
                                        </SwitchLabel>
                                    </SwitchGroup>
                                </div>
                                <div v-if="!enabled" class="">
                                    <Listbox as="div">
                                        <div class="relative mt-2">
                                            <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                                <span class="block truncate text-left pl-3">
                                                    {{ $t('Select users')}}
                                                </span>
                                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                    <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                </span>
                                            </ListboxButton>

                                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                    <ListboxOption as="template" v-for="user in usersWithPermission" :key="user.id" :value="user" v-slot="{ active, selected }">
                                                        <li @click="addOrRemoveFormUserList(user)" :class="'relative cursor-default select-none py-2 pl-3 pr-9'">
                                                            <span>{{ user.full_name }}</span>
                                                        </li>
                                                    </ListboxOption>
                                                </ListboxOptions>
                                            </transition>
                                        </div>
                                    </Listbox>
                                    <div class="mt-3">
                                        <div v-for="user in users" class="my-2">
                                            <div class="flex col-span-2">
                                                <div class="flex items-center">
                                                    <img class="flex h-11 w-11 rounded-full"
                                                         :src="user.profile_photo_url"
                                                         alt=""/>
                                                    <span class="flex ml-4">
                                                        {{ user.first_name }} {{ user.last_name }}
                                                    </span>
                                                </div>
                                                <button type="button" @click="addOrRemoveFormUserList(user)">
                                                    <span class="sr-only">{{ $t('Remove user from team')}}</span>
                                                    <IconCircleX stroke-width="1.5" class="ml-3 text-buttonBlue h-5 w-5 hover:text-error "/>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-center mt-5">
                                <FormButton
                                    text="Speichern"
                                    @click="saveCraft"
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
import {
    Dialog,
    DialogPanel,
    Listbox, ListboxButton,
    ListboxOption,
    ListboxOptions, Switch, SwitchGroup, SwitchLabel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import {CheckIcon, XCircleIcon, XIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import Input from "@/Jetstream/Input.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";

export default defineComponent({
    name: "AddCraftsModal",
    mixins: [IconLib],
    components: {
        FormButton,
        XCircleIcon,
        TagComponent,
        Input,
        ChevronDownIcon, CheckIcon, ListboxButton, ListboxOption, ListboxOptions, Listbox,
        Dialog, TransitionChild, XIcon, TransitionRoot, DialogPanel, SwitchGroup, Switch, SwitchLabel
    },
    props: ['craftToEdit', 'usersWithPermission'],
    data(){
        return {
            open: true,
            craft: useForm({
                name: this.craftToEdit ? this.craftToEdit.name : '',
                abbreviation: this.craftToEdit ? this.craftToEdit.abbreviation : '',
                users: [],
                assignable_by_all: true
            }),
            enabled: this.craftToEdit ? this.craftToEdit.assignable_by_all : true,
            users: this.craftToEdit ? this.craftToEdit.users : []
        }
    },
    unmounted() {
        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
    },
    emits: ['closed'],
    methods: {
        closeModal(bool){
            this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
            this.$emit('closed', bool)
        },
        addOrRemoveFormUserList(user){
            const userIds = this.users.map(user => user.id);
            if(userIds.includes(user.id)){
                this.users = this.users.filter(u => u.id !== user.id)
            } else {
                this.users.push(user)
            }
        },
        saveCraft(){
            if(!this.enabled){
                this.craft.assignable_by_all = false
                this.users.forEach((user) => {
                    this.craft.users.push(user.id);
                })
            } else {
                this.craft.assignable_by_all = true;
                this.craft.users = [];
            }
            if(this.craftToEdit){
                this.craft.patch(route('craft.update', this.craftToEdit.id), {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                        this.closeModal(true)
                    }
                })
            } else {
                this.craft.post(route('craft.store'), {
                    preserveState: true,
                    preserveScroll: true,
                    onFinish: () => {
                        this.craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                        this.closeModal(true)
                    }
                })
            }
        },
        updateCraft(){

        }
    }
})
</script>
<style scoped>

</style>
