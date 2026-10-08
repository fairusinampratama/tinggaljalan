@foreach (\App\Support\PlainText::paragraphs((string) ($text ?? '')) as $paragraph)
    <p>@foreach (explode("\n", $paragraph) as $line)@if (! $loop->first)<br>@endif{{ $line }}@endforeach</p>
@endforeach
