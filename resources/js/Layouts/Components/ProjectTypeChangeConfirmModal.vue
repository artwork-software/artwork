<template>
    <ArtworkBaseModal
        @close="emit('close')"
        :title="toGroup ? 'Convert project into project group' : 'Convert project group into project'"
        z-index="110"
    >
        <div class="mt-5">
            <BaseAlertComponent
                type="warning"
                use-translation
                title="Please read before converting"
                :message="toGroup
                    ? 'This changes the type of the project. Please check the consequences below.'
                    : 'This changes the type of the project group. Please check the consequences below.'"
            />

            <ul class="list-disc pl-5 space-y-2 text-sm text-text">
                <template v-if="toGroup">
                    <li>{{ $t('Other projects can then be assigned to it as subprojects.') }}</li>
                    <li v-if="currentGroupNames">
                        {{ $t('The assignment to the project group “{groups}” will be removed, because a project group cannot belong to another project group.', { groups: currentGroupNames }) }}
                    </li>
                </template>
                <template v-else>
                    <li class="font-semibold">
                        <template v-if="subProjectNames">
                            {{ $t('The connection to the subprojects “{projects}” will be removed.', { projects: subProjectNames }) }}
                        </template>
                        <template v-else>
                            {{ $t('The connection to all subprojects of this group will be removed.') }}
                        </template>
                    </li>
                    <li>{{ $t('The subprojects are not deleted and remain as independent projects.') }}</li>
                    <li>{{ $t('If you convert the project back into a project group later, the subprojects must be assigned again manually.') }}</li>
                    <li>{{ $t('The combined budget view of the subprojects in the group budget is no longer available.') }}</li>
                </template>
                <li>{{ $t('All other data such as events, budget, team and documents is retained.') }}</li>
            </ul>
        </div>

        <div class="flex justify-between items-center mt-6">
            <BaseUIButton
                :label="toGroup ? 'Convert to project group' : 'Convert to project'"
                use-translation
                variant="danger"
                icon="IconTransform"
                :processing="processing"
                :disabled="processing"
                @click="emit('confirm')"
            />
            <BaseUIButton label="No, not really" use-translation variant="secondary" icon="IconCancel" @click="emit('close')"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue';
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    /** Unterprojekte der Gruppe, falls der Aufrufer sie kennt (sonst allgemeiner Hinweis) */
    projectsOfGroup: {
        type: Array,
        default: () => [],
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'close']);

const toGroup = computed(() => !props.project?.is_group);

const currentGroupNames = computed(() => (props.project?.groups ?? [])
    .map((group) => group?.name)
    .filter(Boolean)
    .join(', '));

const subProjectNames = computed(() => (props.projectsOfGroup ?? [])
    .map((subProject) => subProject?.name)
    .filter(Boolean)
    .join(', '));
</script>
