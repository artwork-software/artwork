<template>
    <app-layout :title="$t('Toolsettings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconSettings"
                :title="$t('Toolsettings')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="$t('Define global settings for your artwork.')"
                :search-enabled="false"
            >
                <template #subtitle>
                    <div>{{ $t('Define global settings for your artwork.') }}</div>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5">
                        <span>
                            {{ $t('Installed version') }}:
                            <span class="font-medium text-text">{{ artworkVersion ?? $t('unknown') }}</span>
                        </span>
                        <span aria-hidden="true">·</span>
                        <a
                            :href="changelogUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-0.5 font-medium text-accent-600 hover:text-accent-700"
                            :title="$t('If the changelog lists a newer version, its changes are not live on your installation yet.')"
                        >
                            {{ $t('Changelog') }}
                            <IconExternalLink class="size-3" aria-hidden="true" />
                        </a>
                    </div>
                </template>
            </ToolbarHeader>

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
import {IconExternalLink, IconSettings} from '@tabler/icons-vue';
import {changelogManualPath, manualUrl} from "@/Helper/manualLinks.js";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    props: ['title'],
    mixins: [Permissions],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs,
        IconExternalLink
    },
    computed: {
        artworkVersion() {
            return this.$page.props.artworkVersion ?? null;
        },
        changelogUrl() {
            return manualUrl(changelogManualPath(this.artworkVersion), this.$i18n.locale);
        }
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
                    name: this.$t('Externe Nutzerverwaltung'),
                    href: route('tool.external-user-management'),
                    current: route().current('tool.external-user-management'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('Communication & Legal'),
                    href: route('tool.communication-and-legal'),
                    current: route().current('tool.communication-and-legal'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
                {
                    name: this.$t('Regional formats'),
                    href: route('tool.formats'),
                    current: route().current('tool.formats'),
                    permission: this.$can('change tool settings')
                },
                {
                    name: this.$t('Interfaces'),
                    href: route('tool.interfaces'),
                    current: route().current('tool.interfaces'),
                    // Webhooks hängen an einer eigenen Permission und leben auf derselben Seite
                    permission: this.$can('change tool settings') || this.$can('manage webhooks') || this.hasAdminRole()
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
                {
                    name: this.$t('Mail'),
                    href: route('tool.mail'),
                    current: route().current('tool.mail'),
                    permission: this.$can('change tool settings') || this.hasAdminRole()
                },
            ]
        }
    }
});
</script>
