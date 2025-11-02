<?php

namespace App\Actions\Client;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class UpdateClientAction
{
    public function execute(Client $client, array $data, array $agencyIds): Client
    {
        return DB::transaction(function () use ($client, $data, $agencyIds) {
            $client->update($data);
            $client->agencies()->sync($agencyIds);

            return $client->load('agencies');
        });
    }
}

