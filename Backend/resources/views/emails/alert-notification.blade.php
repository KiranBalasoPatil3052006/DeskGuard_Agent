<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskGuard Alert Notification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing: 1px;">
                                🛡️ DeskGuard Alert
                            </h1>
                        </td>
                    </tr>

                    <!-- Severity Banner -->
                    <tr>
                        <td style="padding: 0;">
                            @php
                                $severityColor = match(strtolower($alert->severity ?? 'warning')) {
                                    'critical' => '#dc3545',
                                    'warning' => '#ffc107',
                                    'info' => '#17a2b8',
                                    default => '#6c757d',
                                };
                                $severityBg = match(strtolower($alert->severity ?? 'warning')) {
                                    'critical' => '#fff5f5',
                                    'warning' => '#fffdf0',
                                    'info' => '#f0f9ff',
                                    default => '#f8f9fa',
                                };
                            @endphp
                            <div style="background-color: {{ $severityBg }}; border-left: 5px solid {{ $severityColor }}; padding: 16px 40px; margin: 0;">
                                <span style="color: {{ $severityColor }}; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                    ● {{ strtoupper($alert->severity ?? 'ALERT') }} ALERT
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- Alert Title -->
                    <tr>
                        <td style="padding: 30px 40px 10px;">
                            <h2 style="margin:0; color:#1a1a2e; font-size:20px; font-weight:600;">
                                {{ $alert->title ?? 'System Alert' }}
                            </h2>
                        </td>
                    </tr>

                    <!-- Alert Description -->
                    <tr>
                        <td style="padding: 10px 40px 20px;">
                            <p style="margin:0; color:#4a5568; font-size:15px; line-height:1.6;">
                                {{ $alert->description ?? $alert->message ?? 'An alert has been triggered for this machine.' }}
                            </p>
                        </td>
                    </tr>

                    <!-- Details Table -->
                    <tr>
                        <td style="padding: 10px 40px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa; border-radius:8px; overflow:hidden;">
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef;">
                                        <span style="color:#6c757d; font-size:13px; font-weight:600;">Computer Name</span>
                                    </td>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; text-align:right;">
                                        <span style="color:#1a1a2e; font-size:14px; font-weight:600;">{{ $computerName }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef;">
                                        <span style="color:#6c757d; font-size:13px; font-weight:600;">Employee Mobile</span>
                                    </td>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; text-align:right;">
                                        <span style="color:#1a1a2e; font-size:14px; font-weight:600;">{{ $mobileNumber }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef;">
                                        <span style="color:#6c757d; font-size:13px; font-weight:600;">Severity</span>
                                    </td>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; text-align:right;">
                                        <span style="color: {{ $severityColor }}; font-size:14px; font-weight:700;">
                                            {{ strtoupper($alert->severity ?? 'N/A') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef;">
                                        <span style="color:#6c757d; font-size:13px; font-weight:600;">Date</span>
                                    </td>
                                    <td style="padding: 14px 20px; border-bottom: 1px solid #e9ecef; text-align:right;">
                                        <span style="color:#1a1a2e; font-size:14px;">{{ $alert->created_at?->format('d M Y') ?? now()->format('d M Y') }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 20px;">
                                        <span style="color:#6c757d; font-size:13px; font-weight:600;">Time</span>
                                    </td>
                                    <td style="padding: 14px 20px; text-align:right;">
                                        <span style="color:#1a1a2e; font-size:14px;">{{ $alert->created_at?->format('h:i A') ?? now()->format('h:i A') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa; padding: 20px 40px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin:0; color:#6c757d; font-size:12px;">
                                This is an automated notification from <strong>DeskGuard</strong> monitoring system.<br>
                                Please log in to the DeskGuard dashboard to view full details and take action.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
