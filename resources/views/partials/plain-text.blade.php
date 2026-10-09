@foreach (\App\Support\PlainText::paragraphs((string) ($text ?? '')) as $paragraph)
    <p>@foreach (explode("\n", $paragraph) as $line)@if (! $loop->first)<br>@endif@if ($inlineStrong ?? false)@include('partials.inline-strong', ['text' => $line])@else{{ $line }}@endif@endforeach</p>
@endforeach
