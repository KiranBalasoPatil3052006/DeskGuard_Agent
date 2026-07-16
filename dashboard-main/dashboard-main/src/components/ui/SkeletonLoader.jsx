import React from 'react';

const SkeletonBar = ({ width = '100%', height = '16px', className = '' }) => (
  <div className={`skeleton-bar ${className}`} style={{ width, height, background: 'linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%)', backgroundSize: '200% 100%', borderRadius: '4px', animation: 'shimmer 1.5s infinite' }} />
);

const SkeletonCard = ({ lines = 3 }) => (
  <div className="card h-100 p-4" style={{ borderRadius: '16px' }}>
    <SkeletonBar width="40%" height="20px" className="mb-4" />
    {Array.from({ length: lines }).map((_, i) => (
      <SkeletonBar key={i} width={`${70 + Math.random() * 30}%`} height="14px" className="mb-2" />
    ))}
  </div>
);

const SkeletonTable = ({ rows = 5, cols = 4 }) => (
  <div className="card" style={{ borderRadius: '16px' }}>
    <div className="card-body p-4">
      {Array.from({ length: rows }).map((_, r) => (
        <div key={r} className="d-flex gap-3 mb-3">
          {Array.from({ length: cols }).map((_, c) => (
            <SkeletonBar key={c} width={`${80 / cols}%`} height="16px" />
          ))}
        </div>
      ))}
    </div>
  </div>
);

const SkeletonList = ({ items = 4 }) => (
  <div className="d-flex flex-column gap-3">
    {Array.from({ length: items }).map((_, i) => (
      <div key={i} className="d-flex align-items-center gap-3 p-3 border rounded" style={{ borderRadius: '12px' }}>
        <SkeletonBar width="40px" height="40px" className="rounded-circle flex-shrink-0" />
        <div className="flex-grow-1">
          <SkeletonBar width="60%" height="16px" className="mb-2" />
          <SkeletonBar width="40%" height="12px" />
        </div>
      </div>
    ))}
  </div>
);

export { SkeletonBar, SkeletonCard, SkeletonTable, SkeletonList };
export default SkeletonCard;
