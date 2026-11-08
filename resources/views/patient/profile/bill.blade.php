
<table class="table">
    <thead>
        <tr>
            <th>Bill No</th>
            <th>Service Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Issued Date</th>
            <th>Due Date</th>
            <th>Issued By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($patient->patientVisits as $visit)
            @foreach($visit->bills as $bill)
            @php
                $canManageBill = $bill->canBeManagedAsUnpaidByAccountant(auth()->user());
                $deleteBlockReason = $bill->softDeleteBlockReason();
            @endphp
            <tr>
                <td>{{$bill->bill_number}}</td>
                <td>{{$bill->service_description}}</td>
                <td>{{ number_format($bill->due_amount, 2) }}</td>
                <td>{{$bill->status}}</td>
                <td>{{$bill->notes}}</td>
                <td>{{$bill->issued_date}}</td>
                <td>{{$bill->due_date}}</td>
                <td>{{$bill->issuedBy->name}}</td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                    @if($canManageBill)
                        <a href="{{ route('accountant.bills.show', $bill) }}" class="btn btn-sm btn-outline-primary">View</a>
                        <a href="{{ route('accountant.bills.edit', $bill) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                        @if(! $deleteBlockReason)
                            <form action="{{ route('accountant.bills.delete', $bill) }}" method="POST" onsubmit="return confirm('Soft delete this unpaid bill? It can be restored from Deleted Bills.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ $deleteBlockReason }}">Delete</button>
                        @endif
                    @endif
                    @if(in_array($bill->status, ['pending', 'partial'], true))
                    <a href="{{route('accountant.bills.payments.create', $bill)}}" class="btn btn-sm btn-danger">Record Payment</a>
                    @endif
                    </div>
                </td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
<div class="infor">
    <p>Total:  {{$patient->bills()['amount']}}</p>
    <p>Paid:  {{$patient->bills()['paid']}}</p>
    <p>Pending:  {{$patient->bills()['pending']}}</p>
    <p>Overdue:  {{$patient->bills()['overdue']}}</p>
</div>
 
