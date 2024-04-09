@extends('statamic::layout')

@section('content')
    {!! ((new \AltDesign\AltInbound\Tags\AltInbound())->CPAssets()) !!}
    <div id="alt-inbound-app" >
        <alt-inbound
            title="Alt Inbound"
            action="{{ cp_route('alt-inbound.create') }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
            :items="{{ json_encode($data) }}"
            :blacklistset="{{$blacklist}}"
        ></alt-inbound>
    </div>
@endsection
