<?php

namespace App\Actions\Agency;

use App\Models\Agency;

class UpdateAgencyAction
{
    public function execute(Agency $agency, array $data): Agency
    {
        $agency->update($data);

        return $agency;
    }
}

