import { useEffect, useState } from 'react';
import { getCompanyDashboard, getCpuTrend, getRamTrend } from '../../services/dashboard';
import { getMachines } from '../../services/machines';
import { getCriticalAlerts } from '../../services/alerts';
import { PageLoading } from '../../components/ui/LoadingState';
import { ErrorState } from '../../components/ui/ErrorState';
import { EmptyState } from '../../components/ui/EmptyState';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { FiMonitor, FiAlertTriangle, FiCheckCircle, FiXCircle } from 'react-icons/fi';
import { useNavigate } from 'react-router-dom';
import { LineChart, Line, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';

function SummaryCard({ icon: Icon, label, value, color, bg }) {
  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
      <div className="flex items-center gap-4">
        <div className={`p-3 rounded-lg ${bg}`}><Icon className={`w-6 h-6 ${color}`} /></div>
        <div>
          <p className="text-sm text-gray-500">{label}</p>
          <p className="text-2xl font-bold text-gray-900">{value ?? '—'}</p>
        </div>
      </div>
    </div>
  );
}

export default function Dashboard() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [summaryCards, setSummaryCards] = useState({});
  const [cpuData, setCpuData] = useState([]);
  const [ramData, setRamData] = useState([]);
  const [machines, setMachines] = useState([]);
  const navigate = useNavigate();

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const [dashRes, machRes, cpuRes, ramRes] = await Promise.all([
        getCompanyDashboard().catch(() => ({ data: {} })),
        getMachines({ per_page: 5 }).catch(() => ({ data: { data: [] } })),
        getCpuTrend(24).catch(() => ({ data: [] })),
        getRamTrend(24).catch(() => ({ data: [] })),
      ]);

      const d = dashRes?.data || dashRes || {};
      setSummaryCards({
        total: d?.total_machines ?? d?.total ?? d?.cards?.total_machines ?? 0,
        online: d?.online_machines ?? d?.online ?? d?.cards?.online_machines ?? 0,
        offline: d?.offline_machines ?? d?.offline ?? d?.cards?.offline_machines ?? 0,
        critical: d?.critical_alerts ?? d?.critical ?? d?.cards?.critical_alerts ?? 0,
      });
      setMachines(machRes?.data?.data || machRes?.data || []);
      setCpuData(Array.isArray(cpuRes?.data) ? cpuRes.data : (cpuRes?.data || []));
      setRamData(Array.isArray(ramRes?.data) ? ramRes.data : (ramRes?.data || []));
    } catch (err) {
      setError(err.message || 'Failed to load dashboard');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchAll(); }, []);

  if (loading) return <PageLoading />;
  if (error) return <ErrorState message={error} onRetry={fetchAll} />;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-sm text-gray-500 mt-1">Overview of your monitored systems</p>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <SummaryCard icon={FiMonitor} label="Total Machines" value={summaryCards.total} color="text-indigo-600" bg="bg-indigo-50" />
        <SummaryCard icon={FiCheckCircle} label="Online" value={summaryCards.online} color="text-emerald-600" bg="bg-emerald-50" />
        <SummaryCard icon={FiXCircle} label="Offline" value={summaryCards.offline} color="text-gray-600" bg="bg-gray-50" />
        <SummaryCard icon={FiAlertTriangle} label="Critical Alerts" value={summaryCards.critical} color="text-red-600" bg="bg-red-50" />
      </div>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 className="text-sm font-semibold text-gray-700 mb-4">CPU Usage (24h)</h3>
          {cpuData.length > 0 ? (
            <ResponsiveContainer width="100%" height={220}>
              <LineChart data={cpuData}>
                <XAxis dataKey={(v) => v?.collected_at?.substring(11, 16) || ''} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                <YAxis domain={[0, 100]} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                <Tooltip />
                <Line type="monotone" dataKey="cpu_percentage" stroke="#6366F1" strokeWidth={2} dot={false} />
              </LineChart>
            </ResponsiveContainer>
          ) : <EmptyState title="No CPU data" description="Waiting for agent data." />}
        </div>
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <h3 className="text-sm font-semibold text-gray-700 mb-4">RAM Usage (24h)</h3>
          {ramData.length > 0 ? (
            <ResponsiveContainer width="100%" height={220}>
              <LineChart data={ramData}>
                <XAxis dataKey={(e) => e.collected_at?.substring(0, 16) || ''} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                <YAxis domain={[0, 100]} tick={{ fontSize: 10 }} axisLine={false} tickLine={false} />
                <Tooltip />
                <Line type="monotone" dataKey="ram_percentage" stroke="#8B5CF6" strokeWidth={2} dot={false} />
              </LineChart>
            </ResponsiveContainer>
          ) : <EmptyState title="No RAM data" description="Waiting for agent data." />}
        </div>
      </div>
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="px-5 py-4 border-b border-gray-100"><h3 className="text-sm font-semibold text-gray-700">Recent Machines</h3></div>
        {machines.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <th className="px-5 py-3">Computer Name</th>
                  <th className="px-5 py-3">Mobile</th>
                  <th className="px-5 py-3">Status</th>
                  <th className="px-5 py-3">Last Seen</th>
                  <th className="px-5 py-3">Alerts</th>
                  <th className="px-5 py-3" />
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {machines.map(m => (
                  <tr key={m.id} className="hover:bg-gray-50 transition-colors cursor-pointer" onClick={() => navigate(`/machines/${m.id}`)}>
                    <td className="px-5 py-3 font-medium text-gray-900">{m.hostname || m.device_name || m.machine_uid}</td>
                    <td className="px-5 py-3 text-gray-500">{m.employee_mobile_number || '—'}</td>
                    <td className="px-5 py-3"><StatusBadge status={m.is_online ? 'online' : 'offline'} /></td>
                    <td className="px-5 py-3 text-gray-500">{m.last_heartbeat_at ? new Date(m.last_heartbeat_at).toLocaleString() : '—'}</td>
                    <td className="px-5 py-3">{m.alerts_count ?? m.alerts ?? 0}</td>
                    <td className="px-5 py-3 text-right"><button onClick={(e) => { e.stopPropagation(); navigate(`/machines/${m.id}`); }} className="text-indigo-600 hover:text-indigo-800 text-xs font-medium">View</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : <EmptyState icon={FiMonitor} title="No machines registered" description="Machines will appear here once agents connect." />}
      </div>
    </div>
  );
}