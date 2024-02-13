<template>
    <app-layout>
        <div class="max-w-screen-xl ml-14 mr-40 mb-4">
            <div class="mb-5">
                <h2 class="headline1 mb-2">Tooleinstellungen</h2>
                <div class="headline3Light">
                    Definiere globale Einstellungen für dein artwork.
                </div>
            </div>
            <ToolSettingsTabs class="mb-5"/>
            <div>
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue';
import ToolSettingsTabs from "@/Pages/ToolSettings/ToolSettingsTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

export default defineComponent({
    components: {
        AppLayout,
        ToolSettingsTabs
    }
});
</script>
