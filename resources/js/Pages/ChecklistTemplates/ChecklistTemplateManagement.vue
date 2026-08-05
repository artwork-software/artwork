<template>
    <ChecklistTemplatesHeader :title="$t('Checklist templates')" :description="$t('Create and manage templates for checklists that can be reused across projects.')">
        <template #actions>
            <Link :href="route('checklist_templates.create')" class="ui-button-add">
                <PropertyIcon name="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('New template') }}
            </Link>
        </template>

        <SettingsGuideBanner
            variant="banner"
            storage-key="settings-guide.checklists.templates"
            title="How checklist templates work"
            :paragraphs="guideParagraphs"
            footnote="Assigned people who are not part of the project automatically become project members when the template is applied."
            class="mb-6"
        />

        <div class="my-10">
            <div v-if="checklist_templates.length === 0" class="text-center text-secondary py-16">
                {{ $t('No checklist templates have been created yet.') }}
            </div>
            <SingleChecklistTemplateListView
                v-for="template in checklist_templates"
                :key="template.id"
                :checklist_template="template"
            />
        </div>
    </ChecklistTemplatesHeader>
</template>

<script>
import { Link } from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ChecklistTemplatesHeader from "@/Pages/ChecklistTemplates/Components/ChecklistTemplatesHeader.vue";
import SingleChecklistTemplateListView from "@/Pages/ChecklistTemplates/Components/SingleChecklistTemplateListView.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "Checklist Management",
    props: ['checklist_templates'],
    components: {
        SettingsGuideBanner,
        PropertyIcon,
        SingleChecklistTemplateListView,
        ChecklistTemplatesHeader,
        Link,
    },
    data() {
        return {
            guideParagraphs: [
                'Templates are picked when creating a new checklist in a project or in your own tasks.',
                'A template brings along its tasks, their order, relative deadlines (X days after creation) and user assignments.',
            ],
        };
    },
}
</script>
