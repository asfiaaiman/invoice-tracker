<?php

namespace App\Actions\Client;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class CreateClientAction
{
    public function execute(array $data, array $agencyIds): Client
    {
        return DB::transaction(function () use ($data, $agencyIds) {
            $client = Client::create($data);
            $client->agencies()->sync($agencyIds);

            return $client->load('agencies');
        });
    }
}

