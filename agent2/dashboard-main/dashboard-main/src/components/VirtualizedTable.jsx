import { memo, useRef, useCallback } from 'react';
import { List } from 'react-window';

const ROW_HEIGHT = 53;
const OVERSCAN_COUNT = 5;

const VirtualizedTable = memo(({ items = [], columns, maxHeight = 500, emptyMessage = 'No data' }) => {
  const listRef = useRef(null);

  const Row = useCallback(({ index, style }) => {
    const item = items[index];
    if (!item) return null;
    return (
      <div style={{
        ...(style || {}),
        display: 'flex',
        alignItems: 'center',
        borderBottom: '1px solid var(--border-color, #e0e0e0)',
        backgroundColor: index % 2 === 0 ? 'transparent' : 'var(--bg-input, #f8f8f8)',
      }}>
        {columns.map((col, ci) => (
          <div key={ci} style={{
            flex: col.flex || 1,
            minWidth: col.minWidth || 0,
            padding: '8px 12px',
            overflow: 'hidden',
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap',
            fontSize: '0.85rem',
            color: col.muted ? 'var(--text-muted, #666)' : 'var(--text-body, #222)',
            fontWeight: col.bold ? 600 : 400,
            textAlign: col.align || 'left',
          }}>
            {col.render ? col.render(item) : item[col.key]}
          </div>
        ))}
      </div>
    );
  }, [items, columns]);

  if (!items || !items.length) {
    return <div className="text-center py-5 text-muted">{emptyMessage}</div>;
  }

  return (
    <div style={{ border: '1px solid var(--border-color, #e0e0e0)', borderRadius: '8px', overflow: 'hidden' }}>
      <div style={{
        display: 'flex',
        alignItems: 'center',
        backgroundColor: 'var(--bg-card, #f0f0f0)',
        borderBottom: '2px solid var(--border-color, #e0e0e0)',
        fontWeight: 600,
        fontSize: '0.8rem',
        color: 'var(--text-muted, #666)',
        textTransform: 'uppercase',
        letterSpacing: '0.5px',
      }}>
        {columns.map((col, ci) => (
          <div key={ci} style={{
            flex: col.flex || 1,
            minWidth: col.minWidth || 0,
            padding: '10px 12px',
            textAlign: col.align || 'left',
          }}>
            {col.header}
          </div>
        ))}
      </div>
      <List
        ref={listRef}
        height={Math.min(items.length * ROW_HEIGHT, maxHeight)}
        width="100%"
        rowCount={items.length}
        rowHeight={ROW_HEIGHT}
        overscanCount={OVERSCAN_COUNT}
        rowComponent={Row}
        rowProps={{}}
      />
    </div>
  );
});

export default VirtualizedTable;
