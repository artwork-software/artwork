<template>
    <div class="card white p-5 mt-10">
        <div class="flex items-center justify-between">
            <BasePageTitle class=""
                           :title="$t('Global qualifications')"
                           :description="$t('Here you can manage the global qualifications that can be assigned to shifts.')"
            />
            <BaseUIButton @click="showCreateOrUpdateGlobalQualificationModal = true" label="New global qualification" use-translation is-add-button />
        </div>
        <div class="mt-5">
            <div class="mb-5 xsLight" v-if="globalQualifications.length === 0">
                {{$t('No qualifications have been created yet.')}}
            </div>
            <ul v-else role="list" class="w-full">
                <li v-for="(globalQualification) in globalQualifications"
                    :key="globalQualification.id"

                    class="cursor-pointer py-4 pr-4 flex justify-between items-center border-b border-zinc-200"
                >
                    <SingleGlobalQualification :global-qualification="globalQualification" />

                </li>
            </ul>
        </div>
    </div>

    <CreateOrUpdateGlobalQualificationModal
        v-if="showCreateOrUpdateGlobalQualificationModal"
        @close="showCreateOrUpdateGlobalQualificationModal = false"

    />

</template>

<script setup>

import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import SingleGlobalQualification
    from "@/Pages/Settings/ShiftSettingsComponents/Components/SingleGlobalQualification.vue";
import {defineAsyncComponent, ref} from "vue";

const props = defineProps({
    globalQualifications: {
        type: Object,
        required: true,
    }
})

const showCreateOrUpdateGlobalQualificationModal = ref(false);

const CreateOrUpdateGlobalQualificationModal = defineAsyncComponent({
    loader: () => import('@/Pages/Settings/ShiftSettingsComponents/Components/CreateOrUpdateGlobalQualificationModal.vue'),
    delay: 200,
})
</script>
<style scoped>

</style>
