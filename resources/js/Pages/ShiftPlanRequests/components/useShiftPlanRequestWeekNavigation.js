import {nextTick, onBeforeUnmount, onMounted, ref, watch} from 'vue';
import {
    calculateShiftPlanRequestScrollDelta,
    findActiveShiftPlanRequestRow,
    isShiftPlanRequestComparisonTarget,
} from './shiftPlanRequestNavigation.js';
import { safeGetItem, safeRemoveItem, safeSetItem } from '@/Helper/safeStorage.js';

const comparisonContextStorageKey = 'shift-plan-request-comparison-context';
// Kontext verfällt, falls die Navigation abbricht und die Ziel-Anfrage erst später regulär geöffnet wird
const comparisonContextTtlMs = 30_000;
const highlightDurationMs = 2000;

export const useShiftPlanRequestWeekNavigation = (requestId) => {
    const weekNavigator = ref(null);
    const highlightedRowKey = ref(null);
    let highlightTimer = null;

    const getWeekNavigatorElement = () => weekNavigator.value?.$el ?? null;

    const rememberReviewPosition = (targetRequestId) => {
        const headerElement = getWeekNavigatorElement();
        if (!headerElement) {
            return;
        }

        const rowsWithRects = Array.from(document.querySelectorAll('[data-shift-plan-row-key]'))
            .map(element => {
                const rect = element.getBoundingClientRect();

                return {
                    rowKey: element.dataset.shiftPlanRowKey,
                    top: rect.top,
                    bottom: rect.bottom,
                };
            });

        const headerRect = headerElement.getBoundingClientRect();
        const reviewContext = findActiveShiftPlanRequestRow({
            headerRect,
            rows: rowsWithRects,
            viewportHeight: window.innerHeight,
        });

        if (!reviewContext) {
            safeRemoveItem(comparisonContextStorageKey, 'session');
            return;
        }

        safeSetItem(comparisonContextStorageKey, JSON.stringify({
            ...reviewContext,
            targetRequestId: Number(targetRequestId),
            storedAt: Date.now(),
        }), 'session');
    };

    const restoreReviewPosition = async (currentRequestId) => {
        const serializedContext = safeGetItem(comparisonContextStorageKey, 'session');
        if (!serializedContext) {
            return;
        }

        let reviewContext;
        try {
            reviewContext = JSON.parse(serializedContext);
        } catch {
            safeRemoveItem(comparisonContextStorageKey, 'session');
            return;
        }

        if (!reviewContext.storedAt || Date.now() - reviewContext.storedAt > comparisonContextTtlMs) {
            safeRemoveItem(comparisonContextStorageKey, 'session');
            return;
        }

        if (!isShiftPlanRequestComparisonTarget(reviewContext, currentRequestId)) {
            safeRemoveItem(comparisonContextStorageKey, 'session');
            return;
        }

        safeRemoveItem(comparisonContextStorageKey, 'session');
        await nextTick();

        window.requestAnimationFrame(() => {
            const headerElement = getWeekNavigatorElement();
            const targetRow = Array.from(document.querySelectorAll('[data-shift-plan-row-key]'))
                .find(element => element.dataset.shiftPlanRowKey === reviewContext.rowKey);

            if (!headerElement || !targetRow) {
                return;
            }

            const scrollDelta = calculateShiftPlanRequestScrollDelta({
                headerBottom: headerElement.getBoundingClientRect().bottom,
                rowTop: targetRow.getBoundingClientRect().top,
                rowOffset: reviewContext.rowOffset,
            });

            window.scrollBy({top: scrollDelta, behavior: 'auto'});
            highlightedRowKey.value = reviewContext.rowKey;

            window.clearTimeout(highlightTimer);
            highlightTimer = window.setTimeout(() => {
                highlightedRowKey.value = null;
            }, highlightDurationMs);
        });
    };

    watch(requestId, currentRequestId => restoreReviewPosition(currentRequestId));
    onMounted(() => restoreReviewPosition(requestId()));
    onBeforeUnmount(() => window.clearTimeout(highlightTimer));

    return {
        highlightedRowKey,
        rememberReviewPosition,
        weekNavigator,
    };
};
