<?php

namespace App\Jobs;

use App\Address;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SearchforAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $Address;
    public function __construct()
    {
        $this->Address = Address::where('Status' , 1)->get();

    }

    public function handle()
    {

        foreach ($this->Address as $key){
            $this->check($key->id);
        }
    }

    public function check($id)
    {
        $address = Address::find($id);
        $Address = $address->Address;

        if (strpos($Address, 'خیابان') || strpos($Address, 'خ ') || strpos($Address, "کوچه") || strpos($Address, "ک ") || strpos($Address, 'پلاک ') !== false) {
            $FinalAddress = $this->ValidateArray($Address);
            $response = $this->CallApi($FinalAddress);
            $address->FoundedAddress = $response->result[0]->title;
            $address->Status = 2;
            $address->save();
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

}
