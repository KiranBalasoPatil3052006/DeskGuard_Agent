import React from 'react';
import { useDashboard, useMachines, useAlerts, useDashboardRecentChanges } from '../../hooks/useQueries';

import SystemHealthOverview from '../../components/dashboard/SystemHealthOverview';
import QuickActions from '../../components/dashboard/QuickActions';
import SummaryCards from '../../components/dashboard/SummaryCards';
import PerformanceCharts from '../../components/dashboard/PerformanceCharts';
import MachineTable from '../../components/dashboard/MachineTable';
import RecentAlerts from '../../components/dashboard/RecentAlerts';
import RecentChanges from '../../components/dashboard/RecentChanges';

/**
 * PERFORMANCE: Skeleton placeholder for sections that are still loading.
 * Shows a pulsing grey block that matches the expected content height.
 * This prevents the "blank page with spinner" pattern and gives users
 * immediate visual feedback that content is on its way.
 */
const SectionSkeleton = ({ height = '200px', className = '' }) => (
  <div
    className={`card border-0 ${className}`}
    style={{
      borderRadius: '16px',
      height,
      background: 'linear-gradient(110deg, var(--bg-card, #f0f0f0) 8%, var(--bg-input, #e8e8e8) 18%, var(--bg-card, #f0f0f0) 33%)',
      backgroundSize: '200% 100%',
      animation: 'shimmer 1.5s ease-in-out infinite',
    }}
  />
);

/**
 * PERFORMANCE: Skeleton for the card row (Summary Cards area).
 * Shows 4 pulsing card outlines matching the SummaryCards layout.
 */
const CardRowSkeleton = () => (
  <div className="row g-3">
    {[1, 2, 3, 4].map(i => (
      <div className="col-6 col-lg-3" key={i}>
        <SectionSkeleton height="100px" />
      </div>
    ))}
  </div>
);

/**
 * PERFORMANCE OPTIMIZED: Dashboard page with progressive rendering.
 *
 * Before: Blocked ALL rendering until 4 API calls completed (2-3s blank spinner).
 * After:  Each section renders independently as its data arrives.
 *
 * - SummaryCards + SystemHealthOverview appear first (fastest API)
 * - PerformanceCharts appear next
 * - MachineTable and Alerts render independently
 * - No "combined loading" gate — each section has its own skeleton
 */
const Dashboard = () => {
  const { data: dashboardData, isLoading: dashLoading, error: dashError } = useDashboard();
  const { data: machinesData, isLoading: machLoading } = useMachines({ per_page: 10 });
  const { data: alertsData, isLoading: alertLoading } = useAlerts({ per_page: 5 });
  const { data: changes, isLoading: changesLoading } = useDashboardRecentChanges(5);

  // PERFORMANCE: Removed combined loading gate.
  // Previously: const loading = dashLoading || machLoading || alertLoading || changesLoading;
  // This blocked the entire page until ALL 4 API calls completed.
  // Now each section renders independently with its own loading state.

  const machines = machinesData?.data || [];
  const alerts = alertsData?.data || [];

  if (dashError) {
    return (
      <div className="container-fluid p-0 d-flex justify-content-center align-items-center" style={{ minHeight: '400px' }}>
        <div className="text-center">
          <div className="mb-3" style={{ fontSize: '48px' }}>⚠️</div>
          <h5 className="fw-bold mb-2" style={{ color: 'var(--text-body)' }}>Something went wrong</h5>
          <p className="text-muted mb-3">{dashError.message || 'Failed to load dashboard data'}</p>
          <button className="btn btn-primary" onClick={() => window.location.reload()}>Retry</button>
        </div>
      </div>
    );
  }

  return (
    <div className="container-fluid p-0">
      {/* PERFORMANCE: Section 1 renders immediately when dashboardData arrives */}
      <div className="row g-4 mb-4">
        <div className="col-12 col-xl-3 d-flex flex-column gap-4">
          {dashLoading ? (
            <SectionSkeleton height="280px" />
          ) : (
            <>
              <SystemHealthOverview data={dashboardData} />
              <QuickActions />
            </>
          )}
        </div>
        <div className="col-12 col-xl-9 overflow-hidden">
          {dashLoading ? (
            <CardRowSkeleton />
          ) : (
            <SummaryCards data={dashboardData} />
          )}
        </div>
      </div>

      {/* PERFORMANCE: Section 2 renders independently */}
      <div className="row g-4 mb-4">
        <div className="col-12">
          {dashLoading ? (
            <SectionSkeleton height="300px" />
          ) : (
            <PerformanceCharts data={dashboardData} />
          )}
        </div>
      </div>

      {/* PERFORMANCE: Section 3 — machines and alerts render independently */}
      <div className="row g-4 mb-4">
        <div className="col-12 col-xl-8">
          {machLoading ? (
            <SectionSkeleton height="400px" />
          ) : machines.length > 0 ? (
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
        <div className="col-12 col-xl-4 d-flex flex-column gap-4">
          {alertLoading ? (
            <SectionSkeleton height="200px" />
          ) : alerts.length > 0 ? (
            <RecentAlerts alerts={alerts} />
          ) : (
            <div className="card p-4 text-center" style={{ borderRadius: '16px' }}>
              <div style={{ fontSize: '36px', marginBottom: '8px' }}>✅</div>
              <h6 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>All Clear</h6>
              <p className="text-muted small mb-0">No recent alerts</p>
            </div>
          )}
          {changesLoading ? (
            <SectionSkeleton height="200px" />
          ) : changes.length > 0 ? (
            <RecentChanges changes={changes} />
          ) : (
            <div className="card p-4 text-center" style={{ borderRadius: '16px' }}>
              <div style={{ fontSize: '36px', marginBottom: '8px' }}>🔄</div>
              <h6 className="fw-bold mb-1" style={{ color: 'var(--text-body)' }}>No Recent Changes</h6>
              <p className="text-muted small mb-0">No hardware changes detected</p>
            </div>
          )}
        </div>
      </div>

      {/* Shimmer animation keyframes */}
      <style>{`
        @keyframes shimmer {
          0% { background-position: 200% 0; }
          100% { background-position: -200% 0; }
        }
      `}</style>
    </div>
  );
};

export default Dashboard;
