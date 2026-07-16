import React, { memo } from 'react';
import { FaHdd } from 'react-icons/fa';

const RecentChanges = memo(({ changes }) => {
  const items = Array.isArray(changes) ? changes : [];

  return (
    <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">Hardware Changes</span>
        <span className="badge bg-danger-subtle text-danger-emphasis rounded-pill">{items.length}</span>
      </div>

      <div className="d-flex flex-column gap-3">
        {items.length > 0 ? (
          items.slice(0, 5).map((c, idx) => (
            <div key={c.id || idx} className="d-flex align-items-center gap-3 pb-3"
              style={{ borderBottom: idx < Math.min(items.length, 5) - 1 ? '1px solid var(--border-color)' : 'none' }}>
              <div className="fs-5 text-danger"><FaHdd /></div>
              <div className="flex-grow-1 min-w-0">
                <div className="fw-semibold text-truncate" style={{ fontSize: '0.9rem', color: 'var(--text-body)' }}>
                  {c.item_label || c.item_identifier || 'Hardware'}
                </div>
                <div className="text-muted small text-truncate">
                  {c.previous_value && c.new_value ? `${c.previous_value} → ${c.new_value}` : (c.description || 'Hardware change detected')}
                </div>
              </div>
              <div className="text-end flex-shrink-0">
                <span className={`badge rounded-pill ${c.severity === 'critical' ? 'bg-danger' : c.severity === 'important' ? 'bg-warning text-dark' : 'bg-info'}`}>
                  {c.severity || 'info'}
                </span>
              </div>
            </div>
          ))
        ) : (
          <div className="text-muted text-center py-4">No hardware changes detected</div>
        )}
      </div>
    </div>
  );
});

export default RecentChanges;
