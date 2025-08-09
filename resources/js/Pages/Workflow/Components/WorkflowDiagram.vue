<template>
    <div class="workflow-diagram">
        <svg ref="svg" :width="width" :height="height" class="workflow-svg">
            <!-- Transitions (arrows) -->
            <g class="transitions">
                <g v-for="(transition, index) in transitions" :key="'transition-' + index" class="transition">
                    <path :d="transition.path" class="transition-path" :class="{ 'transition-path-highlighted': transition.highlighted }" />
                    <polygon :points="transition.arrowhead" class="transition-arrowhead" :class="{ 'transition-arrowhead-highlighted': transition.highlighted }" />
                    <text :x="transition.labelX" :y="transition.labelY" class="transition-label">{{ transition.name }}</text>
                </g>
            </g>

            <!-- Places (states) -->
            <g class="places">
                <g v-for="place in places" :key="place.name" class="place" :transform="`translate(${place.x}, ${place.y})`">
                    <circle r="30" :class="getPlaceClass(place)" />
                    <text dy=".3em" text-anchor="middle" class="place-label">{{ place.name }}</text>
                </g>
            </g>
        </svg>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue'

export default defineComponent({
    props: {
        config: {
            type: Object,
            required: true
        },
        currentPlace: {
            type: String,
            default: null
        },
        width: {
            type: Number,
            default: 800
        },
        height: {
            type: Number,
            default: 400
        }
    },
    setup(props) {
        const svg = ref(null)

        // Compute the positions of places in a circle layout
        const places = computed(() => {
            if (!props.config || !props.config.places) return []

            const centerX = props.width / 2
            const centerY = props.height / 2
            const radius = Math.min(props.width, props.height) / 2 - 60
            const count = props.config.places.length

            return props.config.places.map((place, index) => {
                const angle = (index / count) * 2 * Math.PI
                return {
                    ...place,
                    x: centerX + radius * Math.cos(angle),
                    y: centerY + radius * Math.sin(angle)
                }
            })
        })

        // Compute the paths for transitions
        const transitions = computed(() => {
            if (!props.config || !props.config.transitions) return []

            return props.config.transitions.map(transition => {
                // Find the source and target places
                const fromPlaces = transition.from.map(fromName =>
                    places.value.find(p => p.name === fromName)
                ).filter(Boolean)

                const toPlace = places.value.find(p => p.name === transition.to)

                if (fromPlaces.length === 0 || !toPlace) return null

                // For simplicity, we'll just use the first from place
                const fromPlace = fromPlaces[0]

                // Calculate the angle between the two places
                const dx = toPlace.x - fromPlace.x
                const dy = toPlace.y - fromPlace.y
                const angle = Math.atan2(dy, dx)

                // Start and end points (adjusted to be on the circle edge)
                const startX = fromPlace.x + 30 * Math.cos(angle)
                const startY = fromPlace.y + 30 * Math.sin(angle)
                const endX = toPlace.x - 30 * Math.cos(angle)
                const endY = toPlace.y - 30 * Math.sin(angle)

                // Control point for the curve (to make it bend slightly)
                const midX = (startX + endX) / 2
                const midY = (startY + endY) / 2
                const controlX = midX + 30 * Math.sin(angle)
                const controlY = midY - 30 * Math.cos(angle)

                // Path for the curved line
                const path = `M ${startX} ${startY} Q ${controlX} ${controlY}, ${endX} ${endY}`

                // Calculate arrowhead points
                const arrowLength = 10
                const arrowWidth = 6
                const arrowAngle = Math.atan2(endY - controlY, endX - controlX)
                const arrowPoint1X = endX - arrowLength * Math.cos(arrowAngle) + arrowWidth * Math.sin(arrowAngle)
                const arrowPoint1Y = endY - arrowLength * Math.sin(arrowAngle) - arrowWidth * Math.cos(arrowAngle)
                const arrowPoint2X = endX - arrowLength * Math.cos(arrowAngle) - arrowWidth * Math.sin(arrowAngle)
                const arrowPoint2Y = endY - arrowLength * Math.sin(arrowAngle) + arrowWidth * Math.cos(arrowAngle)
                const arrowhead = `${endX},${endY} ${arrowPoint1X},${arrowPoint1Y} ${arrowPoint2X},${arrowPoint2Y}`

                // Position for the transition label
                const labelX = controlX
                const labelY = controlY - 10

                // Check if this transition is highlighted (can be used to show current transitions)
                const highlighted = props.currentPlace && transition.from.includes(props.currentPlace)

                return {
                    ...transition,
                    path,
                    arrowhead,
                    labelX,
                    labelY,
                    highlighted
                }
            }).filter(Boolean)
        })

        // Function to determine the CSS class for a place based on its type
        const getPlaceClass = (place) => {
            const classes = ['place-circle']

            if (place.type === 'start') {
                classes.push('place-start')
            } else if (place.type === 'end') {
                classes.push('place-end')
            }

            if (props.currentPlace === place.name) {
                classes.push('place-current')
            }

            return classes.join(' ')
        }

        return {
            svg,
            places,
            transitions,
            getPlaceClass
        }
    }
})
</script>

<style scoped>
.workflow-diagram {
    overflow: auto;
}

.workflow-svg {
    font-family: sans-serif;
}

.place-circle {
    fill: #e5e7eb;
    stroke: #9ca3af;
    stroke-width: 2;
}

.place-start {
    fill: #d1fae5;
    stroke: #10b981;
}

.place-end {
    fill: #fee2e2;
    stroke: #ef4444;
}

.place-current {
    fill: #dbeafe;
    stroke: #3b82f6;
    stroke-width: 3;
}

.place-label {
    fill: #1f2937;
    font-size: 12px;
    user-select: none;
}

.transition-path {
    fill: none;
    stroke: #9ca3af;
    stroke-width: 2;
}

.transition-path-highlighted {
    stroke: #3b82f6;
    stroke-width: 3;
}

.transition-arrowhead {
    fill: #9ca3af;
}

.transition-arrowhead-highlighted {
    fill: #3b82f6;
}

.transition-label {
    fill: #4b5563;
    font-size: 10px;
    text-anchor: middle;
    user-select: none;
}
</style>
