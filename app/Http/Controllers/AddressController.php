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

    public function check_address($id)
    {
        $address = Address::find($id);
        $Address = $address->Address;
        /*
          $validateAddress = array(
            '/خیابان/',
            '/کوچه/',
            '/میدان/',
            '/بلوار/',
            '/پلاک/',
            '/بن بست/',
            '/ساختمان/',
            '/\bخ\b/u',
            '/\bک\b/u'
        );
          foreach ($validateAddress as $key) {
            $founded = strpos($Address,$key);
            preg_match_all($key, $Address, $match);
            $founded[] = $match[0];
        }*/
        $validateAddress = array(
            'خیابان',
            'کوچه',
            'میدان',
            'بلوار',
            'پلاک',
            'بن بست',
            'ساختمان',
            'بزرگراه',
            'فلکه',
            'نرسیده به'

        );
        if ($this->MultiStrPos($Address, $validateAddress)) {
            echo "آدرس اصلی :‌ " . $Address . "<br/><br/><br/><br/><br/>";
            $Final_Address = $this->validateMethod1($Address);
            $response = $this->CallApi($Final_Address);
            if (!empty($response->result) && $response->result[0]->certainty > 70) {
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
            '/بانک.+?\-/' => 'خیابان',
            '/\/\d/' => '',
            '/\W{0,100}:/' => '',
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
        );
        $address = preg_replace(array_keys($keyWords), array_values($keyWords), $address);
        $founded = array();
        foreach ($keys as $key) {
            preg_match_all($key, $address, $match);
            $founded[] = implode("", $match[0]);
        }
        $final_address = implode("", $founded);
        $final_address = preg_replace('/خیابان کوچه/', 'کوچه', $final_address);
        $final_address = preg_replace('/خیابان خیابان/', 'خیابان', $final_address);

        $final_address = preg_replace('/-/', ' ', $final_address);
        $final_address = preg_replace('/\(.*?\)/', '', $final_address);
        if (strlen($final_address) > 10) {
            dd($final_address);
            return $final_address;
        } else {
            return false;
        }

    }


    public function MultiStrPos($haystack, $needles = array())
    {
        $chr = array();
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle);
            if ($res !== false) $chr[$needle] = $res;
        }
        if (empty($chr))
            return false;
        return ($chr);
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
