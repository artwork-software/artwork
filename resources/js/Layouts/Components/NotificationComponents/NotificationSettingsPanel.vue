<template>
    <div class="mt-10 max-w-5xl">
        <div class="rounded-lg border border-border-subtle p-4 text-sm text-text-muted space-y-1">
            <p class="text-text font-semibold">{{ $t('How do notifications reach you?') }}</p>
            <p>{{ $t('In the notification centre you always receive every notification that concerns you. Here you decide what additionally reaches you.') }}</p>
            <p><span class="font-semibold text-text">{{ $t('E-mail') }}:</span> {{ $t('immediately or collected in a summary e-mail – choose the frequency per type.') }}</p>
            <p><span class="font-semibold text-text">{{ $t('Hint in artwork') }}:</span> {{ $t('a short hint in the top right corner while you are logged in.') }}</p>
            <p><span class="font-semibold text-text">{{ $t('Summary e-mails') }}:</span> {{ $t('Daily at 9 a.m. · twice a week on Monday and Thursday · once a week on Monday.') }}</p>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-72">
                <BaseInput id="notification-settings-search" v-model="query" label="Search notifications" is-small />
            </div>
            <div class="w-full sm:w-56">
                <ArtworkBaseListbox
                    :model-value="null"
                    :items="frequencyOptions"
                    option-key="id"
                    by="id"
                    label="Frequency for all e-mails"
                    placeholder="Choose frequency"
                    is-small
                    :disabled="busy"
                    @update:model-value="(option) => bulk({ frequency: option.id })"
                />
            </div>
            <BaseUIButton variant="secondary" hide-icon :disabled="busy" @click="bulk({ enabled_email: !allEmailOn })">
                {{ allEmailOn ? $t('Turn off all e-mails') : $t('Turn on all e-mails') }}
            </BaseUIButton>
            <BaseUIButton variant="secondary" hide-icon :disabled="busy" @click="bulk({ enabled_push: !allPushOn })">
                {{ allPushOn ? $t('Turn off all hints') : $t('Turn on all hints') }}
            </BaseUIButton>
            <template v-if="!confirmReset">
                <BaseUIButton variant="secondary" hide-icon :disabled="busy" @click="confirmReset = true">
                    {{ $t('Restore defaults') }}
                </BaseUIButton>
            </template>
            <div v-else class="flex items-center gap-2 text-sm">
                <span>{{ $t('Reset all notification settings?') }}</span>
                <BaseUIButton variant="primary" hide-icon :disabled="busy" @click="resetDefaults">{{ $t('Reset') }}</BaseUIButton>
                <BaseUIButton variant="secondary" hide-icon @click="confirmReset = false">{{ $t('Cancel') }}</BaseUIButton>
            </div>
        </div>

        <p v-if="visibleGroups.length === 0" class="mt-10 text-sm text-text-muted">
            {{ $t('No notification types match your search.') }}
        </p>

        <section
            v-for="group in visibleGroups"
            :id="'notification-group-' + group.key"
            :key="group.key"
            class="mt-8 rounded-lg border border-border-subtle"
        >
            <header class="flex flex-wrap items-start justify-between gap-4 p-4 border-b border-border-subtle">
                <div class="min-w-0 max-w-xl">
                    <h3 class="font-lexend font-semibold text-text">{{ $t(group.title) }}</h3>
                    <p class="text-sm text-text-muted mt-1">{{ $t(group.description) }}</p>
                </div>
                <div class="flex flex-wrap gap-6">
                    <ArtworkBaseToggle
                        :id="'group-email-' + group.key"
                        :model-value="groupState(group, 'enabled_email') === 'all'"
                        :label="groupLabel(group, 'enabled_email', 'All e-mails')"
                        is-small
                        :disabled="busy"
                        @update:model-value="bulk({ groupType: group.key, enabled_email: groupState(group, 'enabled_email') !== 'all' })"
                    />
                    <ArtworkBaseToggle
                        :id="'group-push-' + group.key"
                        :model-value="groupState(group, 'enabled_push') === 'all'"
                        :label="groupLabel(group, 'enabled_push', 'All hints')"
                        is-small
                        :disabled="busy"
                        @update:model-value="bulk({ groupType: group.key, enabled_push: groupState(group, 'enabled_push') !== 'all' })"
                    />
                </div>
            </header>

            <div
                v-for="setting in group.settings"
                :id="'notification-type-' + setting.type"
                :key="setting.id"
                class="grid grid-cols-1 gap-3 p-4 border-b border-border-subtle last:border-b-0 md:grid-cols-[minmax(0,1fr)_auto_auto_12rem] md:items-center md:gap-6"
                :class="{ 'bg-accent-50': highlightedType === setting.type }"
            >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-text">{{ $t(setting.title) }}</p>
                    <p class="text-sm text-text-muted">{{ $t(setting.description) }}</p>
                </div>
                <ArtworkBaseToggle
                    :id="'email-' + setting.id"
                    :model-value="setting.enabled_email"
                    label="E-mail"
                    use-translation
                    is-small
                    @update:model-value="(value) => update(setting, { enabled_email: value })"
                />
                <ArtworkBaseToggle
                    :id="'push-' + setting.id"
                    :model-value="setting.enabled_push"
                    label="Hint in artwork"
                    use-translation
                    is-small
                    @update:model-value="(value) => update(setting, { enabled_push: value })"
                />
                <ArtworkBaseListbox
                    :model-value="frequencyOption(setting.frequency)"
                    :items="frequencyOptions"
                    option-key="id"
                    by="id"
                    label="E-mail frequency"
                    is-small
                    :disabled="!setting.enabled_email"
                    @update:model-value="(option) => update(setting, { frequency: option.id })"
                />
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import { showAppToast } from "@/Helper/appToast.js";
import { filterNotificationGroups, groupChannelState } from "@/Layouts/Components/NotificationComponents/notificationSettings.js";

