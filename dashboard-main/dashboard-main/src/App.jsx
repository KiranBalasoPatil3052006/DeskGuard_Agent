import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';

// Auth Pages
import Login from './pages/auth/Login';
import Register from './pages/auth/Register';
import ForgotPassword from './pages/auth/ForgotPassword';

// Layout
import MainLayout from './layouts/MainLayout';

// Dashboard & Machines
import Dashboard from './pages/dashboard/Dashboard';
import MachinesList from './pages/machines/MachinesList';
import MachineDetails from './pages/machines/MachineDetails';
import LiveMonitoring from './pages/monitoring/LiveMonitoring';

// Alerts & Reports
import AlertsList from './pages/alerts/AlertsList';
import ReportsList from './pages/reports/ReportsList';

// Settings & Profile
import Settings from './pages/settings/Settings';
import UserProfile from './pages/settings/UserProfile';

// UI Showcase
import ComponentShowcase from './pages/docs/ComponentShowcase';

// Guards
import ProtectedRoute from './components/auth/ProtectedRoute';

function App() {
  return (
    <AuthProvider>
      <Routes>
        {/* Auth Routes */}
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/forgot-password" element={<ForgotPassword />} />

        {/* Authenticated Routes wrapped in MainLayout */}
        <Route element={<ProtectedRoute />}>
          <Route element={<MainLayout />}>
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/machines" element={<MachinesList />} />
            <Route path="/machines/:id" element={<MachineDetails />} />
            <Route path="/monitoring" element={<LiveMonitoring />} />
            <Route path="/reports" element={<ReportsList />} />
            <Route path="/alerts" element={<AlertsList />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="/profile" element={<UserProfile />} />
            <Route path="/components" element={<ComponentShowcase />} />
          </Route>
        </Route>

        {/* Default Redirect */}
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </AuthProvider>
  );
}

export default App;
