<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
  public function Index(){
      $notification = DB::table('jobs')->get();
      return view('welcome',['notification' => $notification]);
  }
  public function CreateNewJob(){
      return view('Address.CreateNew');
  }
  public function ShowAddresses(){
      $content = Address::paginate(25);
      return view('Address.Addresses',['content' => $content]);
  }
}
