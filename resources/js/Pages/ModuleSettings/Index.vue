<template>
    <ToolSettingsHeader :title="$t('Module visibility')">
        <div v-if="usePage().props.flash.success"
             class="mt-4 w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ usePage().props.flash.success }}
        </div>
        <div class="flex flex-col mt-4 gap-y-2">
            <div v-for="moduleSetting in computedModuleSettings" class="flex flex-row gap-x-4">
                <label :for="'cb-'+moduleSetting.value.menu">{{  $t(moduleSetting.value.menu) }}</label>
                <input :id="'cb-'+moduleSetting.value.menu"
                       type="checkbox"
                       v-model="moduleSetting.value.enabled"
                       @update:model-value="(enabled) => onCheckboxChange(enabled, moduleSetting.value.menu)"
                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300 rounded-full"/>
            </div>
        </div>
    </ToolSettingsHeader>
</template>

<script setup>
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
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
