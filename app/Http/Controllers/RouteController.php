<?php

namespace App\Http\Controllers;

use App\Address;
use Illuminate\Http\Request;

class RouteController extends Controller
{
  public function Index(){
      return view('welcome');
  }
  public function CreateNewJob(){
      return view('Address.CreateNew');
  }
  public function ShowAddresses(){
      $content = Address::paginate(25);
      return view('Address.Addresses',['content' => $content]);
  }
}
