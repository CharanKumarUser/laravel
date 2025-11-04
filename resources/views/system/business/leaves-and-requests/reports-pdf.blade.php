{{-- Minimal PDF-only view: Leave & Requests clean table --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Leave Reports</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #000;
        }

        h3 {
            margin: 0 0 8px 0;
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }
    </style>
</head>

<body>
    <h3>Leave Requests</h3>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($data['items'] ?? []) as $row)
                <tr>
                    <td>{{ $row['employee'] ?? '-' }}</td>
                    <td>{{ $row['type'] ?? '-' }}</td>
                    <td>{{ $row['subject'] ?? '-' }}</td>
                    <td>{{ $row['start'] ?? '-' }}</td>
                    <td>{{ $row['end'] ?? '-' }}</td>
                    <td>{{ $row['duration'] ?? '-' }}</td>
                    <td>{{ $row['status'] ?? '-' }}</td>
                    <td>{{ $row['reason'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">No leave requests found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
