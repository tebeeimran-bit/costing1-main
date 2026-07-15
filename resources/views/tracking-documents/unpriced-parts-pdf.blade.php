<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Unpriced Parts Recap</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 16px;
            color: #111827;
        }

        h2 {
            margin: 0 0 8px;
        }

        .meta {
            margin-bottom: 14px;
            font-size: 13px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
            font-size: 12px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <button class="no-print" onclick="window.print()">Print / Save as PDF</button>
    <h2>Rekapan Part Belum Ada Harga</h2>
    <div class="meta">
        Revisi: V{{ $revision->version_number }}<br>
        Project: {{ $revision->project->customer ?? '-' }} / {{ $revision->project->model ?? '-' }} / {{ $revision->project->part_number ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Part Number</th>
                <th>Part Name</th>
                <th>Detected Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->part_number }}</td>
                    <td>{{ $row->part_name }}</td>
                    <td>{{ $row->detected_price ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Tidak ada part unpriced untuk revisi ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
