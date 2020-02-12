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
            return redirect('/Adresses');
        } elseif ($request->has('CustomAddress')) {
            $address = new Address();
            $address->Address = $request->input('CustomAddress');
            $address->Status = 1;
            $address->save();
            return redirect('/Adresses');
        }
    }

    public function StartSearch()
    {
        $Address = Address::where('Status', 1)->get();
        foreach ($Address as $job){
            SearchforAddress::dispatch($job);
        }
        return redirect('/Adresses');
    }

    public function check_address($id)
    {
        $address = Address::find($id);
        $Address = $address->Address;
        $keyWords = array(
            '/خیابان/',
            '/کوچه/',
            '/میدان/',
            '/بلوار/',
            '/پلاک/',
            '/ساختمان/',
            '/بزرگراه/',
            '/فلکه/',
            '/دانشکده/',
            '/\bخ\b/u',
            '/\bک\b/u',
            '/نبش/',
            '/جنب/',
            '/بن بست/' ,
            '/نرسیده به/',
            '/بعد از/' ,
            '/بالاتر از/',
            '/پ\d{1,4}/',
        );
        $Valideted = 0;
        foreach ($keyWords as $key) {
           $Valideted += preg_match($key,$Address);
        }
        if ($Valideted > 0) {
            echo "آدرس اصلی :‌ " . $Address . "<br/><br/><br/><br/><br/>";
            $Final_Address = $this->validateMethod1($Address);
            $response = $this->CallApi($Final_Address);
            dd($response);
            if (!empty($response->result) && $response->result[0]->certainty >= 70) {
                echo "آدرس جست و جو شده : " . $Final_Address . "<br/>";
                echo "آدرس پیدا شده : " . $response->result[0]->title . "<br/>";
                echo "درصد درستی آدرس : " . $response->result[0]->certainty;
            } else {
                $response = $this->LastTry($Address);
                if ($response != null) {
                    echo "آدرس جست و جو شده : " . $Address . "<br/>";
                    echo "آدرس پیدا شده : " . $response['title'] . "<br/>";
                    echo "درصد درستی آدرس : " . $response['certainty'];
                } else {
                    echo "Address Invalid After Search";
                }
            }

        } else {
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
        if (preg_match('/:\w/', $address) > 0) {
            preg_match_all('/:.*/', $address, $addres);
            $address = $addres[0][0];
        }
        $keyWords = array(
            '/\bخ\b/u' => 'خیابان',
            '/\bک\b/u' => 'کوچه',
            '/نبش/' => 'خیابان',
            '/جنب/' => 'خیابان',
            '/بین/' => 'خیابان',
            '/بن بست/' => 'کوچه',
            '/نرسیده به/' => 'خیابان',
            '/بعد از/' => 'خیابان',
            '/بالاتر از/' => 'خیابان',
            '/ابتدای/' => 'خیابان',
            '/بانک.+?\-/' => 'خیابان',
            '/\/\d/' => '',
            '/\W{0,100}:/' => '',
            '/\n/' => '',
            '/\(.*?\)/' => '',
            '/\bو\b/u' => '-خیابان',
        );
        $keys = array(
            '/^\W.+?\-/',
            '/میدان.+?\-/',
            '/بلوار.+?\-/',
            '/بزرگراه.+?\-/',
            '/فلکه.+?\-/',
            '/خیابان.+?\-/',
            '/کوچه.+?\-/',
            '/پمپ بنزین.+?\s/',
            '/برج.+?\s/',
            '/ساختمان.+?\s/',
            '/دانشکده.+?\-/'
        );
        $replace = array(
            '/خیابان کوچه/' => 'کوچه',
            '/خیابان خیابان/' => 'خیابان',
            '/خیابانساختمان/' => 'ساختمان',
            '/خیابن/' => 'خیابان',
            '/خیابان بزرگراه/' => 'بزرگراه',
            '/خیابان ایستگاه/' =>'ایستگاه'
        );
        $address = preg_replace(array_keys($keyWords), array_values($keyWords), $address);
        $founded = array();
        foreach ($keys as $key) {
            preg_match_all($key, $address, $match);
            $founded[] = implode("", $match[0]);
        }
        $final_address = implode("", $founded);
        $final_address = preg_replace(array_keys($replace), array_values($replace), $final_address);
        $exp = explode('-', $final_address);
        $arr = array_unique($exp);
        $final_address = implode(' ', $arr);
        if (strlen($final_address) > 10) {
            return $final_address;
        } else {
            return false;
        }

    }
    public function validateAddress2($address){
        if (preg_match('/،/', $address) > 0) {
            $address = preg_replace('/،/', '-', $address);
        }
        $address = preg_replace('/پ.\s?\d{1,4}/','پلاک',$address);
        $address = preg_replace('/پلاک.*/','',$address);
        $address = preg_replace('/-/' , ' ' , $address);
        $address = preg_replace('/ساختمان.*/','',$address);
        return $address;
    }

    public function LastTry($Address)
    {
        if (strpos($Address,'پلاک'))
            $Address = substr($Address, 0, strpos($Address, "پلاک"));
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
