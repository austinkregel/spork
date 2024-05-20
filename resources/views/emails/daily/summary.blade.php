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


### Here's a summary of your accounts and transactions

@if($accounts->count() > 0)
<ul>
@foreach ($accounts as $account)
<li>
    {{ $account->name }} - $<b>{{$account->balance}}</b>/{{ $account->available }}
</li>
@endforeach
</ul>
@endif
@if($transactions->count() > 0)

#### We also dug through your transactions and found the following:

@foreach ($transactions as $transactionGroup)
<h5>{{ $transactionGroup[0]->date->format('F j, Y') }}</h5>
<ul>
@foreach ($transactionGroup as $transaction)
<li>
<div>${{ $transaction->amount }} - {{ $transaction->name }}@if($transaction->pending === true)* @endif</div>
</li>
@endforeach
</ul>
@endforeach
@endif
@endcomponent
