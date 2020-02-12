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
    public function __construct($Address)
    {
        $this->Address = $Address;
    }
    public function handle()
    {
            $this->check($this->Address);
    }
    public function check($address)
    {
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
            '/بن بست/',
            '/نرسیده به/',
            '/بعد از/',
            '/بالاتر از/',
            '/پ\d{1,4}/',
        );
        $Valideted = 0;
        foreach ($keyWords as $key) {
            $Valideted .= preg_match($key, $Address);
        }
        if ($Valideted > 0) {
            $FinalAddress = $this->validateAddress($Address);
            $response = $this->CallApi($FinalAddress);
            if (!empty($response->result) && $response->result[0]->certainty >= 70) {
                $address->FoundedAddress = $response->result[0]->title;
                $address->Status = 2;
                $address->save();
            } else {
                $response = $this->LastTry($Address);
                if ($response != null) {
                    $address->FoundedAddress = $response;
                    $address->Status = 2;
                    $address->save();
                }
            }

        }else {
            $response = $this->LastTry($Address);
            if ($response != null) {
                $address->FoundedAddress = $response;
                $address->Status = 2;
                $address->save();
            }
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
    public function validateAddress($address)
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
            '/بن بست/' => 'کوچه',
            '/بین/' => 'خیابان',
            '/نرسیده به/' => 'خیابان',
            '/بعد از/' => 'خیابان',
            '/بالاتر از/' => 'خیابان',
            '/ابتدای/' => 'خیابان',
            '/بانک.+?\-/' => 'خیابان',
            '/\/\d/' => '',
            '/\W{0,100}:/' => '',
            '/\n/' => '',
            '/\(.*?\)/' => '',
            '/الله/u' => '',

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
        $replace = array(
            '/خیابان کوچه/' => 'کوچه',
            '/خیابان خیابان/' => 'خیابان',
            '/خیابانساختمان/' => 'ساختمان',
            '/خیابن/' => 'خیابان',
            '/خیابان بزرگراه/' => 'بزرگراه',
            '/خیابان ایستگاه/' => 'ایستگاه'
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
    public function LastTry($Address)
    {
        if (strpos($Address, 'پلاک'))
            $Address = substr($Address, 0, strpos($Address, "پلاک"));
        $response = $this->CallApi($Address);
        if (!empty($response->result) && $response->result[0]->certainty >= 50) {
            return $response->result[0]->title;
        } else {
            return null;
        }
    }
}
