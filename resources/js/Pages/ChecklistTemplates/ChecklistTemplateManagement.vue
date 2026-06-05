<template>
    <ChecklistTemplatesHeader :title="$t('Checklist templates')" :description="$t('Create and manage templates for checklists that can be reused across projects.')">
        <template #actions>
            <Link :href="route('checklist_templates.create')" class="ui-button-add">
                <PropertyIcon name="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('New template') }}
            </Link>
        </template>

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

export default {
    mixins: [Permissions, IconLib],
    name: "Checklist Management",
    props: ['checklist_templates'],
    components: {
        PropertyIcon,
        SingleChecklistTemplateListView,
        ChecklistTemplatesHeader,
        Link,
    },
}
</script>
