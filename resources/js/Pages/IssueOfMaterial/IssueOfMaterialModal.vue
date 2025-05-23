<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        modal-size="max-w-7xl"
        :title="issueOfMaterial?.id ?  $t('Edit issue of material') : $t('New issue of material')"
        :description="issueOfMaterial?.id ? $t('Edit Text') : $t('Create Text')"
    >

        <div class="w-fit mb-5">
            <SwitchGroup as="div" class="flex items-center justify-between gap-x-4">
                    <span class="flex grow flex-col">
                      <SwitchLabel as="span" class="text-sm/6 font-medium text-gray-900" passive>Interne Materialausgabe</SwitchLabel>
                      <SwitchDescription as="span" class="text-xs text-gray-500">
                          Erstelle eine interne Materialausgabe für Mitarbeiter
                      </SwitchDescription>
                    </span>
                <Switch v-model="internOrExternal" :class="[internOrExternal ? 'bg-blue-600' : 'bg-gray-200', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 focus:outline-hidden']">
                    <span aria-hidden="true" :class="[internOrExternal ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <span class="flex grow flex-col">
                      <SwitchLabel as="span" class="text-sm/6 font-medium text-gray-900" passive>Leihschein</SwitchLabel>
                      <SwitchDescription as="span" class="text-xs text-gray-500">
                            Erstelle einen Leihschein für externe Materialausgaben
                      </SwitchDescription>
                    </span>
            </SwitchGroup>
        </div>


        <div v-if="internOrExternal" class="flex flex-col gap-y-4">
            <ExternMaterialIssueModul :extern-material-issue="externMaterialIssue" @close="$emit('close')" />
        </div>
        <div v-else>
            <CreateInternMaterialIssueModul :issue-of-material="issueOfMaterial" @close="$emit('close')" />
        </div>
    </ArtworkBaseModal>
</template>
<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import {defineAsyncComponent, ref} from "vue";
import {Switch, SwitchDescription, SwitchGroup, SwitchLabel} from "@headlessui/vue";


const props = defineProps({
    issueOfMaterial: {
        type: Object,
        required: false,
        default: () => ({
            id: null,
            name: '',
            project_id: null,
            start_date: '',
            start_time: '00:00',
            end_date: '',
            end_time: '23:59',
            room_id: null,
            notes: '',
            responsible_user_ids: [],
            special_items_done: false,
            files: [],
            articles: [],
            special_items: []
        })
    },
    externMaterialIssue: {
        type: Object,
        required: false,
        default: () => ({
            material_value: 0.00,
            issue_date: '',
            return_date: '',
            return_remarks: '',
            external_name: '',
            external_address: '',
            external_email: '',
            external_phone: '',
            files: [],
            articles: [],
            special_items: [],
        })
    },
    isExternOrIntern: {
        type: Boolean,
        required: false,
    }
})


const internOrExternal = ref(props.isExternOrIntern)

const CreateInternMaterialIssueModul = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/Components/CreateInternMaterialIssueModul.vue'),
    delay: 0,
    timeout: 3000,
})

const ExternMaterialIssueModul = defineAsyncComponent({
    loader: () => import('@/Pages/IssueOfMaterial/Components/ExternMaterialIssueModul.vue'),
    delay: 0,
    timeout: 3000,
})
</script>
