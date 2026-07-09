export function HealthGauge({ value, label, subtitle, size = 'md', format = 'percentage' }) {
  const num = parseFloat(value) || 0;
  const displayVal = format === 'percentage' ? `${Math.round(num)}%` : num;
  const getColor = (v) => {
    if (v >= 90) return { stroke: '#EF4444', bg: '#FEE2E2' };
    if (v >= 70) return { stroke: '#F59E0B', bg: '#FEF3C7' };
    return { stroke: '#10B981', bg: '#D1FAE5' };
  };
  const colors = getColor(num);
  const dimensions = size === 'lg' ? 120 : 80;
  const strokeWidth = size === 'lg' ? 10 : 8;
  const radius = (dimensions - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference - (num / 100) * circumference;

  return (
    <div className="flex flex-col items-center">
      <svg width={dimensions} height={dimensions} className="transform -rotate-90">
        <circle cx={dimensions / 2} cy={dimensions / 2} r={radius} fill="none" stroke="#E5E7EB" strokeWidth={strokeWidth} />
        <circle cx={dimensions / 2} cy={dimensions / 2} r={radius} fill="none" stroke={colors.stroke} strokeWidth={strokeWidth} strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={offset} />
      </svg>
      <span className="text-xs font-semibold mt-1" style={{ color: colors.stroke }}>{displayVal}</span>
      {label && <span className="text-xs text-gray-500 mt-0.5">{label}</span>}
      {subtitle && <span className="text-[10px] text-gray-400">{subtitle}</span>}
    </div>
  );
}