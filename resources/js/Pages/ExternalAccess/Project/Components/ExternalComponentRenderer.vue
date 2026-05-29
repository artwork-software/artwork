<template>
    <component
        :is="resolvedComponent"
        :component="component"
        :project-id="projectId"
        :tab-id="tabId"
        :scope="scope"
    />
</template>

<script setup>
import { computed } from 'vue'
import ExternalTextField from './Types/ExternalTextField.vue'
import ExternalTextArea from './Types/ExternalTextArea.vue'
import ExternalCheckbox from './Types/ExternalCheckbox.vue'
import ExternalDropdown from './Types/ExternalDropdown.vue'
import ExternalLink from './Types/ExternalLink.vue'
import ExternalLinkList from './Types/ExternalLinkList.vue'
import ExternalTitle from './Types/ExternalTitle.vue'
import ExternalSeparator from './Types/ExternalSeparator.vue'
import ExternalDisclosure from './Types/ExternalDisclosure.vue'
import ExternalProjectTitle from './Types/ExternalProjectTitle.vue'
import ExternalProjectBasicData from './Types/ExternalProjectBasicData.vue'
import ExternalArtistNameDisplay from './Types/ExternalArtistNameDisplay.vue'
import ExternalUnknownType from './Types/ExternalUnknownType.vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

// Keys mirror the backed values of ProjectTabComponentEnum.
const typeToComponent = {
    TextField: ExternalTextField,
    TextArea: ExternalTextArea,
    Checkbox: ExternalCheckbox,
    DropDown: ExternalDropdown,
    Link: ExternalLink,
    LinkList: ExternalLinkList,
    Title: ExternalTitle,
    SeparatorComponent: ExternalSeparator,
    DisclosureComponent: ExternalDisclosure,
    ProjectTitleComponent: ExternalProjectTitle,
    ProjectBasicDataDisplayComponent: ExternalProjectBasicData,
    ArtistNameDisplayComponent: ExternalArtistNameDisplay,
}

const resolvedComponent = computed(() => typeToComponent[props.component.type] ?? ExternalUnknownType)
</script>
