<template>
    <BaseModal @closed="emit('close')" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{ $t('Room properties') }}
            </div>
            <Menu class="relative">
                <div>
                    <MenuButton @click="attributesOpened = true" class="menu-button">
                        <div class="flex items-center justify-between w-full">
                            <div class="flex-grow xsLight text-left subpixel-antialiased">
                                {{ $t('Select room properties') }}
                            </div>
                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                        </div>
                    </MenuButton>

                    <transition
                        enter-active-class="transition duration-50 ease-out"
                        enter-from-class="transform scale-100 opacity-100"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0">
                        <MenuItems
                            class="absolute right-0 w-full origin-top-right rounded-lg divide-y divide-gray-200 bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                            <div class="p-4">
                                <!-- Room Categories Section -->
                                <BaseFilterDisclosure :title="$t('Room categories')">
                                    <div v-if="availableCategories"
                                         v-for="category in availableCategories"
                                         :key="category.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="currentCategories"
                                               :value="category"
                                               class="input-checklist-dark"/>
                                        <p :class="[toIdsArray(currentCategories).includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ category.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">{{ $t('No room categories created yet') }}</div>
                                </BaseFilterDisclosure>

                                <hr class="border-gray-500 rounded-full mt-2 mb-2">

                                <!-- Adjoining rooms Section -->
                                <BaseFilterDisclosure :title="$t('Adjoining rooms')">

                                    <div v-if="availableAdjoiningRooms"
                                         v-for="room in availableAdjoiningRooms"
                                         :key="room.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="currentAdjoiningRooms"
                                               :value="room"
                                               class="input-checklist-dark"/>
                                        <p :class="[toIdsArray(currentAdjoiningRooms).includes(room.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ room.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">{{ $t('No adjoining rooms created yet') }}</div>
                                </BaseFilterDisclosure>

                                <hr class="border-gray-500 rounded-full mt-2 mb-2">

                                <!-- Room attributes Section -->
                                <BaseFilterDisclosure :title="$t('Room properties')">

                                    <div v-if="availableAttributes?.length > 0"
                                         v-for="attribute in availableAttributes"
                                         :key="attribute.id"
                                         class="flex w-full mb-2">
                                        <input type="checkbox"
                                               v-model="currentAttributes"
                                               :value="attribute"
                                               class="input-checklist-dark"/>
                                        <p :class="[toIdsArray(currentAttributes).includes(attribute.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                           class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                            {{ attribute.name }}
                                        </p>
                                    </div>
                                    <div v-else class="text-secondary">{{ $t('No room properties created yet') }}</div>
                                </BaseFilterDisclosure>
                            </div>
                        </MenuItems>
                    </transition>
                </div>
            </Menu>
            <div class="flex flex-wrap">
                <div v-for="(category, index) in currentCategories">
                    <BaseFilterTag v-if="currentCategories.includes(category)" :filter="category"
                                   @remove-filter="removeCategoryFromRoom(index)" class="w-fit"/>
                </div>
                <div v-for="(adjoining_room, index) in currentAdjoiningRooms">
                    <BaseFilterTag v-if="currentAdjoiningRooms.includes(adjoining_room)" :filter="adjoining_room"
                                   @remove-filter="removeAdjoiningRoom(index)" class="w-fit"/>
                </div>
                <div v-for="(attribute, index) in currentAttributes">
                    <BaseFilterTag v-if="currentAttributes.includes(attribute)" :filter="attribute"
                                   @remove-filter="removeAttributeFromRoom(index)" class="w-fit"/>
                </div>
            </div>
        </div>
        <div class="justify-center flex w-full mt-10">
            <FormButton
                :text="$t('Save')"
                @click="saveRoomData"
            />
        </div>
    </BaseModal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import {Menu, MenuButton, MenuItems} from "@headlessui/vue";
import {XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import {onMounted, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    show: Boolean,
    room: Object,
    categories: Object,
    availableCategories: Object,
    adjoiningRooms: Object,
    availableAdjoiningRooms: Object,
    attributes: Object,
    availableAttributes: Object
})

const emit = defineEmits(['close'])

const attributesOpened = ref(false)
const currentCategories = ref(props.categories ?? [])
const currentAdjoiningRooms = ref(props.adjoiningRooms ?? [])
const currentAttributes = ref(props.attributes ?? [])

const editRoomForm = useForm({
    room_categories: currentCategories.value,
    room_attributes: currentAttributes.value,
    adjoining_rooms: currentAdjoiningRooms.value,
})

const saveRoomData = () => {
    updateRoomRequest()
    emit('close')
}

const updateRoomRequest = () => {
    editRoomForm.room_categories = currentCategories.value.map(item => item.id)
    editRoomForm.room_attributes = currentAttributes.value.map(item => item.id)
    editRoomForm.adjoining_rooms = currentAdjoiningRooms.value.map(item => item.id)

    editRoomForm.patch(route('rooms.update', {room: props.room.id}));
}

const removeCategoryFromRoom = (index) => {
    currentCategories.value.splice(index, 1)
    updateRoomRequest()
}

const removeAdjoiningRoom = (index) => {
    currentAdjoiningRooms.value.splice(index, 1)
    updateRoomRequest()
}

const removeAttributeFromRoom = (index) => {
    currentAttributes.value.splice(index, 1)
    updateRoomRequest()
}

const toIdsArray = (array) => {
    let ids = [];
    if (Array.isArray(array)) {
        array.forEach(item => {
            ids.push(item.id)
        })
    }
    return ids;

}


</script>

<style scoped>

</style>
