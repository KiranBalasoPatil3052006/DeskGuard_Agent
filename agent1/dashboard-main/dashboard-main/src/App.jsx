import React, { Suspense, lazy } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from './context/AuthContext';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 30000,
    },
  },
});

// Auth Pages (eager — on initial render path)
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import ForgotPassword from './pages/auth/ForgotPassword';
import MainLayout from './layouts/MainLayout';
import ProtectedRoute from './components/auth/ProtectedRoute';

// Lazy-loaded pages (code-split by webpack)
const Dashboard = lazy(() => import('./pages/dashboard/Dashboard'));
const MachinesList = lazy(() => import('./pages/machines/MachinesList'));
const MachineDetails = lazy(() => import('./pages/machines/MachineDetails'));
const AlertsList = lazy(() => import('./pages/alerts/AlertsList'));
const ReportsList = lazy(() => import('./pages/reports/ReportsList'));
const ChangesList = lazy(() => import('./pages/changes/ChangesList'));
const Settings = lazy(() => import('./pages/settings/Settings'));
const UserProfile = lazy(() => import('./pages/settings/UserProfile'));
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
            <Route path="/dashboard" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><Dashboard /></Suspense>} />
            <Route path="/machines" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><MachinesList /></Suspense>} />
            <Route path="/machines/:id" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><MachineDetails /></Suspense>} />
            <Route path="/reports" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ReportsList /></Suspense>} />
            <Route path="/alerts" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><AlertsList /></Suspense>} />
            <Route path="/changes" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ChangesList /></Suspense>} />
            <Route path="/settings" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><Settings /></Suspense>} />
            <Route path="/profile" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><UserProfile /></Suspense>} />
            <Route path="/components" element={<Suspense fallback={<div className="text-center py-5"><div className="spinner-border" role="status" /></div>}><ComponentShowcase /></Suspense>} />
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
