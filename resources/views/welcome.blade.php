@extends('layout')
@section('content')
    <meta name="_token" content="{{csrf_token()}}" />
    <h1>
        @auth
            {{'Hi '. auth::user()->name}}
        @endauth
    </h1>
    <div>
        <p id="AllAddress"></p>
        <p id="founded"></p>
        <p id="notfound"></p>
    </div>

@endsection
@section('js')
    <script type="text/javascript">
        $(document).ready(function () {
            GetAddress();
        });
        function GetAddress() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            jQuery.ajax({
                url: "/GetDataFromSQl",
                method: 'post',
                success: function(result){
                    var Founded_Count = result['Founded_Count'];
                    var All_Count = result['All_Count'];
                    document.getElementById('AllAddress').innerHTML =  "تعداد کل آدرس ها :‌ " + All_Count;
                    document.getElementById('founded').innerHTML =  "تعداد پیدا شده ها :‌ " + Founded_Count;
                    document.getElementById('notfound').innerHTML =  "تعداد پیدا نشده ها :‌ " + (All_Count - Founded_Count);
                }});
        }
    </script>
@endsection
