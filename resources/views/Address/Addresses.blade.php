@extends('layout')
@section('content')
    <meta name="_token" content="{{csrf_token()}}"/>
    <div class="widget has-shadow">
        <div class="widget-header bordered no-actions d-flex align-items-center">
            <h4>آدرس ها</h4>
        </div>
        <div class="widget-body">
            <div class="table-responsive">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: 0%"
                         aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" id="ProgressBar"></div>
                </div>
                <div>
                    <p id="AllAddress">تعداد کل آدرس ها :‌ </p>
                    <p id="founded">تعداد پیدا شده ها :‌ </p>
                    <p id="notfound">تعداد پیدا نشده ها :‌ </p>
                </div>
                <table class="table mb-0">
                    <thead>
                    <tr>
                        <th>ایدی</th>
                        <th>آدرس</th>
                        <th>وضعیت</th>
                        <th>آدرس پیدا شده</th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach($content as $address)
                        <tr>
                            <td><span class="text-primary">{{$address->id}}</span></td>
                            <td>{{$address->Address}}</td>
                            <td>
                                @if($address->Status == 1)
                                    انجام نشده
                                @else
                                    انجام شده
                                @endif
                            </td>
                            <td>
                                @if($address->Status == 1)
                                    هیچی
                                @else
                                    {{$address->FoundedAddress}}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{$content->links()}}
@endsection

@section('js')
    <script type="text/javascript">
        $(document).ready(function () {
            ChangeProgressBar();
        });

        function ChangeProgressBar() {
            var ProgressBar = document.getElementById(ProgressBar);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                }
            });
            jQuery.ajax({
                url: "/GetDataFromSQl",
                method: 'post',
                success: function (result) {
                    console.log(result);
                    var Founded_Count = result['Founded_Count'];
                    var All_Count = result['All_Count'];
                    var Darsad = 100 / All_Count;
                    var ProgressBarWidth = Founded_Count * Darsad;
                    document.getElementById('ProgressBar').style.width = ProgressBarWidth + "%";
                    document.getElementById('AllAddress').innerHTML = "تعداد کل آدرس ها :‌ " + All_Count;
                    document.getElementById('founded').innerHTML = "تعداد پیدا شده ها :‌ " + Founded_Count;
                    document.getElementById('notfound').innerHTML = "تعداد پیدا نشده ها :‌ " + (All_Count - Founded_Count);
                }
            });

        }

        setInterval(ChangeProgressBar, 100);
        setInterval(ChangeAddress, 100);
    </script>
@endsection
