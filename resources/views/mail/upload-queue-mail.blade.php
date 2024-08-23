<div>
    Hi, <br>
    <h4>File Scan Status : {{ $tracker->ciUploadId }}</h4>
    <br>
    <p>Processing ID : {{ $tracker->processing_id }}</p>
    <p>Processing Time : {{ $tracker->updated_at }}</p>
    <br>

@switch($tracker->status)
    @case('pending')
        <p> Your file is in queue for processing. </p>
        <br>
        <i>We will notify you once the processing is completed.</i>
        <br>
        @break

    @case('completed')
        <p> Your file has been processed successfully. </p>
            @if($tracker->no_of_threats_found > 1)
                <b>!! Attention Needed !!</b>
                <p> {{ $tracker->no_of_threats_found }} threats found in the Scan. Please take action. </p>
            @else
                <p> {{ $tracker->no_of_threats_found }} threat found in the file. </p>
            @endif

            @if(!empty($tracker->details_url))
                <a href="{{ $tracker->details_url }}">View Scan Details</a>
                <br>
            @endif
        @break
    @default
        <p> Your file processing has failed. </p>
        <br>
        <i>Please contact support for more details.</i>
        <br>
        <i>You can view the processed scan from the link below.</i>
        <br>
        @if(!empty($tracker->details_url))
            <a href="{{ $tracker->details_url }}">View</a>
            <br>
        @endif
@endswitch

    <p>Thanks, <br>
        Team </p>
</div>