const props = defineProps({
    groups: { type: Array, required: true },
    frequencies: { type: Array, required: true },
});

const { t } = useI18n();

const groups = ref(JSON.parse(JSON.stringify(props.groups)));
const query = ref('');
const busy = ref(false);
const confirmReset = ref(false);
const highlightedType = ref(null);

const frequencyOptions = computed(() => props.frequencies.map((frequency) => ({
    id: frequency.value,
    name: t(frequency.title),
})));
const frequencyOption = (value) => frequencyOptions.value.find((option) => option.id === value) ?? null;

const visibleGroups = computed(() => filterNotificationGroups(groups.value, query.value, t));
const allSettings = computed(() => groups.value.flatMap((group) => group.settings));
const allEmailOn = computed(() => allSettings.value.every((setting) => setting.enabled_email));
const allPushOn = computed(() => allSettings.value.every((setting) => setting.enabled_push));

const groupState = (group, channel) => groupChannelState(group.settings, channel);
const groupLabel = (group, channel, label) => groupState(group, channel) === 'some'
    ? `${t(label)} (${t('partially')})`
    : t(label);

const saved = () => showAppToast('success', t('Notification settings saved'));

/** Optimistisch speichern, bei Fehler zurückdrehen (Fehlermeldung kommt vom globalen Handler) */
const update = async (setting, changes) => {
    const previous = { ...setting };
    Object.assign(setting, changes);
    try {
        await axios.patch(route('notifications.settings', setting.id), changes);
        saved();
    } catch (error) {
        Object.assign(setting, previous);
    }
};

const replaceGroups = (response) => {
    groups.value = response.data.groups;
};

const bulk = async (changes) => {
    busy.value = true;
    try {
        replaceGroups(await axios.patch(route('notifications.settings.bulk'), changes));
        saved();
    } finally {
        busy.value = false;
    }
};

const resetDefaults = async () => {
    busy.value = true;
    try {
        replaceGroups(await axios.post(route('notifications.settings.reset')));
        confirmReset.value = false;
        saved();
    } finally {
        busy.value = false;
    }
};

// Deep-Link aus einer Benachrichtigung: ?tab=settings&type=NOTIFICATION_…
onMounted(() => {
    const type = new URLSearchParams(window.location.search).get('type');
    if (!type) {
        return;
    }
    highlightedType.value = type;
    document.getElementById('notification-type-' + type)?.scrollIntoView({ block: 'center' });
});
</script>
