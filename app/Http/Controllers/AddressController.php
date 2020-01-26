<?php

namespace App\Http\Controllers;

use App\Address;
use App\Imports\AddressImport;
use App\Jobs\SearchforAddress;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AddressController extends Controller
{
    public function StoreAddress(Request $request)
    {
        if ($request->hasfile('ExelFileAddress')) {
            Excel::import(new AddressImport, $request->file('ExelFileAddress'));
            SearchforAddress::dispatch();
            return redirect('/Adresses');
        } elseif ($request->has('CustomAddress')) {
            $address = new Address();
            $address->Address = $request->input('CustomAddress');
            $address->Status = 1;
            $address->save();
            SearchforAddress::dispatch();
            return redirect('/Adresses');
        }
    }

    public function check($id)
    {
        $address = Address::find($id);
        $Address = $address->Address;
        if (strpos($Address, 'خیابان') || strpos($Address, 'خ ') || strpos($Address, "کوچه") || strpos($Address, "ک ") || strpos($Address, 'پلاک ') || strpos($Address , "واحد")|| strpos($Address , "ساختمان") !== false) {
            $FinalAddress = $this->ValidateArray($Address);
            echo "آدرس جست و جو شده :‌ ".$FinalAddress;
            echo "<br/><br/><br/><br/><br/>";
            $response = $this->CallApi($FinalAddress);
            $address->FoundedAddress = $response->result[0]->title;
            $address->Status = 2;
            $address->save();
            echo "آدرس پیدا شده :‌ " . $response->result[0]->title;
        }else{
            echo "Address Not Valid";
        }
    }

    public function CallApi($address)
    {

        $curl = curl_init();
        $text = urlencode(trim($address));
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://alopeyk.parsimap.com/comapi.svc/FindAddressLocation/10511133/" . $text . "/ALo475W-43FG6cv7-OPw230-kmA88q/11",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "X-Requested-With: XMLHttpRequest"
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);

    }

    public function ValidateArray($address)
    {
        if (strpos($address, "،")) {
            $address = explode("،", $address);
            $address = implode(" ", $address);
        }
        elseif (strpos($address, "-")) {
            $address = explode("-", $address);
            $address = implode(" ", $address);
        }
        if (strpos($address, ":")) {
            $address = strstr($address, ':');
            $address = str_replace(":", "", $address);
        }
        if (strpos($address , ")") ){
            $start = "(";
            $end = ")";
            $replace = " ";
            $pos1 = strpos($address , $start);
            $pos2 = strpos($address , $end , $pos1);
            $lenght = $pos2 + strlen($pos1) - $pos1;
            $address = substr_replace($address , $replace , $pos1 , $lenght);
        }
        if (strpos($address, "واحد")) {
            $address = substr($address, 0, strpos($address, "واحد"));
        }
        if (strpos($address, "طبقه")) {
            $address = substr($address, 0, strpos($address, "طبقه"));
        }
        if (strpos($address, "پلاک")) {
            $address = substr($address, 0, strpos($address, "پلاک"));
        }
        if (strpos($address, "شرکت")) {
            $address = substr($address, 0, strpos($address, "شرکت"));
        }
        if (strpos($address, "ساختمان")) {
            $address = substr($address, 0, strpos($address, "ساختمان"));
        }
       return $address;
    }

    public function GetDataFromSQl()
    {
        $All_Count = Address::all()->count();
        $Founded_Count = Address::where('Status', 2)->count();
        return response()->json(array('All_Count' => $All_Count, 'Founded_Count' => $Founded_Count), 200);
    }
}
