import React, { createContext, useContext, useState, useCallback } from 'react';
import { FaInfoCircle, FaCheckCircle, FaExclamationTriangle, FaTimesCircle, FaTimes } from 'react-icons/fa';

const ToastContext = createContext(null);

export const useToast = () => useContext(ToastContext);

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);

  const showToast = useCallback((message, type = 'info', duration = 3000) => {
    const id = Date.now();
    setToasts((prev) => [...prev, { id, message, type }]);
    
    setTimeout(() => {
      removeToast(id);
    }, duration);
  }, []);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
  }, []);

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <div 
        className="toast-container position-fixed bottom-0 end-0 p-3" 
        style={{ zIndex: 9999 }}
      >
        {toasts.map((toast) => (
          <div 
            key={toast.id} 
            className={`toast show align-items-center text-white bg-${
              toast.type === 'success' ? 'success' : 
              toast.type === 'error' ? 'danger' : 
              toast.type === 'warning' ? 'warning text-dark' : 'primary'
            } border-0 mb-2`}
            role="alert" aria-live="assertive" aria-atomic="true"
          >
            <div className="d-flex">
              <div className="toast-body d-flex align-items-center">
                <span className="me-2 fs-5">
                  {toast.type === 'success' && <FaCheckCircle />}
                  {toast.type === 'error' && <FaTimesCircle />}
                  {toast.type === 'warning' && <FaExclamationTriangle />}
                  {toast.type === 'info' && <FaInfoCircle />}
                </span>
                {toast.message}
              </div>
              <button 
                type="button" 
                className={`btn-close me-2 m-auto ${toast.type !== 'warning' ? 'btn-close-white' : ''}`}
                onClick={() => removeToast(toast.id)}
              ></button>
            </div>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
};
