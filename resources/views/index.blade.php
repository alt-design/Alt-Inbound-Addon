@extends('statamic::layout')

@section('content')
    <div id="alt-redirect-app" >
        <alt-blocker
            title="Alt Blocker"
            action="{{ cp_route('alt-blocker.create') }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
            :items="{{ json_encode($data) }}"
            :blacklistset="{{$blacklist}}"
        ></alt-blocker>
    </div>
@endsection
