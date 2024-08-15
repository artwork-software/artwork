<template>
    <BaseModal v-if="true" @closed="$emit('closed')" modal-size="max-w-6xl">
        <div class="modal-header">
            <div class="flex items-center gap-x-4">
                <div class="model-title">
                    {{ $t('Events') }}
                </div>
                <div class="model-title !text-gray-400 text-md">
                    ({{ project.name}})
                </div>
            </div>
        </div>

        <div>
            <BulkBody
                :rooms="rooms"
                :event-types="event_types"
                :project="project"
                @closed="$emit('closed')"
                is-in-modal
            />
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import {reactive, ref, watch} from "vue";
import {IconCheck, IconTrash, IconChevronDown, IconCirclePlus, IconCopy, IconPlus, IconCircleCheckFilled, IconX} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions, Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import BulkHeader from "@/Pages/Projects/Components/BulkComponents/BulkHeader.vue";
import BulkSingleEvent from "@/Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue";
import BulkBody from "@/Pages/Projects/Components/BulkComponents/BulkBody.vue";



const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    event_types: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    }
})


</script>

<style scoped>

</style>