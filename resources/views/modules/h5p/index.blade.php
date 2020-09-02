@extends('h5p::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('h5p.name') !!}
    </p>
@endsection
