@extends('layouts.modern')

@section('page-title','Financial Reports')

@section('content')

<div class="max-w-6xl mx-auto bg-white p-8 rounded-2xl shadow">

<form method="POST" action="{{ route('admin.reports.generate') }}" class="space-y-6">
@csrf

<div class="grid md:grid-cols-3 gap-6">

    <div>
        <label class="block text-sm mb-2">From Date</label>
        <input type="date" name="from" value="{{ request('from') }}" class="w-full px-4 py-3 border rounded-xl">
    </div>

    <div>
        <label class="block text-sm mb-2">To Date</label>
        <input type="date" name="to" value="{{ request('to') }}" class="w-full px-4 py-3 border rounded-xl">
    </div>

    <div class="flex items-center gap-3 mt-8">
        <input type="checkbox" name="today" value="1" {{ request('today') ? 'checked' : '' }}> 
        <span>Today Only</span>
    </div>

    @if(auth()->user()->role === 'admin')
        <div class="md:col-span-3">
            <label class="block text-sm mb-2">User (optional)</label>
            <select name="user_id" class="w-full px-4 py-3 border rounded-xl">
                <option value="">All users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

</div>

<div class="flex flex-wrap gap-4 mt-6">
    <button name="type" value="summary" class="bg-primary text-white px-6 py-3 rounded-xl">
        View Summary
    </button>

    <button name="type" value="user-breakdown" class="bg-indigo-600 text-white px-6 py-3 rounded-xl">
        User Income Breakdown
    </button>
</div>

</form>

</div>

<div class="max-w-6xl mx-auto mt-8">
    <div class="grid md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-sm text-gray-500">Total Reports</p>
            <h3 class="text-2xl font-bold">{{ $stats->total_reports }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-sm text-gray-500">Total Gross Revenue</p>
            <h3 class="text-2xl font-bold">NGN {{ number_format($stats->total_gross ?? 0, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-sm text-gray-500">Total Salary In Reports</p>
            <h3 class="text-2xl font-bold">NGN {{ number_format($stats->total_salary ?? 0, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-sm text-gray-500">Total Profit</p>
            <h3 class="text-2xl font-bold">NGN {{ number_format($stats->total_profit ?? 0, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow">
            <p class="text-sm text-gray-500">Average Profit per Report</p>
            <h3 class="text-2xl font-bold">NGN {{ number_format($stats->avg_profit ?? 0, 2) }}</h3>
        </div>
    </div>

    @if(isset($userBreakdown) && $userBreakdown)
        <div class="bg-white rounded-2xl shadow p-6 overflow-x-auto mb-6">
            <h3 class="text-lg font-bold mb-4">User Income Breakdown</h3>
            <table class="w-full text-left text-sm">
                <thead class="border-b">
                    <tr>
                        <th class="py-3">User</th>
                        <th>Gross</th>
                        <th>Net</th>
                        <th>Staff Share</th>
                        <th>Annex Share</th>
                        <th>Radiologist Share</th>
                        <th>Radiographer Share</th>
                        <th>Expenses</th>
                        <th>Salary</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($userBreakdown as $userData)
                        <tr class="border-b hover:bg-lightbg">
                            <td class="py-3 font-semibold">{{ $userData['user_name'] ?? 'Unknown' }}</td>
                            <td>NGN {{ number_format($userData['gross'], 2) }}</td>
                            <td>NGN {{ number_format($userData['net'], 2) }}</td>
                            <td>NGN {{ number_format($userData['staff_share'], 2) }}</td>
                            <td>NGN {{ number_format($userData['annex_share'], 2) }}</td>
                            <td>NGN {{ number_format($userData['radiologist_share'], 2) }}</td>
                            <td>NGN {{ number_format($userData['radiographer_share'], 2) }}</td>
                            <td>NGN {{ number_format($userData['expenses'], 2) }}</td>
                            <td>NGN {{ number_format($userData['salary_amount'] ?? 0, 2) }}</td>
                            <td>NGN {{ number_format($userData['profit'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow p-6 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b">
                <tr>
                    <th class="py-3">Reference</th>
                    <th>Date</th>
                    <th>Range</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>Salary</th>
                    <th>Profit</th>
                    <th>Generated By</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr class="border-b hover:bg-lightbg">
                        <td class="py-3 font-semibold">{{ $report->report_reference }}</td>
                        <td>{{ optional($report->created_at)->format('d M Y H:i') }}</td>
                        <td>{{ $report->from_date ? \Carbon\Carbon::parse($report->from_date)->format('d M Y') : '-' }} - {{ $report->to_date ? \Carbon\Carbon::parse($report->to_date)->format('d M Y') : '-' }}</td>
                        <td>NGN {{ number_format($report->gross, 2) }}</td>
                        <td>NGN {{ number_format($report->net, 2) }}</td>
                        <td>NGN {{ number_format($report->salary_amount ?? 0, 2) }}</td>
                        <td>NGN {{ number_format($report->profit, 2) }}</td>
                        <td>{{ optional($report->user)->name ?? 'System' }}</td>
                        <td class="text-right">
                            @if($report->pdf_path)
                                <a href="{{ asset($report->pdf_path) }}" class="text-blue-600 hover:underline" target="_blank">Download</a>
                            @else
                                <span class="text-gray-500">No PDF</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</div>

@endsection

