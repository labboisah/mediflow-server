@extends('layouts.modern')

@section('page-title', 'Bills')

@section('content')

<div class="mb-6 flex justify-between">
    <h2 class="text-xl font-bold text-primary">All Bills</h2>

    <a href="{{ route('bills.create') }}"
       class="bg-accent text-white px-5 py-2 rounded-lg shadow">
        + New Bill
    </a>
</div>

<div class="bg-white rounded-2xl shadow p-6 overflow-x-auto">

    <table class="w-full text-left text-sm">

        <thead class="border-b">
            <tr>
                <th class="py-3">Bill No</th>
                <th>Patient</th>
                <th>Total (₦)</th>
                <th>Staff</th>
                <th>Annex</th>
                <th>Radiologist</th>
                <th>Radiographer</th>
                <th>Date</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>

        <tbody>
        @foreach($bills as $bill)
            <tr class="border-b hover:bg-lightbg">
                <td class="py-3 font-semibold text-primary">
                    {{ $bill->bill_no }}
                </td>

                <td>
                    {{ $bill->patient_name ?? 'Walk-in' }}
                </td>

                <td>
                    ₦{{ number_format($bill->total_amount,2) }}
                </td>

                <td class="text-accent font-semibold">
                    ₦{{ number_format($bill->shares()['staff'],2) }}
                </td>

                <td class="text-accent font-semibold">
                    ₦{{ number_format($bill->shares()['annex'],2) }}
                </td>

                <td class="text-accent font-semibold">
                    ₦{{ number_format($bill->shares()['radiologist'],2) }}
                </td>

                <td class="text-accent font-semibold">
                    ₦{{ number_format($bill->shares()['radiographer'],2) }}
                </td>

                <td>
                    {{ $bill->created_at->format('d M Y') }}
                </td>

                <td class="space-x-2">
                    <a href="{{ route('bills.show', $bill) }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A1 1 0 0017 4H3a1 1 0 00-.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a1 1 0 001 1h14a1 1 0 001-1V8.118z" />
                        </svg>
                        <span>View</span>
                    </a>

                    @if($bill->balance > 0)
                        <a href="{{ route('payments.create', $bill) }}" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Pay Now</span>
                        </a>
                    @else
                        <a href="{{ route('payments.receipt', ['bill' => $bill, 'type' => 'a4']) }}" class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 00-1 1v1H5.5A1.5 1.5 0 004 5.5v11A1.5 1.5 0 005.5 18h9a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0014.5 4H12V3a1 1 0 00-1-1H9zM7 6h6v2H7V6zm0 3h6v2H7V9z" />
                            </svg>
                            <span>Receipt A4</span>
                        </a>
                        <a href="{{ route('payments.receipt', ['bill' => $bill, 'type' => 'thermal']) }}" class="inline-flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 2a1 1 0 00-1 1v1H5.5A1.5 1.5 0 004 5.5v11A1.5 1.5 0 005.5 18h9a1.5 1.5 0 001.5-1.5v-11A1.5 1.5 0 0014.5 4H12V3a1 1 0 00-1-1H9zM7 6h6v2H7V6zm0 3h6v2H7V9z" />
                            </svg>
                            <span>Receipt Thermal</span>
                        </a>
                    @endif

                    @if(($bill->results_count ?? 0) === 0)
                        <form method="POST"
                              action="{{ route('bills.destroy', $bill) }}"
                              class="inline"
                              onsubmit="return confirm('Delete this bill?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                <span>Delete</span>
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

    <div class="mt-6">
        {{ $bills->links() }}
    </div>

</div>

@endsection

