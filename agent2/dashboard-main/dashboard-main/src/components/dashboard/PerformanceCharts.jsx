import { useRef, useEffect, useState } from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { useQuery } from '@tanstack/react-query';
import { getCpuTrend, getRamTrend } from '../../services/dashboard';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const TIMEFRAME_MAP = { '1D': 24, '1W': 168, '1M': 720, '6M': 4320, '1Y': 8760 };

const PerformanceCharts = ({ data }) => {
  const chartRef = useRef(null);
  const [chartData, setChartData] = useState(null);
  const [timeframe, setTimeframe] = useState('6M');
  const [chartMetric, setChartMetric] = useState('cpu');

  const hours = TIMEFRAME_MAP[timeframe] || 4320;

  const { data: cpuTrend } = useQuery({
    queryKey: ['cpuTrend', hours],
    queryFn: () => getCpuTrend(hours),
    staleTime: 60000,
    enabled: chartMetric === 'cpu',
  });

  const { data: ramTrend } = useQuery({
    queryKey: ['ramTrend', hours],
    queryFn: () => getRamTrend(hours),
    staleTime: 60000,
    enabled: chartMetric === 'ram',
  });

  useEffect(() => {
    const chart = chartRef.current;
    if (!chart) return;

    const ctx = chart.ctx;
    const gradient = ctx.createLinearGradient(0, 0, 0, 350);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    const trendData = chartMetric === 'cpu' ? cpuTrend : ramTrend;
    const rawData = trendData?.data || data?.[chartMetric === 'cpu' ? 'cpu_chart' : 'ram_chart'] || null;

    let labels = [];
    let datasets = [];

    const colors = [
      '#6366F1',
      '#10B981',
      '#F59E0B',
      '#EF4444',
      '#8B5CF6',
      '#06B6D4',
      '#EC4899',
      '#84CC16',
    ];

    if (Array.isArray(rawData)) {
      labels = rawData.map(d => d.label || d.date || '');
      const dataPoints = rawData.map(d => d.value || d.usage || d.average || 0);
      datasets = [{
        fill: true,
        label: chartMetric === 'cpu' ? 'CPU Usage %' : 'RAM Usage %',
        data: dataPoints,
        borderColor: colors[0],
        backgroundColor: gradient,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 6,
        borderWidth: 2,
        pointBackgroundColor: colors[0],
        pointBorderColor: '#ffffff'
      }];
    } else if (rawData && rawData.labels && rawData.datasets) {
      labels = rawData.labels;
      datasets = rawData.datasets.map((ds, idx) => {
        const color = colors[idx % colors.length];
        return {
          fill: false,
          label: ds.label,
          data: ds.data,
          borderColor: color,
          backgroundColor: color,
          tension: 0.4,
          pointRadius: 0,
          pointHoverRadius: 6,
          borderWidth: 2,
          pointBackgroundColor: color,
          pointBorderColor: '#ffffff'
        };
      });

      if (datasets.length === 1) {
        datasets[0].fill = true;
        datasets[0].backgroundColor = gradient;
      }
    }

    setChartData({
      labels: labels,
      datasets: datasets
    });
  }, [timeframe, chartMetric, data, cpuTrend, ramTrend]);

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index',
        intersect: false,
        backgroundColor: 'rgba(255, 255, 255, 0.9)',
        titleColor: '#64748B',
        bodyColor: '#1E1B4B',
        borderColor: '#E2E8F0',
        borderWidth: 1,
        padding: 10,
        displayColors: false,
        callbacks: {
          label: (context) => `${chartMetric === 'cpu' ? 'CPU' : 'RAM'}: ${context.parsed.y}%`
        }
      }
    },
    scales: {
      x: {
        grid: { display: false, drawBorder: false },
        ticks: { color: '#666666', font: { size: 10 } }
      },
      y: {
        display: false,
        grid: { display: false },
        min: 0,
        max: 100
      }
    },
    interaction: {
      mode: 'nearest',
      axis: 'x',
      intersect: false
    }
  };

  return (
    <div className="card p-4" style={{ borderRadius: '16px' }}>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <span className="text-muted fw-semibold">System Performance</span>
        <div className="d-flex gap-2">
          <div className="pill-group me-2">
            {['cpu', 'ram'].map(m => (
              <button
                key={m}
                className={`pill-btn ${chartMetric === m ? 'active' : ''}`}
                onClick={() => setChartMetric(m)}
              >
                {m.toUpperCase()}
              </button>
            ))}
          </div>
          <div className="pill-group">
            {['1D', '1W', '1M', '6M', '1Y'].map(tf => (
              <button
                key={tf}
                className={`pill-btn ${timeframe === tf ? 'active' : ''}`}
                onClick={() => setTimeframe(tf)}
              >
                {tf}
              </button>
            ))}
          </div>
        </div>
      </div>
      <div style={{ height: '350px', width: '100%' }}>
        {chartData ? (
          <Line ref={chartRef} options={options} data={chartData} />
        ) : (
          <div className="d-flex justify-content-center align-items-center h-100 text-muted">
            No chart data available
          </div>
        )}
      </div>
    </div>
  );
};

export default PerformanceCharts;
