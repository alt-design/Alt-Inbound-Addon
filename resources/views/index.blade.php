@extends('statamic::layout')

@section('content')
    <div id="alt-inbound-app" >
        <alt-blocker
            title="Alt Inbound"
            action="{{ cp_route('alt-inbound.create') }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
            :items="{{ json_encode($data) }}"
            :blacklistset="{{$blacklist}}"
        ></alt-blocker>
    </div>
@endsection
