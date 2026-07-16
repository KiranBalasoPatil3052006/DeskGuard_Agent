import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { loginUser, logoutUser } from '../services/auth';
import { setAuthToken } from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('auth_user');
    if (!stored) return null;
    try {
      return JSON.parse(stored);
    } catch (e) {
      localStorage.removeItem('auth_user');
      return null;
    }
  });
  const [token, setToken] = useState(() => localStorage.getItem('auth_token'));
  const [loading, setLoading] = useState(false);

  const login = useCallback(async (email, password) => {
    setLoading(true);
    try {
      const response = await loginUser(email, password);
      const { user: userData, token: authToken } = response.data;
      setToken(authToken);
      setUser(userData);
      setAuthToken(authToken);
      localStorage.setItem('auth_user', JSON.stringify(userData));
      return response;
    } finally {
      setLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await logoutUser();
    } catch {
    } finally {
      setToken(null);
      setUser(null);
      setAuthToken(null);
      localStorage.removeItem('auth_user');
    }
  }, []);

  useEffect(() => {
    if (!token) {
      setUser(null);
    }
  }, [token]);

  return (
    <AuthContext.Provider value={{ user, token, loading, login, logout, isAuthenticated: !!token }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;
