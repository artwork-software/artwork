<template>
    <AppLayout>
        <div class="max-w-screen-lg ml-14 mr-40">
            <div class="">
                <h2 class="headline1">Schichteinstellungen</h2>
                <div class="xsLight mt-2">
                    Definiere globale Einstellungen für die Schichtplanung.
                </div>
            </div>


            <div class="mt-10">
                <h3 class="headline2 mb-2">Gewerke</h3>
                <p class="xsLight">
                    Definiere Gewerke, welchen du später Mitarbeiter*innen sowie Schichten zuteilen kannst.
                    Zusätzlich kannst du hier festlegen welche Nutzer*innen welche Art von Mitarbeiter*innen Schichten zuteilen dürfen.
                </p>
            </div>

            <AddButton text="Neues Gewerk" class="!ml-0 mt-5" @click="openAddCraftsModal = true"/>

            <ul role="list" class="divide-y divide-gray-100">
                <li v-for="craft in crafts" :key="craft" class="flex justify-between gap-x-6 py-5">
                    <div class="flex gap-x-4">
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-gray-900">{{ craft.name }} ({{ craft.abbreviation }})</p>
                            <div class="" v-if="craft.assignable_by_all">
                                <p class="mt-1 truncate xsLight">Von allen Schichtplaner*innen zuteilbar</p>
                            </div>
                            <div v-else>
                                <p class="mt-1 truncate xsLight">
                                    Darf ausschließlich zugeteilt werden von:
                                    <span class="" v-for="(user, index) in craft.users">
                                        {{ user.full_name }}<span>, </span>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="hidden sm:flex sm:flex-col sm:items-end">
                        <Menu as="div" class="my-auto mt-3 relative">
                            <div class="flex items-center -mt-1">
                                <MenuButton
                                    class="flex bg-tagBg p-0.5 rounded-full">
                                    <DotsVerticalIcon
                                        class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                        aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition enter-active-class="transition ease-out duration-100"
                                        enter-from-class="transform opacity-0 scale-95"
                                        enter-to-class="transform opacity-100 scale-100"
                                        leave-active-class="transition ease-in duration-75"
                                        leave-from-class="transform opacity-100 scale-100"
                                        leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="cursor-pointer origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem @click="updateCraft(craft)"
                                            v-slot="{ active }">
                                            <a
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Bearbeiten
                                            </a>
                                        </MenuItem>
                                        <MenuItem @click="openDeleteCraftModal(craft)"
                                            v-slot="{ active }">
                                            <a
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Löschen
                                            </a>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </li>
            </ul>


            <div class="mt-10">
                <h4 class="mb-2 headline2">Schichtrelevante Termintypen</h4>
                <p class="xsLight">
                    Lege fest, welche Termintypen standardmäßig als Schichtrelevant angezeigt werden.
                    Diese werden dann automatisch im Projekttab „Schichten“ angezeigt.
                    Auf Projektebene kannst du weitere Termine als schichtrelevant definieren.
                </p>

                <div class="mt-3">
                    <Listbox as="div">
                        <div class="relative mt-2 w-1/2">
                            <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                <span class="block truncate text-left pl-3">Termintypen wählen</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="type in notRelevantEventTypes" :key="type.id" :value="type" v-slot="{ active, selected }">
                                        <li @click="addRelevantEventType(type)" :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ type.name }}</span>
                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>

                <div class="mt-3 flex">
                    <div v-for="type in relevantEventTypes">
                        <TagComponent :method="removeRelevantEventType" :displayed-text="type.name" :property="type" />
                    </div>

                </div>

            </div>


        </div>

        <AddCraftsModal @closed="openAddCraftsModal = false" v-if="openAddCraftsModal" :craft-to-edit="craftToEdit" :users-with-permission="usersWithPermission" />

        <ConfirmDeleteModal title="Gewerk löschen" description="Bist du sicher, dass du das ausgewählte Gewerk löschen möchtest?" @closed="closedDeleteCraftModal" @delete="submitDelete" v-if="openConfirmDeleteModal" />
    </AppLayout>
</template>
<script>
import {defineComponent} from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {CheckIcon, DotsVerticalIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, DuplicateIcon, PencilAltIcon, TrashIcon} from "@heroicons/vue/outline";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AddCraftsModal from "@/Layouts/Components/AddCraftsModal.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default defineComponent({
    name: "ShiftSettings",
    components: {
        ConfirmDeleteModal,
        TagComponent,
        AddCraftsModal,
        ChevronDownIcon, CheckIcon, ListboxButton, ListboxOption, ListboxOptions, Listbox,
        PencilAltIcon, MenuItem, Menu, MenuButton, SvgCollection, MenuItems, DuplicateIcon, TrashIcon, DotsVerticalIcon,
        AddButton, AppLayout
    },
    props: ['crafts', 'eventTypes', 'usersWithPermission'],
    data(){
        return {
            selectedEventType: null,
            openAddCraftsModal: false,
            craftToEdit: null,
            openConfirmDeleteModal: false,
            craftToDelete: null,
        }
    },
    computed: {
        relevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(type.relevant_for_shift){
                    types.push(type)
                }
            })
            return types;
        },
        notRelevantEventTypes(){
            const types = [];
            this.eventTypes.forEach((type) => {
                if(!type.relevant_for_shift && type.id !== 1){
                    types.push(type)
                }
            })
            return types;
        }
    },
    methods: {
        addRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: true
            });
        },
        removeRelevantEventType(type){
            this.$inertia.patch(route('event-type.update.relevant', type), {
                relevant_for_shift: false
            });

            return true;
        },
        updateCraft(craft){
            this.craftToEdit = craft;
            this.openAddCraftsModal = true;
        },
        openDeleteCraftModal(craft){
            this.craftToDelete = craft;
            this.openConfirmDeleteModal = true;
        },
        closedDeleteCraftModal(){
            this.openConfirmDeleteModal = false;
            this.craftToDelete = null;
        },
        submitDelete(){
            this.$inertia.delete(route('craft.delete', this.craftToDelete.id), {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    this.closedDeleteCraftModal();
                }
            })
        }
    }
})
</script>


<style scoped>

</style>
