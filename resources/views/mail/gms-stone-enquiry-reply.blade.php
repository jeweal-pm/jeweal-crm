<p>{!! nl2br(e($replyMessage)) !!}</p>

<hr>

<p>
    <strong>Original request</strong><br>
    Name: {{ $enquiry->full_name }}<br>
    Email: {{ $enquiry->email }}<br>
    Phone: {{ $enquiry->phone_number }}<br>
    Company: {{ $enquiry->company_name ?: $enquiry->business_name ?: '-' }}
</p>
