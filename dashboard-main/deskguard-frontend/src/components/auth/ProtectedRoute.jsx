import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { PageLoading } from '../ui/LoadingState';

export default function ProtectedRoute() {
  const { isAuthenticated, loading } = useAuth();
  if (loading) return <PageLoading />;
  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />;
}