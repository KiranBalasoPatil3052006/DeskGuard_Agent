import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE_URL = 'https://deskguardbackend-production.up.railway.app';
const AUTH_TOKEN = __ENV.TOKEN || 'YOUR_JWT_TOKEN_HERE';

// Custom metrics
const dashboardTrend = new Trend('dashboard_duration');
const machineListTrend = new Trend('machine_list_duration');
const machineDetailTrend = new Trend('machine_detail_duration');
const alertsTrend = new Trend('alerts_duration');
const processesTrend = new Trend('processes_duration');
const servicesTrend = new Trend('services_duration');
const errorRate = new Rate('error_rate');

const params = {
  headers: {
    'Authorization': `Bearer ${AUTH_TOKEN}`,
    'Content-Type': 'application/json',
  },
  timeout: '30s',
};

export const options = {
  stages: [
    { duration: '30s', target: 5 },   // Ramp up to 5 VUs
    { duration: '1m', target: 10 },    // Ramp to 10 VUs
    { duration: '1m', target: 10 },    // Stay at 10 VUs
    { duration: '30s', target: 0 },    // Ramp down
  ],
  thresholds: {
    dashboard_duration: ['p(95)<500', 'avg<300'],
    machine_list_duration: ['p(95)<200', 'avg<150'],
    machine_detail_duration: ['p(95)<500', 'avg<300'],
    alerts_duration: ['p(95)<300', 'avg<200'],
    processes_duration: ['p(95)<200', 'avg<150'],
    services_duration: ['p(95)<200', 'avg<150'],
    error_rate: ['rate<0.05'],
  },
};

const companyId = 1; // Adjust to match actual test data
const machineId = 1; // Adjust to match actual test data

export default function () {
  group('Dashboard', function () {
    const res = http.get(`${BASE_URL}/api/dashboard/company/${companyId}`, params);
    dashboardTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'dashboard status 200': (r) => r.status === 200,
      'dashboard < 500ms': (r) => r.timings.duration < 500,
    });
  });

  sleep(0.5);

  group('Machine List', function () {
    const res = http.get(`${BASE_URL}/api/machines?page=1&per_page=10`, params);
    machineListTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'machine list status 200': (r) => r.status === 200,
      'machine list < 200ms': (r) => r.timings.duration < 200,
    });
  });

  sleep(0.5);

  group('Machine Detail', function () {
    const res = http.get(`${BASE_URL}/api/machines/${machineId}`, params);
    machineDetailTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'machine detail status 200': (r) => r.status === 200,
      'machine detail < 300ms': (r) => r.timings.duration < 300,
    });
  });

  sleep(0.5);

  group('Alerts', function () {
    const res = http.get(`${BASE_URL}/api/alerts?page=1&per_page=20&severity=critical`, params);
    alertsTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'alerts status 200': (r) => r.status === 200,
      'alerts < 300ms': (r) => r.timings.duration < 300,
    });
  });

  sleep(0.5);

  group('Machine Processes', function () {
    const res = http.get(`${BASE_URL}/api/machines/${machineId}/processes?page=1&per_page=50`, params);
    processesTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'processes status 200': (r) => r.status === 200,
      'processes < 200ms': (r) => r.timings.duration < 200,
    });
  });

  sleep(0.5);

  group('Machine Services', function () {
    const res = http.get(`${BASE_URL}/api/machines/${machineId}/services`, params);
    servicesTrend.add(res.timings.duration);
    errorRate.add(res.status !== 200);
    check(res, {
      'services status 200': (r) => r.status === 200,
      'services < 200ms': (r) => r.timings.duration < 200,
    });
  });

  sleep(1);
}
