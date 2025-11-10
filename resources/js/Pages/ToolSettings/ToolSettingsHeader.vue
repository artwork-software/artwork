<template>
    <app-layout :title="$t('Toolsettings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconSettings"
                :title="$t('Toolsettings')"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :description="$t('Define global settings for your artwork.')"
                :search-enabled="false"
            />

            <!-- Tabs outside the header card -->
            <BaseTabs :tabs="tabs" />

            <div class="mt-6">
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {IconSettings} from '@tabler/icons-vue';
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    props: ['title'],
    mixins: [Permissions],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconSettings,
            tabs: [
                {
                    name: this.$t('Branding'),
                    href: route('tool.branding'),
                    current: route().current('tool.branding'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('Communication & Legal'),
                    href: route('tool.communication-and-legal'),
                    current: route().current('tool.communication-and-legal'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('Interfaces'),
                    href: route('tool.interfaces'),
                    current: route().current('tool.interfaces'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('Module visibility'),
                    href: route('tool.module-settings.index'),
                    current: route().current('tool.module-settings.index'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('File settings'),
                    href: route('tool.file-settings.index'),
                    current: route().current('tool.file-settings.index'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
            ]
        }
    }
});
</script>
