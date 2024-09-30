@extends('backend.mails.container')

@section('content')

<h1>{!! $data['title'] !!}</h1>
<p>{!! $data['body'] !!}</p>

@endsection