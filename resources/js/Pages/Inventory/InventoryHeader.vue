<template>
    <app-layout :title="$t('Toolsettings') + ' - ' + title">
        <div class="max-w-screen-xl ml-14 mr-40 mb-4">
            <div class="mb-5">
                <h2 class="headline1 mb-2">Bestandsverwaltung</h2>
                <div class="headline3Light">
                    Pflege den Bestand aller Projekte für dein artwork.
                </div>
            </div>
            <InventoryTabs class="mb-5"/>
            <div>
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import InventoryTabs from "@/Pages/Inventory/InventoryTabs.vue";

export default defineComponent({
    props: [
        'title'
    ],
    components: {
        InventoryTabs,
        AppLayout
    }
})
</script>
