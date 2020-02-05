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
        $this->Address = Address::where('Status', 1)->get();

    }

    public function handle()
    {

        foreach ($this->Address as $key) {
            $this->check($key);
        }
    }

    public function check($address)
    {
        $Address = $address->Address;
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
            $FinalAddress = $this->validateAddress($Address);
            $response = $this->CallApi($FinalAddress);
            if (!empty($response->result) && $response->result[0]->certainty > 70){
                $address->FoundedAddress = $response->result[0]->title;
                $address->Status = 2;
                $address->save();
            }else{
                    $response = $this->LastTry($Address);
                    if ($response != null){
                        $address->FoundedAddress = $response;
                        $address->Status = 2;
                        $address->save();
                    }
                }

        } else {
            $response = $this->LastTry($Address);
            if ($response != null){
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
            $founded[] = implode("", $match[0]);
        }
        $final_address = implode("", $founded);
        $final_address = preg_replace('/-/', ' ', $final_address);
        $final_address = preg_replace('/\(.*?\)/' , '' , $final_address);
        if (strlen($final_address) > 10) {
            if ($founded[0] != null)
            $final_address =preg_replace('/^'.$founded[0].'?/','',$final_address);
            return $final_address;
        } else {
            return false;
        }

    }
    public function MultiStrPos($haystack, $needles=array()) {
        $chr = array();
        foreach($needles as $needle) {
            $res = strpos($haystack, $needle);
            if ($res !== false) $chr[$needle] = $res;
        }
        if(empty($chr))
            return false;
        return ($chr);
    }
    public function LastTry($Address)
    {
        $response = $this->CallApi($Address);
            if (!empty($response->result)) {
                return $response->result[0]->title;
            } else {
                return null;
            }
    }

}
