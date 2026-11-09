<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">
            <section>
                <h1 class="text-lg font-semibold text-text">{{ $t(exportTabEnum) }}</h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ isWord
                        ? $t('Exports one block per project with the selected contents as plain paragraphs, without tables or tabs.')
                        : $t('Exports one row per project with the selected contents as columns.') }}
                    {{ $t('Only projects you may open and components you may see are exported.') }}
                </p>
            </section>

            <div v-if="loading" class="space-y-3">
                <div class="h-24 animate-pulse rounded-2xl bg-surface-sunken" />
                <div class="h-48 animate-pulse rounded-2xl bg-surface-sunken" />
            </div>

            <template v-else-if="options">
                <!-- Vorlage -->
                <section class="space-y-3 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-text">{{ $t('Template') }}</h2>
                    <div class="flex flex-wrap items-end gap-2">
                        <div class="min-w-64 flex-1">
                            <BaseCombobox
                                v-model="selectedTemplateId"
                                :items="templates"
                                option-label="name"
                                option-key="id"
                                :placeholder="$t('No template')"
                                @update:model-value="applyTemplate"
                            />
                        </div>
                        <BaseUIButton
                            v-if="selectedTemplate?.can_manage"
                            :label="$t('Update template')"
                            icon="IconDeviceFloppy"
                            :disabled="form.columns.length === 0 || savingTemplate"
                            @click="saveTemplate(selectedTemplate)"
                        />
                        <BaseUIButton
                            v-if="selectedTemplate?.can_manage"
                            :label="$t('Delete')"
                            is-delete-button
                            :disabled="savingTemplate"
                            @click="deleteTemplate"
                        />
                        <BaseUIButton
                            :label="$t('Save as new template')"
                            icon="IconPlus"
                            :disabled="form.columns.length === 0"
                            @click="showNewTemplateName = !showNewTemplateName"
                        />
                    </div>
                    <div v-if="showNewTemplateName" class="flex flex-wrap items-end gap-2">
                        <div class="min-w-64 flex-1">
                            <BaseInput
                                id="projectContentTemplateName"
                                v-model="newTemplateName"
                                label="Template name"
                            />
                        </div>
                        <BaseUIButton
                            :label="$t('Save')"
                            icon="IconDeviceFloppy"
                            is-add-button
                            :disabled="newTemplateName.trim() === '' || savingTemplate"
                            @click="saveTemplate(null)"
                        />
                    </div>
                    <p class="text-xs text-text-subtle">
                        {{ $t('Templates are visible to everyone with the export permission and apply to Excel and Word. The time period is not saved.') }}
                    </p>
                    <p v-if="templateError" class="text-xs text-danger" role="alert">{{ templateError }}</p>
                    <p v-else-if="templateMessage" class="text-xs text-success" role="status">{{ templateMessage }}</p>
                </section>

                <!-- Projektauswahl -->
                <section class="space-y-4 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-text">{{ $t('Project selection') }}</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <BaseInput id="projectContentDateStart" v-model="form.filters.date_start" type="date" label="Start date" />
                        <BaseInput id="projectContentDateEnd" v-model="form.filters.date_end" type="date" label="End date" />
                        <BaseCombobox
                            v-model="form.filters.project_group_id"
                            :items="options.project_groups"
                            option-label="name"
                            option-key="id"
                            :label="$t('Project group')"
                            :placeholder="$t('All project groups')"
                        />
                    </div>
                    <p v-if="dateRangeInvalid" class="text-xs text-danger">
                        {{ $t('Please enter both start and end date, the start must not be after the end.') }}
                    </p>
                    <p class="text-xs text-text-subtle">
                        {{ $t('With a time period, only projects with at least one event in it are exported, and only these events are listed.') }}
                    </p>

                    <div class="space-y-2">
                        <div class="text-xs font-medium text-text">{{ $t('Characteristics') }}</div>
                        <p class="text-xs text-text-subtle">
                            {{ $t('All projects to which at least one selected characteristic applies are exported.') }}
                        </p>
                        <div
                            v-for="group in characteristicGroups"
                            :key="group.key"
                            class="rounded-lg border border-border-subtle px-4"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between py-2.5 text-sm text-text"
                                @click="toggleGroup(group.key)"
                            >
                                <span>{{ $t(group.label) }}</span>
                                <span class="flex items-center gap-3">
                                    <span v-if="form.filters[group.key].length" class="rounded-lg bg-success-surface px-2 py-0.5 text-xs text-success">
                                        {{ form.filters[group.key].length }} {{ $t('selected') }}
                                    </span>
                                    <IconChevronDown class="size-4 text-text-subtle" :class="openGroups[group.key] ? 'rotate-180' : ''" />
                                </span>
                            </button>
                            <div v-if="openGroups[group.key]" class="grid grid-cols-1 gap-2 pb-3 sm:grid-cols-3">
                                <BaseCheckbox
                                    v-for="item in options[group.options]"
                                    :id="`projectContent-${group.key}-${item.id}`"
                                    :key="item.id"
                                    :model-value="form.filters[group.key].includes(item.id)"
                                    :label="item.name"
                                    @update:model-value="(checked) => toggleId(form.filters[group.key], item.id, checked)"
                                />
                                <p v-if="options[group.options].length === 0" class="text-xs text-text-subtle">
                                    {{ $t('No entries') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <UserSearch :label="$t('Project members')" @user-selected="addMember" />
                        <p class="text-xs text-text-subtle">
                            {{ $t('Only projects with at least one of these persons in the project team are exported.') }}
                        </p>
                        <div v-if="members.length" class="flex flex-wrap gap-1.5">
                            <TagComponent
                                v-for="member in members"
                                :key="member.id"
                                :displayed-text="member.name"
                                :property="member.id"
                                :method="removeMember"
                            />
                        </div>
                    </div>
                </section>

                <!-- Spalten -->
                <section class="space-y-4 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-text">{{ $t('Columns') }}</h2>
                    <AlertComponent
                        v-if="hiddenColumns > 0"
                        type="info"
                        show-icon
                        icon-size="h-4 w-4"
                        :text="$t('{0} columns of this template are not visible to you and will be omitted.', [hiddenColumns])"
                    />
                    <ProjectContentColumnPicker
                        v-model="form.columns"
                        :columns="options.columns"
                        :show-label-option="isWord"
                    />

                    <div v-if="hasEventsColumn" class="space-y-2 rounded-lg border border-border-subtle p-4">
                        <div class="text-xs font-medium text-text">{{ $t('Event types in the events column') }}</div>
                        <p class="text-xs text-text-subtle">
                            {{ $t('Each event is listed as event type, date, time and room. Without a selection, all event types are listed.') }}
                        </p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <BaseCheckbox
                                v-for="eventType in options.event_types"
                                :id="`projectContentEventColumn-${eventType.id}`"
                                :key="eventType.id"
                                :model-value="form.event_column.event_type_ids.includes(eventType.id)"
                                :label="eventType.name"
                                @update:model-value="(checked) => toggleId(form.event_column.event_type_ids, eventType.id, checked)"
                            />
                        </div>
                    </div>
                </section>

                <section class="flex flex-wrap items-center justify-end gap-4">
                    <p v-if="exportError" class="text-sm text-danger" role="alert">{{ exportError }}</p>
                    <p v-else-if="projectCount !== null" class="text-sm text-text-muted" role="status">
                        {{ $t('{0} projects will be exported.', [projectCount]) }}
                    </p>
                    <BaseUIButton
                        :label="$t('Export')"
                        icon="IconFileExport"
                        is-add-button
                        :disabled="exportDisabled"
                        :processing="exporting"
                        @click="initializeDownload"
                    />
                </section>
            </template>

            <p v-else class="text-sm text-danger" role="alert">{{ $t('Export could not be created. Please try again.') }}</p>
        </div>
    </div>
</template>

<script setup>
import {computed, onMounted, reactive, ref, watch} from "vue";
import axios from "axios";
import debounce from "lodash.debounce";
import {IconChevronDown} from "@tabler/icons-vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import ProjectContentColumnPicker from "@/Layouts/Components/Export/Components/ProjectContentColumnPicker.vue";
import {useTranslation} from "@/Composeables/Translation.js";

/**
 * Projektinhalte als Excel oder Word (je ein Reiter im Export-Modal der Projektübersicht). Beide Reiter teilen
 * sich Vorlagen; die Projektauswahl prüft der Server, die Vorschau zeigt die Anzahl der Projekte.
 */
const props = defineProps({
    exportTabEnum: {type: String, required: true},
    format: {type: String, required: true, validator: (value) => ["xlsx", "docx"].includes(value)},
});
const emit = defineEmits(["close"]);
const $t = useTranslation();

const isWord = computed(() => props.format === "docx");

const characteristicGroups = [
    {key: "project_state_ids", label: "Project status", options: "project_states"},
    {key: "category_ids", label: "Categories", options: "categories"},
    {key: "genre_ids", label: "Genres", options: "genres"},
    {key: "sector_ids", label: "Sectors", options: "sectors"},
    {key: "event_type_ids", label: "Projects with events of type", options: "event_types"},
];

const emptyFilters = () => ({
    date_start: "",
    date_end: "",
    project_group_id: null,
    project_state_ids: [],
    category_ids: [],
    genre_ids: [],
    sector_ids: [],
    event_type_ids: [],
    member_ids: [],
});

const defaultColumns = () => [
    {key: "system:project_name", title: null, show_label: false},
    {key: "system:events", title: null, show_label: false},
];

const loading = ref(true);
const options = ref(null);
const templates = ref([]);
const selectedTemplateId = ref(null);
const members = ref([]);
const openGroups = ref({});
const projectCount = ref(null);
const hiddenColumns = ref(0);
const exporting = ref(false);
const exportError = ref("");
const showNewTemplateName = ref(false);
const newTemplateName = ref("");
const templateError = ref("");
const templateMessage = ref("");
const savingTemplate = ref(false);

const form = reactive({
    columns: defaultColumns(),
    filters: emptyFilters(),
    event_column: {event_type_ids: []},
});

const selectedTemplate = computed(() => templates.value.find((template) => template.id === selectedTemplateId.value) ?? null);
const hasEventsColumn = computed(() => form.columns.some((column) => column.key === "system:events"));

const dateRangeInvalid = computed(() => {
    const {date_start: start, date_end: end} = form.filters;
    if (!start && !end) {
        return false;
    }

    return !start || !end || start > end;
});

const exportDisabled = computed(() => form.columns.length === 0 || dateRangeInvalid.value || exporting.value || projectCount.value === 0);

const payload = () => ({
    format: props.format,
    template_id: selectedTemplateId.value,
    columns: form.columns.map((column) => ({
        key: column.key,
        title: column.title?.trim() || null,
        show_label: Boolean(column.show_label),
    })),
    filters: {
        ...form.filters,
        date_start: form.filters.date_start || null,
        date_end: form.filters.date_end || null,
        member_ids: members.value.map((member) => member.id),
    },
    event_column: {event_type_ids: [...form.event_column.event_type_ids]},
});

const toggleGroup = (key) => {
    openGroups.value = {...openGroups.value, [key]: !openGroups.value[key]};
};

const toggleId = (list, id, checked) => {
    const index = list.indexOf(id);
    if (checked && index === -1) {
        list.push(id);
    } else if (!checked && index > -1) {
        list.splice(index, 1);
    }
};

const addMember = (user) => {
    if (members.value.some((member) => member.id === user.id)) {
        return;
    }
    members.value = [...members.value, {id: user.id, name: `${user.first_name ?? ""} ${user.last_name ?? ""}`.trim()}];
};

const removeMember = (id) => {
    members.value = members.value.filter((member) => member.id !== id);
};

const applyTemplate = () => {
    templateMessage.value = "";
    const template = selectedTemplate.value;
    if (!template) {
        return;
    }
    const filters = template.settings?.filters ?? {};
    form.columns = template.columns.map((column) => ({...column}));
    form.filters = {
        ...emptyFilters(),
        ...filters,
        // Zeitraum gehört nicht zur Vorlage: aktuelle Eingabe behalten
        date_start: form.filters.date_start,
        date_end: form.filters.date_end,
    };
    form.event_column = {event_type_ids: [...(template.settings?.event_column?.event_type_ids ?? [])]};
    members.value = (template.members ?? []).map((member) => ({...member}));
};

const loadTemplates = async () => {
    templates.value = (await axios.get(route("projects.content-export.templates.index"))).data;
};

const saveTemplate = async (template) => {
    savingTemplate.value = true;
    templateError.value = "";
    templateMessage.value = "";
    const data = {
        name: template ? template.name : newTemplateName.value.trim(),
        columns: payload().columns,
        settings: {filters: payload().filters, event_column: payload().event_column},
    };
    try {
        const response = template
            ? await axios.patch(route("projects.content-export.templates.update", template.id), data)
            : await axios.post(route("projects.content-export.templates.store"), data);
        await loadTemplates();
        selectedTemplateId.value = response.data.id;
        showNewTemplateName.value = false;
        newTemplateName.value = "";
        templateMessage.value = $t("Template saved.");
    } catch (error) {
        templateError.value = error.response?.data?.errors?.name?.[0] ?? $t("Template could not be saved.");
    } finally {
        savingTemplate.value = false;
    }
};

const deleteTemplate = async () => {
    const template = selectedTemplate.value;
    if (!template || !window.confirm($t("Delete template \"{0}\"?", [template.name]))) {
        return;
    }
    savingTemplate.value = true;
    try {
        await axios.delete(route("projects.content-export.templates.destroy", template.id));
        selectedTemplateId.value = null;
        await loadTemplates();
    } finally {
        savingTemplate.value = false;
    }
};

const refreshPreview = debounce(async () => {
    if (dateRangeInvalid.value) {
        projectCount.value = null;
        return;
    }
    try {
        const {columns, filters, event_column} = payload();
        const response = await axios.post(route("projects.content-export.preview"), {columns, filters, event_column});
        projectCount.value = response.data.count;
        hiddenColumns.value = response.data.hidden_columns;
    } catch {
        projectCount.value = null;
    }
}, 400);

watch([() => form.filters, () => form.columns, members], () => {
    exportError.value = "";
    refreshPreview();
}, {deep: true});

const initializeDownload = async () => {
    exporting.value = true;
    exportError.value = "";
    try {
        const token = (await axios.post(route("projects.content-export.cache"), payload())).data;
        window.open(route("projects.content-export.download", {cacheToken: token}), "_blank", "noopener");
        emit("close");
    } catch {
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};

onMounted(async () => {
    try {
        const [optionsResponse] = await Promise.all([
            axios.get(route("projects.content-export.options")),
            loadTemplates(),
        ]);
        options.value = optionsResponse.data;
        refreshPreview();
    } catch {
        options.value = null;
    } finally {
        loading.value = false;
    }
});
</script>
