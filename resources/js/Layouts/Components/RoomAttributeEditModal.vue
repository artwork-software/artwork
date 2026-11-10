<template>
    <ArtworkBaseModal
        v-if="show"
        title="Room properties"
        description="Select room properties"
        modal-size="sm:max-w-xl"
        @close="close"
    >
        <div class="space-y-4">
            <ArtworkBaseListbox
                v-model="currentCategories"
                :items="categoryOptions"
                multiple
                by="id"
                option-label="name"
                label="Room categories"
                :empty-text="$t('No room categories created yet')"
            />
            <ArtworkBaseListbox
                v-model="currentAdjoiningRooms"
                :items="adjoiningRoomOptions"
                multiple
                by="id"
                option-label="name"
                label="Adjoining rooms"
                :empty-text="$t('No adjoining rooms created yet')"
            />
            <ArtworkBaseListbox
                v-model="currentAttributes"
                :items="attributeOptions"
                multiple
                by="id"
                option-label="name"
                label="Room properties"
                :empty-text="$t('No room properties created yet')"
            />

            <div v-if="hasSelection" class="flex flex-wrap">
                <BaseFilterTag
                    v-for="(category, index) in currentCategories"
                    :key="'category-' + category.id"
                    :filter="category"
                    class="w-fit"
                    @remove-filter="removeCategoryFromRoom(index)"
                />
                <BaseFilterTag
                    v-for="(adjoiningRoom, index) in currentAdjoiningRooms"
                    :key="'room-' + adjoiningRoom.id"
                    :filter="adjoiningRoom"
                    class="w-fit"
                    @remove-filter="removeAdjoiningRoom(index)"
                />
                <BaseFilterTag
                    v-for="(attribute, index) in currentAttributes"
                    :key="'attribute-' + attribute.id"
                    :filter="attribute"
                    class="w-fit"
                    @remove-filter="removeAttributeFromRoom(index)"
                />
            </div>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="close"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                :disabled="processing"
                @click="saveRoomData"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";

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

const currentCategories = ref(props.categories ?? [])
const currentAdjoiningRooms = ref(props.adjoiningRooms ?? [])
const currentAttributes = ref(props.attributes ?? [])
const processing = ref(false)

// Die Listen kommen je nach Controller als Array oder als Objekt
const toList = (value) => Object.values(value ?? {})
const categoryOptions = computed(() => toList(props.availableCategories))
const adjoiningRoomOptions = computed(() => toList(props.availableAdjoiningRooms))
const attributeOptions = computed(() => toList(props.availableAttributes))

const hasSelection = computed(() =>
    currentCategories.value.length > 0
    || currentAdjoiningRooms.value.length > 0
    || currentAttributes.value.length > 0
)

const editRoomForm = useForm({
    room_categories: currentCategories.value,
    room_attributes: currentAttributes.value,
    adjoining_rooms: currentAdjoiningRooms.value,
})

const close = () => {
    if (processing.value) {
        return
    }
    emit('close')
}

const saveRoomData = () => {
    if (processing.value) {
        return
    }
    processing.value = true
    updateRoomRequest({
        onSuccess: () => emit('close'),
        onFinish: () => {
            processing.value = false
        },
    })
}

const updateRoomRequest = (options = {}) => {
    editRoomForm.room_categories = currentCategories.value.map(item => item.id)
    editRoomForm.room_attributes = currentAttributes.value.map(item => item.id)
    editRoomForm.adjoining_rooms = currentAdjoiningRooms.value.map(item => item.id)

    editRoomForm.patch(route('rooms.update', {room: props.room.id}), {
        preserveScroll: true,
        ...options,
    });
}

// Entfernen über das Tag speichert sofort (wie bisher)
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
</script>
