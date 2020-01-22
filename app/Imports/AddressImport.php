<?php

namespace App\Imports;

use App\Address;
use App\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
class AddressImport implements ToModel
{

    public function model(array $row)
    {
        return new Address([
            'Address'  => $row[0],
            'Status' => 1,
        ]);

    }
}
