<template>
    <div class="tooltip">
        <slot></slot>
        <div class="tooltip-content">
            {{ event.eventName }}
        </div>
    </div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";

export default {
    props: ['event'],
    mixins: [Permissions],
}
</script>

<style scoped>
.tooltip {
    position: relative;
}

.tooltip .tooltip-content {
    position: absolute;
    visibility: hidden;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    background-color: #000;
    color: #fff;
    padding: 5px;
    border-radius: 3px;
    font-size: 14px;
}

.tooltip:hover .tooltip-content {
    visibility: visible;
}
</style>
