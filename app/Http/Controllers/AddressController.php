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
        if (strpos($Address, 'خیابان') ||
            strpos($Address, 'خ ') ||
            strpos($Address, "کوچه") ||
            strpos($Address, "ک ") ||
            strpos($Address, 'پلاک ') ||
            strpos($Address, "واحد") ||
            strpos($Address, "ساختمان") !== false) {
            $FinalAddress = $this->ValidateArray($Address);
            echo "آدرس جست و جو شده :‌ " . $FinalAddress;
            echo "<br/><br/><br/><br/><br/>";
            $response = $this->CallApi($FinalAddress);
            if ($response->result != null) {
                $address->FoundedAddress = $response->result[0]->title;
                $address->Status = 2;
                $address->save();
                echo "آدرس پیدا شده :‌ " . $response->result[0]->title;
            } else {
                echo "Address not found";
                $address->FoundedAddress = "Address Not Found";
                $address->Status = 3;
                $address->save();
            }
        } else {
            echo "Address Not Valid";
        }
    }

    public function check_address($id)
    {
        $address = Address::find($id);
        $Address = $address->Address;
        if (preg_match('/خیابان/', $Address) > 0 ||
            preg_match('/کوچه/', $Address) > 0 ||
            preg_match('/میدان/', $Address) > 0 ||
            preg_match('/بلوار/', $Address) > 0 ||
            preg_match('/پلاک/', $Address) > 0 ||
            preg_match('/بن بست/', $Address) > 0 ||
            preg_match('/ساختمان/', $Address) > 0 ||
            preg_match('/\bخ\b/u', $Address) > 0 ||
            preg_match('/\bک\b/u', $Address) > 0) {
            echo "آدرس اصلی :‌ " . $Address . "<br/><br/><br/><br/><br/>";
            $Final_Address = $this->validateMethod1($Address);
            $response = $this->CallApi($Final_Address);
            if (!empty($response->result) && $response->result[0]->certainty > 70){
                echo "آدرس جست و جو شده : " . $Final_Address . "<br/>";
                echo "آدرس پیدا شده : ". $response->result[0]->title . "<br/>";
                echo "درصد درستی آدرس : " .$response->result[0]->certainty ;
            }else{
                $FinalAddress = $this->validateMethod2($Address);

                $response = $this->CallApi($FinalAddress);
                if (!empty($response->result) && $response->result[0]->certainty > 70){
                    echo "آدرس جست و جو شده : " . $Final_Address . "<br/>";
                    echo "آدرس پیدا شده : ". $response->result[0]->title . "<br/>";
                    echo "درصد درستی آدرس : " .$response->result[0]->certainty ;
                }else{
                    $response = $this->LastTry($Address);
                    if ($response != null){
                        echo "آدرس جست و جو شده : " . $Address . "<br/>";
                        echo "آدرس پیدا شده : ". $response['title'] . "<br/>";
                        echo "درصد درستی آدرس : " .$response['certainty'] ;
                    }else{
                        echo "Address Invalid After Search";
                    }
                }
            }
        }else{
            echo "Address Invalid Before Search";
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

    public function validateMethod1($address)
    {

        if (preg_match('/،/', $address) > 0) {
            $address = preg_replace('/،/', '-', $address);
        }
        $keyWords = array(
            '/\bخ\b/u' => 'خیابان',
            '/\bک\b/u' => 'کوچه',
            '/نبش/' => 'خیابان',
            '/جنب/' => 'خیابان',
            '/بن بست/' => 'کوچه',
            '/نرسیده به/' => 'خیابان',
            '/بعد از/' => 'خیابان',
            '/بالاتر از/' => 'خیابان',
            '/ابتدای/' => 'خیابان',
            '/\/\d/' => '',

        );
        $keys = array(
            '/^\W.+?\-/',
            '/خیابان.+?\-/',
            '/کوچه.+?\-/',
            '/بلوار.+?\-/',
            '/بزرگراه.+?\-/',
            '/فلکه.+?\-/',
            '/میدان.+?\-/',
            '/برج.+?\s/',
            '/ساختمان.+?\s/',
            '/پمپ بنزین.+?\s/',
        );
        $address = preg_replace(array_keys($keyWords), array_values($keyWords), $address);
        $founded = array();
        foreach ($keys as $key) {
            preg_match_all($key, $address, $match);
            $founded[] = $match[0];
        }
        $final_address = "";
        for ($i = 0; $i < count($founded); $i++) {
            $final_address .= implode("", $founded[$i]);
        }

        $final_address = preg_replace('/-/', ' ', $final_address);
        if (strlen($final_address) > 10) {
            return $final_address;
        } else {
            return false;
        }

    }

    public function ValidateMethod2($address)
    {
        $keyWords = array(
            '/،/' => ' ',
            '/-/' => ' ',
            '/\bخ\b/u' => 'خیابان',
            '/\bک\b/u' => 'کوچه',
            '/پ\s?\d{1,3}/' => 'پلاک'
        );
        $keys = array(
            'واحد',
            'طبقه',
            'پلاک',
            'شرکت',
            'ساختمان',
        );
        $address = preg_replace(array_keys($keyWords), array_values($keyWords), $address);
        if (strpos($address, ":")) {
            $address = strstr($address, ':');
            $address = str_replace(":", "", $address);
        }

        foreach ($keys as $key) {

            if (strpos($address, $key)) {
                $address = substr($address, 0, strpos($address, $key));
            }
        }
        return $address;
    }

    public function SearchMethod2($Address, $certainty)
    {
        $FinalAddress = $this->validateMethod2($Address);
        $response = $this->CallApi($FinalAddress);
        if ($response != null) {
            if (!empty($response->result)) {


                if ($response->result[0]->certainty > $certainty) {
                    $result = array(
                        'Final_Address' => $FinalAddress,
                        'Fonded_Address' => $response->result[0]->title,
                        'certainty' => $response->result[0]->certainty
                    );
                    return $result;
                }
                return false;

            }
            return false;
        }
        return false;
    }

    public function LastTry($Address)
    {
        $response = $this->CallApi($Address);
            if (!empty($response->result)) {
                return array(
                    'title' => $response->result[0]->title,
                    'certainty' => $response->result[0]->certainty
                );
            } else {
                return null;
            }
    }


    public function GetDataFromSQl()
    {
        $All_Count = Address::all()->count();
        $Founded_Count = Address::where('Status', 2)->count();
        return response()->json(array('All_Count' => $All_Count, 'Founded_Count' => $Founded_Count), 200);
    }

}
