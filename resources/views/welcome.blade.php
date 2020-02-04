@extends('layout')
@section('content')
    <meta name="_token" content="{{csrf_token()}}" />

    <div class="col-xl-4 col-md-6">
        <!-- Begin Widget 05 -->
        <div class="widget widget-05 has-shadow">
            <!-- Begin Widget Header -->
            <div class="widget-header bordered d-flex align-items-center">
                <h2>وضعیت برنامه</h2>
            </div>
            <!-- End Widget Header -->
            <!-- Begin Widget Body -->
            <div class="widget-body no-padding hidden">
                <div class="social-stats">
                    <div class="row d-flex justify-content-between">
                        <div class="col-4 text-center">
                            <div class="counter" id="AllAddress">
                            </div>
                            <div class="heading">تمامی آدرس ها</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="counter" id="founded"></div>
                            <div class="heading">پیدا شده</div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="counter" id="notfound"></div>
                            <div class="heading">پیدا نشده</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Widget Body -->
        </div>
        <!-- End Widget 05 -->
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
                    document.getElementById('AllAddress').innerHTML =  All_Count;
                    document.getElementById('founded').innerHTML =   Founded_Count;
                    document.getElementById('notfound').innerHTML =  (All_Count - Founded_Count);
                }});
        }
    </script>
@endsection
