<template>
    <!-- Eine Quelle für die Erklärung der Projektrechte-Hierarchie (Nutzerrechte, Presets, Einladung) -->
    <SettingsGuideBanner
        variant="static"
        icon="IconShieldLock"
        title="How project permissions work together"
        :paragraphs="[
            '1. Access: whether a project can be opened is decided by membership in the project team (person or department) or by the global permission “Read permissions for all projects”.',
            '2. Editing: writing in a project requires write permission within that project – the “Write permission” or “Project management” checkbox in the project team, having created the project, or belonging to an assigned department. The global permission “Write permissions for all projects” grants this for every project.',
            '3. Components: the permission settings of individual tab components (system settings) can only restrict these rights further – never extend them.'
        ]"
        footnote="“Project management” only allows a person to be marked as project manager in a project team – it grants no access to other projects."
    />
</template>

<script setup>
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
</script>
