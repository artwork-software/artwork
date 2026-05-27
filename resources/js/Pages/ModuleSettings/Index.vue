<template>
    <ToolSettingsHeader :title="$t('Module visibility')">
        <div v-if="usePage().props.flash.success"
             class="mt-4 w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ usePage().props.flash.success }}
        </div>
        <p class="mt-2 text-sm text-secondary">
            {{ $t('Choose which modules should be displayed actively. If a module is unchecked, the respective entry in the main navigation (left sidebar) will be hidden for all users until the checkbox is activated again here.') }}
        </p>
        <div class="flex flex-col mt-4 gap-y-2">
            <div v-for="moduleSetting in computedModuleSettings" :key="moduleSetting.value.menu">
                <BaseCheckbox
                    :id="'cb-'+moduleSetting.value.menu"
                    :model-value="moduleSetting.value.enabled"
                    :label="$t(moduleSetting.value.menu)"
                    @update:model-value="(enabled) => onCheckboxChange(enabled, moduleSetting.value.menu)"
                />
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script setup>
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import {computed, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation(),
    props = defineProps({
        moduleSettings: {
            type: Object,
            required: true
        }
    }),
    computedModuleSettings = computed(() => {
        const computedModuleSettings = [];

        for (const [menu, enabled] of Object.entries(props.moduleSettings)) {
            computedModuleSettings.push(
                ref(
                    {
                    menu: menu,
                    enabled: Number(enabled) === 1
                    }
                )
            )
        }

        return computedModuleSettings;
    }),
    onCheckboxChange = (enabled, menu) => {
        router.patch(
            route('tool.module-settings.update'),
            {
                menu: menu,
                enabled: enabled
            },
            {
                preserveScroll: true
            }
        );
    };
</script>
