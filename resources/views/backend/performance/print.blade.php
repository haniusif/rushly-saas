<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Performance Dashboard</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 11px; }
        h1   { font-size: 18px; margin: 0 0 4px 0; }
        h2   { font-size: 13px; margin: 16px 0 6px 0; color: #6b7280; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; font-size: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; font-weight: 600; }
        .grid { display: table; width: 100%; border-spacing: 8px 0; margin-bottom: 8px; }
        .col  { display: table-cell; width: 25%; border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        .col .l { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.06em; }
        .col .v { font-size: 16px; font-weight: 700; margin-top: 2px; }
        .col .s { font-size: 9px; color: #6b7280; margin-top: 1px; }
        .num  { text-align: right; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 9999px; font-size: 9px; font-weight: 600; }
        .b-good { background: #d1fae5; color: #047857; }
        .b-very { background: #e0f2fe; color: #0369a1; }
        .b-ok   { background: #e0e7ff; color: #3730a3; }
        .b-warn { background: #fef3c7; color: #92400e; }
        .b-bad  { background: #fee2e2; color: #991b1b; }
        .footer { color: #9ca3af; font-size: 9px; margin-top: 14px; text-align: right; }
    </style>
</head>
<body>
    @php
        $pct = fn ($v) => $v === null ? '—' : number_format($v * 100, 1) . '%';
        $bandClass = fn ($band) => [
            'excellent' => 'b-good', 'very_good' => 'b-very', 'good' => 'b-ok',
            'needs_improvement' => 'b-warn', 'critical' => 'b-bad',
        ][$band] ?? 'b-bad';
        $bandLabel = fn ($band) => [
            'excellent' => 'Excellent', 'very_good' => 'Very Good', 'good' => 'Good',
            'needs_improvement' => 'Needs Improvement', 'critical' => 'Critical',
        ][$band] ?? '—';
        $o = $kpi['orders']; $f = $kpi['financial']; $a = $kpi['activity']; $s = $kpi['service'];
    @endphp

    <h1>Performance Dashboard</h1>
    <div class="meta">
        Range: <strong>{{ $filters->from->toDateString() }}</strong> → <strong>{{ $filters->to->toDateString() }}</strong>
        ({{ $filters->days() }} days) ·
        Generated: {{ now()->toDateTimeString() }} ·
        Company: {{ settings()->name ?? '' }}
    </div>

    <h2>Orders</h2>
    <div class="grid">
        <div class="col"><div class="l">Total</div><div class="v">{{ number_format($o['total']) }}</div><div class="s">vs prev {{ number_format($o['previous_total']) }}</div></div>
        <div class="col"><div class="l">Completed</div><div class="v">{{ number_format($o['completed']) }}</div><div class="s">{{ $pct($o['completion_rate']) }}</div></div>
        <div class="col"><div class="l">Pending</div><div class="v">{{ number_format($o['pending']) }}</div></div>
        <div class="col"><div class="l">Cancelled</div><div class="v">{{ number_format($o['cancelled']) }}</div></div>
    </div>

    <h2>Financial ({{ $f['currency'] }})</h2>
    <div class="grid">
        <div class="col"><div class="l">Revenue</div><div class="v">{{ number_format($f['revenue'], 2) }}</div></div>
        <div class="col"><div class="l">Expenses</div><div class="v">{{ number_format($f['expenses'], 2) }}</div></div>
        <div class="col"><div class="l">Profit</div><div class="v">{{ number_format($f['profit'], 2) }}</div></div>
        <div class="col"><div class="l">Growth</div><div class="v">{{ $pct($o['growth_rate']) }}</div></div>
    </div>

    <h2>Activity</h2>
    <div class="grid">
        <div class="col"><div class="l">Active Drivers</div><div class="v">{{ $a['active_drivers'] }}</div><div class="s">of {{ $a['total_drivers'] }}</div></div>
        <div class="col"><div class="l">Active Customers</div><div class="v">{{ $a['active_customers'] }}</div><div class="s">of {{ $a['total_customers'] }}</div></div>
        <div class="col"><div class="l">Active Companies</div><div class="v">{{ $a['active_companies'] }}</div><div class="s">3PL operating co.</div></div>
        <div class="col"><div class="l">Active Branches</div><div class="v">{{ $a['active_branches'] }}</div><div class="s">of {{ $a['total_branches'] }}</div></div>
    </div>

    <h2>Service Quality</h2>
    <div class="grid">
        <div class="col"><div class="l">Avg. Delivery</div><div class="v">{{ $s['avg_delivery_hours'] !== null ? $s['avg_delivery_hours'].' h' : '—' }}</div></div>
        <div class="col"><div class="l">On-time (proxy)</div><div class="v">{{ $pct($s['on_time_rate']) }}</div></div>
        <div class="col"><div class="l">SLA Compliance</div><div class="v">{{ $pct($s['sla_compliance']) }}</div><div class="s">{{ $s['abnormal_open'] }} open abnormal</div></div>
        <div class="col"><div class="l">Satisfaction (proxy)</div><div class="v">{{ $pct($s['satisfaction']) }}</div><div class="s">{{ $s['support_tickets'] }} tickets</div></div>
    </div>

    <h2>Driver Leaderboard</h2>
    @if (count($drivers['ranking']) === 0)
        <div class="muted">No driver activity in this range.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Driver</th>
                    <th class="num">Delivered</th><th class="num">Handled</th>
                    <th class="num">Completion</th><th class="num">On-time</th>
                    <th class="num">Revenue</th><th>Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($drivers['ranking'] as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="num">{{ number_format($r['delivered']) }}</td>
                        <td class="num">{{ number_format($r['handled']) }}</td>
                        <td class="num">{{ number_format($r['completion_rate'] * 100, 1) }}%</td>
                        <td class="num">{{ $r['on_time_rate'] !== null ? number_format($r['on_time_rate'] * 100, 1).'%' : '—' }}</td>
                        <td class="num">{{ number_format($r['revenue'], 2) }}</td>
                        <td>
                            <span class="badge {{ $bandClass($r['band']) }}">{{ $r['score'] }} · {{ $bandLabel($r['band']) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @isset($customers)
        @php $ck = $customers['kpi']; @endphp
        <h2>Customers</h2>
        <div class="grid">
            <div class="col"><div class="l">Total</div><div class="v">{{ number_format($ck['total_customers']) }}</div></div>
            <div class="col"><div class="l">Active</div><div class="v">{{ number_format($ck['active_customers']) }}</div></div>
            <div class="col"><div class="l">New</div><div class="v">{{ number_format($ck['new_customers']) }}</div></div>
            <div class="col"><div class="l">Returning</div><div class="v">{{ number_format($ck['returning_customers']) }}</div></div>
        </div>
        <div class="grid">
            <div class="col"><div class="l">Lost / Churn</div><div class="v">{{ number_format($ck['lost_customers']) }}</div></div>
            <div class="col"><div class="l">Avg LTV</div><div class="v">{{ number_format($ck['lifetime_value'], 2) }}</div></div>
            <div class="col"><div class="l">AOV</div><div class="v">{{ number_format($ck['avg_order_value'], 2) }}</div></div>
            <div class="col"><div class="l">Retention</div><div class="v">{{ $pct($ck['retention_rate']) }}</div></div>
        </div>
        @if (count($customers['top']) > 0)
            <h2>Top customers</h2>
            <table>
                <thead><tr>
                    <th>#</th><th>Customer</th>
                    <th class="num">Orders</th><th class="num">Delivered</th>
                    <th class="num">Completion</th><th class="num">Revenue</th><th class="num">AOV</th>
                    <th>Score</th>
                </tr></thead>
                <tbody>
                @foreach ($customers['top'] as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="num">{{ number_format($r['orders']) }}</td>
                        <td class="num">{{ number_format($r['delivered']) }}</td>
                        <td class="num">{{ $r['completion_rate'] !== null ? number_format($r['completion_rate']*100, 1).'%' : '—' }}</td>
                        <td class="num">{{ number_format($r['revenue'], 2) }}</td>
                        <td class="num">{{ number_format($r['aov'], 2) }}</td>
                        <td><span class="badge {{ $bandClass($r['band']) }}">{{ $r['score'] }} · {{ $bandLabel($r['band']) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endisset

    @isset($hubs)
        @php $hk = $hubs['kpi']; @endphp
        <h2>Branches</h2>
        <div class="grid">
            <div class="col"><div class="l">Total Branches</div><div class="v">{{ number_format($hk['total_branches']) }}</div></div>
            <div class="col"><div class="l">Active</div><div class="v">{{ number_format($hk['active_branches']) }}</div></div>
            <div class="col"><div class="l">Orders</div><div class="v">{{ number_format($hk['orders']) }}</div></div>
            <div class="col"><div class="l">Success Rate</div><div class="v">{{ $pct($hk['success_rate']) }}</div></div>
        </div>
        <div class="grid">
            <div class="col"><div class="l">Revenue</div><div class="v">{{ number_format($hk['revenue'], 2) }}</div></div>
            <div class="col"><div class="l">Expenses</div><div class="v">{{ number_format($hk['expenses'], 2) }}</div></div>
            <div class="col"><div class="l">Profit</div><div class="v">{{ number_format($hk['profit'], 2) }}</div></div>
            <div class="col"><div class="l">Employees / Vehicles</div><div class="v">{{ $hk['employees'] }} / {{ $hk['vehicles'] }}</div></div>
        </div>
        @if (count($hubs['ranking']) > 0)
            <h2>Branch ranking</h2>
            <table>
                <thead><tr>
                    <th>#</th><th>Branch</th>
                    <th class="num">Orders</th><th class="num">Delivered</th>
                    <th class="num">Success</th><th class="num">Revenue</th>
                    <th class="num">Profit</th><th>Score</th>
                </tr></thead>
                <tbody>
                @foreach ($hubs['ranking'] as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="num">{{ number_format($r['orders']) }}</td>
                        <td class="num">{{ number_format($r['delivered']) }}</td>
                        <td class="num">{{ $r['success_rate'] !== null ? number_format($r['success_rate']*100, 1).'%' : '—' }}</td>
                        <td class="num">{{ number_format($r['revenue'], 2) }}</td>
                        <td class="num">{{ number_format($r['profit'], 2) }}</td>
                        <td><span class="badge {{ $bandClass($r['band']) }}">{{ $r['score'] }} · {{ $bandLabel($r['band']) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endisset

    @isset($companies)
        @php $ok = $companies['kpi']; @endphp
        <h2>Operating Companies (3PL)</h2>
        <div class="grid">
            <div class="col"><div class="l">Total / Active</div><div class="v">{{ $ok['total_companies'] }} / {{ $ok['active_companies'] }}</div></div>
            <div class="col"><div class="l">Fleet</div><div class="v">{{ $ok['fleet_size'] }}</div></div>
            <div class="col"><div class="l">Handled / Completed</div><div class="v">{{ $ok['handled'] }} / {{ $ok['completed'] }}</div></div>
            <div class="col"><div class="l">Success Rate</div><div class="v">{{ $pct($ok['success_rate']) }}</div></div>
        </div>
        <div class="grid">
            <div class="col"><div class="l">Revenue</div><div class="v">{{ number_format($ok['revenue'], 2) }}</div></div>
            <div class="col"><div class="l">Expenses</div><div class="v">{{ number_format($ok['expenses'], 2) }}</div></div>
            <div class="col"><div class="l">Profit</div><div class="v">{{ number_format($ok['profit'], 2) }}</div></div>
            <div class="col"><div class="l">Avg Delivery</div><div class="v">{{ $ok['avg_delivery_hours'] !== null ? $ok['avg_delivery_hours'].' h' : '—' }}</div></div>
        </div>
        @if (count($companies['ranking']) > 0)
            <h2>Operating-company ranking</h2>
            <table>
                <thead><tr>
                    <th>#</th><th>Company</th>
                    <th class="num">Fleet</th><th class="num">Handled</th>
                    <th class="num">Success</th><th class="num">Revenue</th>
                    <th class="num">Profit</th><th>Score</th>
                </tr></thead>
                <tbody>
                @foreach ($companies['ranking'] as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="num">{{ $r['fleet_size'] }}</td>
                        <td class="num">{{ number_format($r['handled']) }}</td>
                        <td class="num">{{ $r['success_rate'] !== null ? number_format($r['success_rate']*100, 1).'%' : '—' }}</td>
                        <td class="num">{{ number_format($r['revenue'], 2) }}</td>
                        <td class="num">{{ number_format($r['profit'], 2) }}</td>
                        <td><span class="badge {{ $bandClass($r['band']) }}">{{ $r['score'] }} · {{ $bandLabel($r['band']) }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endisset

    @isset($insights)
        <h2>AI Insights</h2>
        @php $h = $insights['highlights']; @endphp
        <table>
            <thead><tr><th>Highlight</th><th>Name</th><th>Score</th></tr></thead>
            <tbody>
                @foreach (['best_driver','best_customer','best_branch','best_company','highest_revenue_company','fastest_growing_branch','worst_driver'] as $key)
                    @if (!empty($h[$key]))
                        <tr>
                            <td>{{ $h[$key]['kind'] ?? $key }}</td>
                            <td>{{ $h[$key]['name'] ?? '—' }}</td>
                            <td>
                                @if (!empty($h[$key]['score']))
                                    <span class="badge {{ $bandClass($h[$key]['band'] ?? '') }}">{{ $h[$key]['score'] }} · {{ $bandLabel($h[$key]['band'] ?? '') }}</span>
                                @else — @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        @if (count($insights['risks']) > 0)
            <h2>Risks</h2>
            <table>
                <thead><tr><th>Level</th><th>Kind</th><th>Title</th><th>Detail</th></tr></thead>
                <tbody>
                    @foreach ($insights['risks'] as $r)
                        <tr>
                            <td><span class="badge {{ $r['level'] === 'high' ? 'b-bad' : 'b-warn' }}">{{ strtoupper($r['level']) }}</span></td>
                            <td>{{ $r['kind'] }}</td>
                            <td>{{ $r['title'] }}</td>
                            <td>{{ $r['detail'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (count($insights['suggestions']) > 0)
            <h2>Suggested actions</h2>
            <ul>
                @foreach ($insights['suggestions'] as $s)
                    <li>{{ $s }}</li>
                @endforeach
            </ul>
        @endif
    @endisset

    <div class="footer">
        Performance Dashboard · Rushly · {{ now()->toIso8601String() }} <br/>
        Proxy KPIs marked above are computed from substitute data — see in-app tooltips for formulae.
    </div>
</body>
</html>
