<p>{!! nl2br(e($replyMessage)) !!}</p>

<hr>

<p>
    <strong>Original {{ $enquiryType }}</strong><br>
    @if($enquiryType === 'GIS enquiry')
        Name: {{ trim($enquiry->first_name.' '.$enquiry->last_name) }}<br>
        Email: {{ $enquiry->email }}<br>
        Phone: {{ $enquiry->phone_number }}<br>
        Inquiry: {{ $enquiry->inquiry ?: '-' }}
    @else
        Name: {{ $enquiry->name }}<br>
        Email: {{ $enquiry->email }}<br>
        Phone: {{ $enquiry->phone }}<br>
        Company: {{ $enquiry->company ?: '-' }}
    @endif
</p>
