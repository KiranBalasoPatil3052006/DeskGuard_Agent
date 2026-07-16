import { useEffect, useRef, useCallback } from 'react';
import * as signalR from '@microsoft/signalr';

export function useSignalR(hubUrl, callbacks = {}) {
  const connectionRef = useRef(null);
  const callbacksRef = useRef(callbacks);
  callbacksRef.current = callbacks;

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    const connection = new signalR.HubConnectionBuilder()
      .withUrl(hubUrl, {
        accessTokenFactory: () => token || '',
      })
      .withAutomaticReconnect([0, 2000, 5000, 10000, 30000])
      .configureLogging(signalR.LogLevel.Warning)
      .build();

    connectionRef.current = connection;

    if (callbacksRef.current.onAlertEvent) {
      connection.on('AlertEvent', callbacksRef.current.onAlertEvent);
    }
    if (callbacksRef.current.onMachineStatus) {
      connection.on('MachineStatus', callbacksRef.current.onMachineStatus);
    }

    connection.start()
      .then(() => {
        if (callbacksRef.current.onConnected) {
          callbacksRef.current.onConnected();
        }
      })
      .catch(err => console.error('SignalR connection failed:', err));

    connection.onreconnecting(() => {
      console.warn('SignalR reconnecting...');
    });

    connection.onreconnected(() => {
      console.info('SignalR reconnected');
    });

    connection.onclose(() => {
      console.warn('SignalR connection closed');
    });

    return () => {
      connection.stop();
    };
  }, [hubUrl]);

  const invoke = useCallback((method, ...args) => {
    if (connectionRef.current?.state === signalR.HubConnectionState.Connected) {
      return connectionRef.current.invoke(method, ...args);
    }
    return Promise.reject(new Error('Not connected'));
  }, []);

  return { invoke, connection: connectionRef };
}
