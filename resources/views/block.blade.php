<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <s:AltInbound:FrontendAssets />
    @include('statamic::partials.head')

</head>
<body class="alt-inbound relative h-screen w-screen overflow-hidden">
    <!--    Desktop -->
    <div class="alt-inbound-desktop items-center justify-center h-screen w-screen bg-cover bg-center bg-no-repeat"
        style="background-image: url('<s:AltInbound:Background />');">
        <div class="z-20 block fixed mx-auto bg-white aspect-square h-3/4 max-h-[600px] rounded-2xl flex flex-col items-center justify-center">
            <img src="<s:AltInbound:Blocked />" class="w-56"/>
            <div class="alt-inbound-text">
                <h1 class="mt-8 mb-4 md:text-2xl lg:text-5xl font-bold text-center">Oops, sorry about this</h1>
                <h2 class="text-base mt-4 text-center">You're not allowed to visit this site.</h2>
                <h2 class="text-base mt-1 text-center">If you think this is an error, please contact site administrators.</h2>
            </div>
        </div>
    </div>

    <!--    Mobile   -->
    <div class="alt-inbound-mobile items-center justify-center flex-col  h-screen w-screen"
         style="background: url('<s:AltInbound:Mobile />')">
        <div class="z-20 fixed mx-auto bg-white w-11/12 aspect-square m-8 rounded-xl flex flex-col items-center justify-center">
            <img src="<s:AltInbound:Blocked />" class="w-36"/>
            <div class="alt-inbound-text">
                <h1 class="text-3xl text-center font-bold mt-4 mb-4">Oops, sorry about this</h1>
                <h2 class="text-base text-center mb-1 ">You're not allowed to visit this site.</h2>
                <h2 class="text-base text-center">If you think this is an error, please contact site administrators.</h2>
            </div>
        </div>
    </div>
</body>
</html>
