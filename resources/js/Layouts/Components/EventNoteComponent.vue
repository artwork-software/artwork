<template>
    <div class="my-2" @click="openTextField" v-if="!showTextField" :class="canEditDescription ? '' : 'cursor-default'">
        <div v-if="event.description?.length === 0 || event.description === null">
            <PropertyIcon v-if="canEditDescription" name="IconNote" class="w-4 h-4 text-text-muted"/>
        </div>
        <p
            v-else
            ref="descriptionAnchor"
            class="text-xs"
            @mouseenter="startDescriptionTooltip"
            @mouseleave="hideDescriptionTooltip"
        >
            {{ cutDescription }}
        </p>
    </div>
    <!-- Teleport + fixed: entkommt overflow-hidden/sticky der Kalenderzellen
         (gleiches Muster wie DayRemarkHoverTooltip) -->
    <Teleport to="body">
        <div
            v-if="showDescriptionTooltip"
            ref="descriptionTooltip"
            class="fixed z-[999] pointer-events-none max-w-[320px] rounded-lg border border-border-subtle bg-surface shadow-overlay px-2.5 py-2"
            :style="descriptionTooltipStyle"
        >
            <p class="text-[11px] leading-[15px] text-text whitespace-pre-line break-words">
                {{ event.description }}
            </p>
        </div>
    </Teleport>
    <div v-if="showTextField">
        <div>
            <textarea ref="descriptionField" v-model="eventDescription.description"
                      class="w-[95%] h-20 p-1 text-sm border-text-muted/30 rounded-lg" maxlength="250"
                      @focusout="updateDescription"/>
            <div class="text-xs text-end text-text-muted">
                {{ eventDescription.description.length }} / 250
            </div>
        </div>
    </div>
</template>

<script>
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import axios from "axios";

export default {
    name: "EventNoteComponent",
    components: {PropertyIcon},
    props: {
        event: {
            type: Object,
            required: true
        },
    },
    mixins: [IconLib, Permissions],
    computed: {
        isDescriptionTruncated() {
            return this.event.description?.length > 70;
        },
        cutDescription() {
            return this.isDescriptionTruncated ? this.event.description.substring(0, 70) + '...' : this.event.description;
        },
        // Spiegelt die EventPolicy::update-Prüfung des Backends (event.update.description
        // antwortet sonst mit 403); Raum-Admins sind clientseitig nicht auflösbar und
        // erhalten das Textfeld daher nur, wenn sie zusätzlich eine der Rechte/Rollen haben.
        canEditDescription() {
            return this.hasAdminRole() ||
                this.$canAny([
                    'management projects',
                    'can edit planning calendar',
                    'create events without request'
                ]) ||
                this.event?.created_by?.id === this.$page.props.auth.user.id;
        }
    },
    data() {
        return {
            showTextField: false,
            showDescriptionTooltip: false,
            descriptionTooltipStyle: {},
            eventDescription: {
                description: this.event.description ? this.event.description : '',
                originalDescription: this.event.description ? this.event.description : ''
            }
        }
    },
    beforeUnmount() {
        this.hideDescriptionTooltip();
    },
    watch: {
        'event.description'(newVal) {
            if (!this.showTextField) {
                this.eventDescription.description = newVal ?? '';
                this.eventDescription.originalDescription = newVal ?? '';
            }
        }
    },
    methods: {
        startDescriptionTooltip() {
            if (!this.isDescriptionTruncated) {
                return;
            }
            this.descriptionTooltipTimer = setTimeout(() => this.openDescriptionTooltip(), 250);
            window.addEventListener('scroll', this.hideDescriptionTooltip, true);
        },
        async openDescriptionTooltip() {
            const rect = this.$refs.descriptionAnchor?.getBoundingClientRect();
            if (!rect) {
                return;
            }
            // Erst unsichtbar rendern und die echte Höhe messen, dann unter bzw.
            // über dem Anker platzieren und im Viewport festklemmen
            this.descriptionTooltipStyle = {left: '-9999px', top: '0px'};
            this.showDescriptionTooltip = true;
            await this.$nextTick();
            const maxWidth = 320;
            const height = this.$refs.descriptionTooltip?.offsetHeight ?? 0;
            const left = Math.max(8, Math.min(rect.left, window.innerWidth - maxWidth - 12));
            let top = rect.bottom + 6;
            if (top + height > window.innerHeight - 8) {
                top = rect.top - height - 6;
            }
            top = Math.max(8, Math.min(top, window.innerHeight - height - 8));
            this.descriptionTooltipStyle = {left: `${left}px`, top: `${top}px`};
        },
        hideDescriptionTooltip() {
            clearTimeout(this.descriptionTooltipTimer);
            this.showDescriptionTooltip = false;
            window.removeEventListener('scroll', this.hideDescriptionTooltip, true);
        },
        async updateDescription() {
            if (this.eventDescription.description !== this.eventDescription.originalDescription) {
                try {
                    await axios.patch(route('event.update.description', this.event.id), {
                        description: this.eventDescription.description
                    });
                    this.eventDescription.originalDescription = this.eventDescription.description;
                    this.showTextField = false;
                } catch (error) {
                    console.error('Failed to update description:', error);
                }
            } else {
                this.showTextField = false;
            }
        },
        openTextField() {
            if (!this.canEditDescription) {
                return;
            }
            // Der Anker verschwindet per v-if — mouseleave feuert dann nicht mehr
            this.hideDescriptionTooltip();
            this.showTextField = true
            this.$nextTick(() => {
                this.$refs.descriptionField.focus()
            })
        }
    }
}
</script>
