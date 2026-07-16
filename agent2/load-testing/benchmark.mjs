// Simple Node.js benchmark script - no dependencies needed (uses built-in fetch)
// Run: node benchmark.mjs
// Requires Node.js 18+ (for fetch API)

const BASE_URL = 'https://deskguardbackend-production.up.railway.app';
const TOKEN = process.env.TOKEN || 'YOUR_JWT_TOKEN_HERE';
const ITERATIONS = 50;
const CONCURRENCY = 5;

const ENDPOINTS = [
  { name: 'Dashboard', path: '/api/dashboard/company/1', expected: 300, priority: 'target' },
  { name: 'Machine List', path: '/api/machines?page=1&per_page=10', expected: 200, priority: 'target' },
  { name: 'Machine Detail', path: '/api/machines/1', expected: 500, priority: 'target' },
  { name: 'Alerts (critical)', path: '/api/alerts?page=1&per_page=20&severity=critical', expected: 300, priority: 'target' },
  { name: 'Processes (paginated)', path: '/api/machines/1/processes?page=1&per_page=50', expected: 200, priority: 'target' },
  { name: 'Services', path: '/api/machines/1/services', expected: 200, priority: 'target' },
  { name: 'Startup Programs', path: '/api/machines/1/startup-programs', expected: 200, priority: 'optional' },
  { name: 'Event Logs', path: '/api/machines/1/event-logs', expected: 200, priority: 'optional' },
  { name: 'Network', path: '/api/machines/1/network', expected: 300, priority: 'optional' },
];

async function benchmark() {
  console.log(`\n=== DeskGuard API Benchmark ===`);
  console.log(`Target: ${BASE_URL}`);
  console.log(`Iterations per endpoint: ${ITERATIONS}`);
  console.log(`Concurrency: ${CONCURRENCY}\n`);

  for (const ep of ENDPOINTS) {
    const times = [];
    const errors = [];
    const statuses = new Map();
    let successCount = 0;

    console.log(`\n[${ep.priority.toUpperCase()}] ${ep.name}`);
    console.log(`  GET ${ep.path}`);

    const startAll = performance.now();

    const promises = Array.from({ length: ITERATIONS }, async (_, i) => {
      const start = performance.now();
      try {
        const res = await fetch(`${BASE_URL}${ep.path}`, {
          headers: {
            'Authorization': `Bearer ${TOKEN}`,
            'Content-Type': 'application/json',
          },
          signal: AbortSignal.timeout(15000),
        });
        const elapsed = performance.now() - start;
        times.push(elapsed);
        statuses.set(res.status, (statuses.get(res.status) || 0) + 1);
        if (res.ok) successCount++;
      } catch (err) {
        elapsed = performance.now() - start;
        times.push(elapsed);
        errors.push(err.message);
      }
    });

    await Promise.all(promises);
    const totalDuration = performance.now() - startAll;

    if (times.length > 0) {
      times.sort((a, b) => a - b);
      const avg = times.reduce((s, t) => s + t, 0) / times.length;
      const p50 = times[Math.floor(times.length * 0.5)];
      const p95 = times[Math.floor(times.length * 0.95)];
      const p99 = times[Math.floor(times.length * 0.99)];
      const min = times[0];
      const max = times[times.length - 1];

      console.log(`  Results (${times.length} requests):`);
      console.log(`    Avg: ${avg.toFixed(1)}ms  P50: ${p50.toFixed(1)}ms  P95: ${p95.toFixed(1)}ms  P99: ${p99.toFixed(1)}ms`);
      console.log(`    Min: ${min.toFixed(1)}ms  Max: ${max.toFixed(1)}ms  Total: ${totalDuration.toFixed(0)}ms`);
      console.log(`    Success: ${successCount}/${ITERATIONS} (${(successCount/ITERATIONS*100).toFixed(0)}%)`);
      console.log(`    Statuses: ${[...statuses.entries()].map(([k, v]) => `${k}=${v}`).join(', ')}`);

      const target = ep.expected;
      const meetsTarget = avg <= target;
      const meetsP95 = p95 <= target * 1.5;
      console.log(`    Target: ${target}ms avg → ${meetsTarget ? '✅ PASS' : '❌ FAIL'}  (P95 < ${target*1.5}ms → ${meetsP95 ? '✅ PASS' : '❌ FAIL'})`);
    } else {
      console.log('  No results collected');
    }
  }

  console.log('\n=== Benchmark Complete ===\n');
}

benchmark().catch(console.error);
