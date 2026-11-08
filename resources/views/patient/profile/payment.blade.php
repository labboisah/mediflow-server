
<table class="table">
    <thead>
        <tr>
            <th>Bill No</th>
            <th>Payment ID</th>
            <th>Amount</th>
            <th>Method of Payment</th>
            <th>Status</th>
            <th>Payment Date</th>
            <th>Paid By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($patient->patientVisits as $visit)
            @foreach($visit->bills as $bill)
                @foreach($bill->payments as $payment)
                <tr>
                    <td>{{$bill->bill_number}}</td>
                    <td>{{$payment->payment_id}}</td>
                    <td>{{$payment->amount}}</td>
                    <td>{{$payment->paymentMethod->name}}</td>
                    <td>{{$payment->status}}</td>
                    <td>{{$payment->payment_date}}</td>
                    <td>{{$payment->recordedBy->name}}</td>
                    <td><a href="{{route('accountant.payments.receipt',$payment)}}">Print Receipt</a></td>
                </tr>
                @endforeach
            @endforeach
        @endforeach
    </tbody>
</table>
<div class="infor">
    <p>Total:  {{number_format($patient->payment()['total'], 2)}}</p>
    <p>Number of Payments:  {{$patient->payment()['count']}}</p>
    <p>Paid:  {{number_format($patient->payment()['paid'], 2)}}</p>
    <p>Pending:  {{number_format($patient->payment()['pending'], 2)}}</p>
    <p>Reversed:  {{number_format($patient->payment()['reversed'], 2)}}</p>
</div>
 