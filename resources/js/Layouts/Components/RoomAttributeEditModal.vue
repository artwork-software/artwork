<template>
    <jet-dialog-modal :show="show" @close="emit('close')">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="artwork"/>
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Room properties')}}
                </div>
                <XIcon @click="emit('close')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>
                <Menu class="relative">
                    <div>
                        <MenuButton @click="attributesOpened = true" class="w-full">
                            <div class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4 mt-4">
                                <div class="flex-grow xsLight text-left subpixel-antialiased">
                                    {{$t('Select room properties')}}
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
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems
                                class="absolute right-0 mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="rounded-2xl max-h-56 overflow-y-auto bg-primary border-none mt-2">

                                    <!-- Room Categories Section -->
                                    <BaseFilterDisclosure :title="$t('Room categories')">

                                        <div v-if="availableCategories?.length > 0"
                                             v-for="category in availableCategories"
                                             :key="category.id"
                                             class="flex w-full mb-2">
                                            <input type="checkbox"
                                                   v-model="currentCategories"
                                                   :value="category"
                                                   class="checkBoxOnDark"/>
                                            <p :class="[toIdsArray(currentCategories).includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{ $t('No room categories created yet')}}</div>
                                    </BaseFilterDisclosure>

                                    <hr class="border-gray-500 rounded-full mt-2 mb-2">

                                    <!-- Adjoining rooms Section -->
                                    <BaseFilterDisclosure :title="$t('Adjoining rooms')">

                                        <div v-if="availableAdjoiningRooms?.length > 0"
                                             v-for="room in availableAdjoiningRooms"
                                             :key="room.id"
                                             class="flex w-full mb-2">
                                            <input type="checkbox"
                                                   v-model="currentAdjoiningRooms"
                                                   :value="room"
                                                   class="checkBoxOnDark"/>
                                            <p :class="[toIdsArray(currentAdjoiningRooms).includes(room.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                {{ room.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{$t('No adjoining rooms created yet')}}</div>
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
                                                   class="checkBoxOnDark"/>
                                            <p :class="[toIdsArray(currentAttributes).includes(attribute.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                                {{ attribute.name }}
                                            </p>
                                        </div>
                                        <div v-else class="text-secondary">{{$t('No room properties created yet')}}</div>
                                    </BaseFilterDisclosure>
                                </div>
                            </MenuItems>
                        </transition>
                    </div>
                </Menu>
                <div class="mt-2 flex flex-wrap">
                    <div v-for="(category, index) in currentCategories">
                        <BaseFilterTag v-if="currentCategories.includes(category)"  :filter="category" @remove-filter="removeCategoryFromRoom(index)" class="w-fit" />
                    </div>
                    <div v-for="(adjoining_room, index) in currentAdjoiningRooms">
                        <BaseFilterTag v-if="currentAdjoiningRooms.includes(adjoining_room)"  :filter="adjoining_room" @remove-filter="removeAdjoiningRoom(index)" class="w-fit" />
                    </div>
                    <div v-for="(attribute, index) in currentAttributes">
                        <BaseFilterTag v-if="currentAttributes.includes(attribute)"  :filter="attribute" @remove-filter="removeAttributeFromRoom(index)" class="w-fit" />
                    </div>
                </div>
            </div>
            <div class="justify-center flex w-full my-6 mt-44">
                <FormButton
                    :text="$t('Save')"
                    @click="saveRoomData"
                    />
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script setup>
import JetDialogModal from "@/Jetstream/DialogModal";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import {Menu, MenuButton, MenuItems} from "@headlessui/vue";
import {XIcon, ChevronDownIcon} from "@heroicons/vue/outline";
import {onMounted, ref} from "vue";
import {useForm} from "@inertiajs/inertia-vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    show: Boolean,
    room: Object,
    categories: Array,
    availableCategories: Array,
    adjoiningRooms: Array,
    availableAdjoiningRooms: Array,
    attributes: Array,
    availableAttributes: Array
})

const emit = defineEmits(['close'])

const attributesOpened = ref(false)
const currentCategories = ref(props.categories)
const currentAdjoiningRooms = ref(props.adjoiningRooms)
const currentAttributes = ref(props.attributes)

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
    editRoomForm.room_categories = toIdsArray(currentCategories.value)
    editRoomForm.room_attributes = toIdsArray(currentAttributes.value)
    editRoomForm.adjoining_rooms = toIdsArray(currentAdjoiningRooms.value)

    editRoomForm.patch(route('rooms.update', {room: props.room.id}));
}

const removeCategoryFromRoom = (index) => {
    currentCategories.value.splice(index,1)
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
    array.forEach(item => {
        ids.push(item.id)
    })
    return ids;
}


</script>

<style scoped>

</style>
