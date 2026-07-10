const STICKY_POSITION_TOLERANCE = 2;
const REVIEW_LINE_OFFSET = 48;

export const findActiveShiftPlanRequestRow = ({
    headerRect,
    rows,
    viewportHeight,
}) => {
    if (!headerRect || headerRect.top > STICKY_POSITION_TOLERANCE) {
        return null;
    }

    const reviewLine = headerRect.bottom + REVIEW_LINE_OFFSET;
    const visibleRows = rows.filter(row => (
        row.bottom > reviewLine && row.top < viewportHeight
    ));

    if (!visibleRows.length) {
        return null;
    }

    const activeRow = visibleRows.find(row => (
        row.top <= reviewLine && row.bottom > reviewLine
    )) ?? visibleRows[0];

    return {
        rowKey: activeRow.rowKey,
        rowOffset: activeRow.top - headerRect.bottom,
    };
};

export const calculateShiftPlanRequestScrollDelta = ({
    headerBottom,
    rowTop,
    rowOffset,
}) => rowTop - (headerBottom + rowOffset);

export const isShiftPlanRequestComparisonTarget = (reviewContext, requestId) => (
    reviewContext?.targetRequestId !== undefined &&
    Number(reviewContext.targetRequestId) === Number(requestId)
);
