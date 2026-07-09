import { useState, useEffect, useCallback, useRef } from 'react';

export function useApi(asyncFn, immediate = true) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(immediate);
  const [error, setError] = useState(null);
  const mountedRef = useRef(true);

  const execute = useCallback(async (...args) => {
    setLoading(true);
    setError(null);
    try {
      const result = await asyncFn(...args);
      if (mountedRef.current) {
        setData(result?.data || result);
        setLoading(false);
      }
      return result;
    } catch (err) {
      if (mountedRef.current) {
        setError(err.message || 'Something went wrong');
        setLoading(false);
      }
      throw err;
    }
  }, [asyncFn]);

  useEffect(() => {
    mountedRef.current = true;
    if (immediate) execute();
    return () => { mountedRef.current = false; };
  }, [immediate, execute]);

  return { data, loading, error, execute, setData };
}

export function usePagination(fn, defaultParams = {}) {
  const [params, setParams] = useState({ page: 1, per_page: 20, ...defaultParams });
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetch = useCallback(async (overrides = {}) => {
    setLoading(true);
    setError(null);
    try {
      const merged = { ...params, ...overrides };
      const res = await fn(merged);
      setData(res?.data || res);
      setLoading(false);
      return res;
    } catch (err) {
      setError(err.message || 'Failed to load data');
      setLoading(false);
    }
  }, [fn, params]);

  useEffect(() => { fetch(); }, [fetch]);

  const updateParams = useCallback((updates) => {
    setParams(prev => ({ ...prev, ...updates }));
  }, []);

  const pagination = data?.pagination || data?.meta || {};
  const currentPage = pagination.current_page || pagination.page || params.page;
  const lastPage = pagination.last_page || pagination.total_pages || 1;
  const total = pagination.total || 0;

  return {
    data: data?.data || (Array.isArray(data) ? data : []),
    loading,
    error,
    currentPage,
    lastPage,
    total,
    onPageChange: (page) => updateParams({ page }),
    updateParams,
    refresh: () => fetch(),
  };
}