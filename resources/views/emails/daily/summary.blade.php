@component('vendor.mail.html.layout')

<h3>At {{ $weather->address }}</h3>
<div>{{ $weather->temperature }}F  {{ $weather->condition }} {{$weather->condition_image}}</div>

<h2>Latest News:</h2>
<ul>
@foreach ($articles as $article)
<li>
    <div>
        <a href="{{ $article['url'] }}" target="_blank">{{ $article['headline'] }}</a>
    </div>
</li>
@endforeach
</ul>

@endcomponent
