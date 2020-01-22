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
        $array = $this->ValidateArray($address->Address);
        $FinalAddress = implode(" ", $array);
        $response = $this->CallApi($FinalAddress);
        while ($response == null) {
            array_splice($array, -1);
            $FinalAddress = implode(" ", $array);
            $response = $this->CallApi($FinalAddress);
        }
        while ($response->num < 1) {
            array_splice($array, -1);
            $FinalAddress = implode(" ", $array);
            $response = $this->CallApi($FinalAddress);
        }
        $address->FoundedAddress = $response->result[0]->title;
        $address->Status = 2;
        $address->save();
        echo "محل پیدا شده :‌ " . $response->result[0]->title;
        echo "<br/><br/><br/><br/>";
        echo $FinalAddress;
        echo "<br/><br/><br/><br/>";
        var_dump($response);
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
        $first = str_replace("،", " ", $address);
        $second = str_replace("-", " ", $first);
        $third = str_replace("(", " ", $second);
        $Fourth = str_replace(":", " ", $third);
        $Fifth = str_replace("سلام", " ", $Fourth);
        $Sixth = str_replace("دفتر", " ", $Fifth);
        $Seventh = str_replace("مرکزی", " ", $Sixth);
        $Final = str_replace(")", " ", $Seventh);
        $array = explode(" ", $Final);
        return $array;
    }

    public function GetDataFromSQl(){
        $All_Count = Address::all()->count();
        $Founded_Count = Address::where('Status' , 2)->count();
        return response()->json(array('All_Count'=> $All_Count , 'Founded_Count' => $Founded_Count), 200);
    }
}
