const statusConfig = {
  online: { bg: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', dot: 'bg-emerald-500' },
  offline: { bg: 'bg-gray-100 text-gray-600 ring-gray-500/20', dot: 'bg-gray-400' },
  pending: { bg: 'bg-amber-50 text-amber-700 ring-amber-600/20', dot: 'bg-amber-500' },
  active: { bg: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20', dot: 'bg-emerald-500' },
  resolved: { bg: 'bg-blue-50 text-blue-700 ring-blue-600/20', dot: 'bg-blue-500' },
  acknowledged: { bg: 'bg-amber-50 text-amber-700 ring-amber-600/20', dot: 'bg-amber-500' },
  open: { bg: 'bg-red-50 text-red-700 ring-red-600/20', dot: 'bg-red-500' },
  critical: { bg: 'bg-red-50 text-red-700 ring-red-600/20', dot: 'bg-red-500' },
  warning: { bg: 'bg-amber-50 text-amber-700 ring-amber-600/20', dot: 'bg-amber-500' },
  info: { bg: 'bg-blue-50 text-blue-700 ring-blue-600/20', dot: 'bg-blue-500' },
};

export function StatusBadge({ status, label, dot = true }) {
  const config = statusConfig[status?.toLowerCase()] || statusConfig.pending;
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ${config.bg}`}>
      {dot && <span className={`w-1.5 h-1.5 rounded-full ${config.dot}`} />}
      {label || status}
    </span>
  );
}

export function SeverityBadge({ severity }) {
  const colors = {
    critical: 'bg-red-100 text-red-800',
    warning: 'bg-amber-100 text-amber-800',
    info: 'bg-blue-100 text-blue-800',
  };
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[severity] || colors.info}`}>
      {severity}
    </span>
  );
}