import { Suspense, lazy } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthContext';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: true,
      staleTime: 30000,
      refetchInterval: 30000,
    },
  },
});

// Auth Pages (eager — on initial render path)
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import ForgotPassword from './pages/auth/ForgotPassword';
import MainLayout from './layouts/MainLayout';
import ProtectedRoute from './components/auth/ProtectedRoute';
import ErrorBoundary from './components/ErrorBoundary';

// Lazy-loaded pages (code-split by webpack)
const Dashboard = lazy(() => import('./pages/dashboard/Dashboard'));
const MachinesList = lazy(() => import('./pages/machines/MachinesList'));
const MachineDetails = lazy(() => import('./pages/machines/MachineDetails'));
const AlertsList = lazy(() => import('./pages/alerts/AlertsList'));
const ReportsList = lazy(() => import('./pages/reports/ReportsList'));
const ChangesList = lazy(() => import('./pages/changes/ChangesList'));
const Settings = lazy(() => import('./pages/settings/Settings'));
const AlertThresholds = lazy(() => import('./pages/settings/AlertThresholds'));
const AccountsList = lazy(() => import('./pages/accounts/AccountsList'));
const AgentsList = lazy(() => import('./pages/agents/AgentsList'));
const AgentDetails = lazy(() => import('./pages/agents/AgentDetails'));
const ComponentShowcase = lazy(() => import('./pages/docs/ComponentShowcase'));

function App() {
  return (
    <QueryClientProvider client={queryClient}>
    <AuthProvider>
      <Routes>
        {/* Auth Routes */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />

        {/* Authenticated Routes wrapped in MainLayout */}
        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route path="/dashboard" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><Dashboard /></ErrorBoundary></Suspense>} />
            <Route path="/machines" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><MachinesList /></ErrorBoundary></Suspense>} />
            <Route path="/machines/:id" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><MachineDetails /></ErrorBoundary></Suspense>} />
            <Route path="/reports" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><ReportsList /></ErrorBoundary></Suspense>} />
            <Route path="/alerts" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><AlertsList /></ErrorBoundary></Suspense>} />
            <Route path="/changes" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><ChangesList /></ErrorBoundary></Suspense>} />
            <Route path="/settings" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><Settings /></ErrorBoundary></Suspense>} />
            <Route path="/settings/alert-thresholds" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><AlertThresholds /></ErrorBoundary></Suspense>} />
            <Route path="/accounts" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><AccountsList /></ErrorBoundary></Suspense>} />
            <Route path="/agents" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><AgentsList /></ErrorBoundary></Suspense>} />
            <Route path="/agents/:id" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><AgentDetails /></ErrorBoundary></Suspense>} />
            <Route path="/components" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ErrorBoundary><ComponentShowcase /></ErrorBoundary></Suspense>} />
          </Route>
        </Route>

        {/* Default Redirect */}
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </AuthProvider>
    </QueryClientProvider>
  );
}

export default App;
