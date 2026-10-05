<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="modalTitle"
        :description="modalDescription"
        modal-size="max-w-4xl"
    >
        <!-- Inhalt kommt vollständig aus dem Katalog (CalendarSettingsCatalog.js):
             je Ansicht nur die Einstellungen, die dort auch gelesen werden. -->
        <div class="p-5 space-y-6">
            <template v-for="(section, index) in sections" :key="section.id">
                <hr v-if="index > 0" class="border-border-subtle">
                <div>
                    <h3 class="text-sm font-semibold text-text mb-3">
                        {{ $t(section.title) }}
                    </h3>

                    <div class="grid grid-cols-1 gap-4" :class="section.columns === 2 ? 'md:grid-cols-2' : ''">
                        <template v-for="item in section.items" :key="item.key">
                            <BaseCheckbox
                                v-if="(item.type ?? 'checkbox') === 'checkbox'"
                                :id="item.key"
                                v-model="form[item.key]"
                                :label="$t(item.label)"
                                :description="$t(isDisabled(item) && item.disabledDescription ? item.disabledDescription : item.description)"
                                :disabled="isDisabled(item)"
                                :title="isDisabled(item) && item.disabledDescription ? $t(item.disabledDescription) : null"
                                :class="{ 'pl-6': item.indent, 'opacity-60': isDisabled(item) }"
                                @change="(value) => item.onChange?.(form, value)"
                            />
                        </template>
                    </div>

                    <!-- Listbox-Einträge unter dem Raster, damit das Dropdown das Checkbox-Raster nicht aufbricht -->
                    <template v-for="item in section.items" :key="'lb-' + item.key">
                        <div v-if="item.type === 'room_column_width'" class="mt-4 md:max-w-sm">
                            <ArtworkBaseListbox
                                :model-value="selectedColumnWidthOption"
                                @update:model-value="onColumnWidthChange"
                                :items="columnWidthOptions"
                                by="id"
                                option-label="name"
                                :label="$t(item.label)"
                                :enable-search="false"
                            />
                            <p class="text-text-subtle text-xs mt-1">
                                {{ $t(item.description) }}
                            </p>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="flex flex-col-reverse gap-3 px-5 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <a
                :href="manualHref"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-accent-600 hover:text-accent-700"
            >
                <PropertyIcon name="IconBook" class="h-4 w-4" />
                {{ $t('What does each option do? Open the manual') }}
                <PropertyIcon name="IconExternalLink" class="h-3.5 w-3.5" />
            </a>
            <BaseUIButton :label="$t('Save')" is-add-button @click="save"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { can, is } from "laravel-permission-to-vuejs";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { displaySettingsManualPath, manualUrl } from "@/Helper/manualLinks.js";
import {
    VIEW,
    resolveView,
    sectionsForView,
    settingsForView,
    initialValue,
    isShiftPlanView,
} from "@/Artwork/Modals/CalendarSettingsCatalog.js";

const props = defineProps({
    isPlanning: { type: Boolean, default: false },
    inShiftPlan: { type: Boolean, default: false },
    isDailyView: { type: Boolean, default: false },
    isListView: { type: Boolean, default: false },
    isInProjectView: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const page = usePage().props;
const view = resolveView(props);
const { t, locale } = useI18n();
const manualHref = computed(() => manualUrl(displaySettingsManualPath(view), locale.value));

// Gespeicherte Settings-Zeile der Ansicht (mit derselben Fallback-Kette wie die Ansichten selbst)
const activeSettings = (() => {
    switch (view) {
        case VIEW.SHIFT_LIST:
            return page.listViewSettings;
        case VIEW.SHIFT_DAY:
        case VIEW.PROJECT_SHIFT_TAB:
            return page.shift_plan_daily_settings ?? page.shift_plan_settings ?? page.auth.user.calendar_settings;
        case VIEW.SHIFT_WEEK:
            return page.shift_plan_settings ?? page.auth.user.calendar_settings;
        case VIEW.CALENDAR_DAILY:
        case VIEW.PLANNING_DAILY:
            return page.daily_view_calendar_settings ?? page.auth.user.calendar_settings;
        default:
            return page.auth.user.calendar_settings;
    }
})();

// Tagesbemerkungen: Instanz-Setting + Rechte (global geteilt via HandleInertiaRequests)
const dayRemarks = page.day_remarks ?? { enabled: false, mandatory: false, can_view: false, can_edit: false };

const ctx = { view, page, settings: activeSettings, dayRemarks, can, is };

const sections = sectionsForView(view, ctx);
const visibleItems = settingsForView(view, ctx);

// Formular: Steuerfelder für den Controller + genau die sichtbaren Einstellungen.
// Nicht sichtbare Spalten werden nicht gesendet und bleiben damit unverändert.
const form = useForm({
    ...(view === VIEW.SHIFT_LIST
        ? {}
        : {
            is_daily_view: props.isDailyView,
            is_shift_plan: props.inShiftPlan,
            is_planning: props.isPlanning,
        }),
    ...Object.fromEntries(visibleItems.map((item) => [item.key, initialValue(item, ctx)])),
});

const isDisabled = (item) => (item.disabled ? item.disabled(ctx, form) : false);

const modalTitle = computed(() => (isShiftPlanView(view) || view === VIEW.SHIFT_LIST ? 'Display Settings' : 'Calendar Settings'));
const modalDescription = computed(() => (isShiftPlanView(view) || view === VIEW.SHIFT_LIST
    ? 'Only settings that affect this view are shown.'
    : 'Configure your calendar settings here.'));

// Raumspaltenbreite: feste Presets, gespeichert als px-Wert
const columnWidthOptions = computed(() => [
    { id: 160, name: `${t('Narrow')} (160 px)` },
    { id: 212, name: `${t('Standard')} (212 px)` },
    { id: 280, name: `${t('Wide')} (280 px)` },
    { id: 320, name: `${t('Very wide')} (320 px)` },
]);

const selectedColumnWidthOption = computed(() =>
    columnWidthOptions.value.find((option) => option.id === form.calendar_column_width) ?? columnWidthOptions.value[1]
);

const onColumnWidthChange = (option) => {
    if (option?.id) {
        form.calendar_column_width = option.id;
    }
};

const save = () => {
    if (view === VIEW.SHIFT_LIST) {
        form.patch(
            route("user.shift_list_view_settings.update", { user: page.auth.user.id }),
            { preserveScroll: true, preserveState: false }
        );
        return;
    }

    const valuesToReload = [];
    if (form.project_management) valuesToReload.push("leaders");
    if (form.project_status) valuesToReload.push("status");
    // Räume/Kalender immer neu laden: Raum-Sichtbarkeit und Kachelinhalte hängen an den Settings
    valuesToReload.push("rooms", "calendar", "calendarData");

    form.patch(
        route("user.calendar_settings.update", { user: page.auth.user.id }),
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                router.reload({ only: valuesToReload });
            },
        }
    );
};
</script>
