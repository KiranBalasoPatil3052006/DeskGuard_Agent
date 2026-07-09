/**
 * Dashboard Page
 *
 * Main landing page after login. Displays:
 * - Summary cards (total, online, offline machines + critical alerts)
 * - System health overview
 * - Performance charts (CPU, RAM, Alert trends)
 * - Machine quick list
 * - Recent alerts
 *
 * All data is fetched from Backend APIs. No hardcoded data.
 * Implements loading, error, and empty states.
 * Auto-refreshes every 60 seconds.
 */
import React, { useState, useEffect, useCallback } from 'react';
import { getCompanyDashboard } from '../../services/dashboard';
import { getMachines } from '../../services/machines';
import { getAlerts } from '../../services/alerts';

import SystemHealthOverview from '../../components/dashboard/SystemHealthOverview';
import QuickActions from '../../components/dashboard/QuickActions';
import SummaryCards from '../../components/dashboard/SummaryCards';
import PerformanceCharts from '../../components/dashboard/PerformanceCharts';
import MachineTable from '../../components/dashboard/MachineTable';
import RecentAlerts from '../../components/dashboard/RecentAlerts';

const AUTO_REFRESH_INTERVAL_MS = 60000; // 60 seconds

const Dashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [machines, setMachines] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  /**
   * Fetch all dashboard data from Backend APIs.
   * Fetches company dashboard, machines list, and recent alerts in parallel.
   */
  const fetchData = useCallback(async (showLoading = true) => {
    if (showLoading) setLoading(true);
    setError(null);
    try {
      const [dashRes, machinesRes, alertsRes] = await Promise.all([
        getCompanyDashboard(),
        getMachines({ per_page: 10 }),
        getAlerts({ per_page: 5 }),
      ]);

      // Dashboard API returns { success: true, data: { cards, cpu_chart, ram_chart, alert_chart } }
      // The axios interceptor strips the outer response, so dashRes IS the response.data
      const dashPayload = dashRes?.data || dashRes || {};
      setDashboardData(dashPayload);

      // Machines — handle both paginated ({ data: [...] }) and flat array responses
      const machineList = machinesRes?.data?.data || machinesRes?.data || machinesRes || [];
      setMachines(Array.isArray(machineList) ? machineList : []);

      // Alerts — same handling
      const alertList = alertsRes?.data?.data || alertsRes?.data || alertsRes || [];
      setAlerts(Array.isArray(alertList) ? alertList : []);
    } catch (err) {
      console.error('Dashboard fetch error:', err);
      setError('Failed to load dashboard data. Please try again.');
    } finally {
      setLoading(false);
    }
  }, []);

  // Initial fetch + auto-refresh
  useEffect(() => {
    fetchData();

    const interval = setInterval(() => {
      fetchData(false); // Silent refresh (no loading spinner)
    }, AUTO_REFRESH_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [fetchData]);

  // ---------- Loading State ----------
  if (loading) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
        <div className="text-center">
          <div className="spinner-border text-primary mb-3" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <p className="text-muted">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  // ---------- Error State ----------
  if (error) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
        <div className="text-center">
          <div className="mb-3" style={{ fontSize: '48px' }}>⚠️</div>
          <h5 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>Something went wrong</h5>
          <p className="text-muted mb-3">{error}</p>
          <button className="btn btn-primary" onClick={() => fetchData()}>
            Retry
          </button>
        </div>
      </div>
    );
  }

  // ---------- Main Dashboard ----------
  return (
    <div className="container-fluid p-0">
      <div className="row g-4 mb-4">
        <div className="col-12 col-xl-3 d-flex flex-column gap-4">
          <SystemHealthOverview data={dashboardData} />
          <QuickActions />
        </div>
        <div className="col-12 col-xl-9 overflow-hidden">
          <SummaryCards data={dashboardData} />
        </div>
      </div>

      <div className="row g-4 mb-4">
        <div className="col-12">
          <PerformanceCharts data={dashboardData} />
        </div>
      </div>

      <div className="row g-4 mb-4">
        <div className="col-12 col-xl-8">
          {machines.length > 0 ? (
            <MachineTable machines={machines} />
          ) : (
            <div className="card p-5 text-center" style={{ borderRadius: '16px' }}>
              <div style={{ fontSize: '48px', marginBottom: '12px' }}>🖥️</div>
              <h6 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>No Machines Found</h6>
              <p className="text-muted mb-0">
                No machines are registered yet. Install the DeskGuard Agent on employee computers to start monitoring.
              </p>
            </div>
          )}
        </div>
        <div className="col-12 col-xl-4">
          {alerts.length > 0 ? (
            <RecentAlerts alerts={alerts} />
          ) : (
            <div className="card p-4 text-center" style={{ borderRadius: '16px' }}>
              <div style={{ fontSize: '36px', marginBottom: '8px' }}>✅</div>
              <h6 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>All Clear</h6>
              <p className="text-muted small mb-0">No recent alerts</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
