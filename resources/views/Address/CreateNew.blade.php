@extends('layout')
@section('content')
    <div class="row flex-row">
        <div class="col-12">
            <!-- Form -->
            <div class="widget has-shadow">
                <div class="widget-header bordered no-actions d-flex align-items-center">
                    <h4>همه عناصر</h4>
                </div>
                <div class="widget-body">
                    <form class="form-horizontal" method="post" action="/CreateNewJob" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group row d-flex align-items-center mb-5">
                            <label class="col-lg-3 form-control-label">وارد کردن دستی آدرس</label>
                            <div class="col-lg-9">
                                <input type="text" name="CustomAddress" class="form-control">
                            </div>
                        </div>

                        <div class="form-group row d-flex align-items-center mb-5">
                            <label class="col-lg-3 form-control-label">وارد کردن  آدرس از اکسل</label>
                            <div class="col-lg-9">
                                <input type="file" name="ExelFileAddress" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary mr-1 mb-2">ثبت</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- End Form -->
        </div>
    </div>
    @endsection
