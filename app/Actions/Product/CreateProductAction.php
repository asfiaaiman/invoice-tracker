<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function execute(array $data, array $agencyIds, array $agencyPrices = []): Product
    {
        return DB::transaction(function () use ($data, $agencyIds, $agencyPrices) {
            $product = Product::create($data);
            
            $syncData = [];
            foreach ($agencyIds as $agencyId) {
                $syncData[$agencyId] = [];
                if (isset($agencyPrices[$agencyId]) && $agencyPrices[$agencyId] !== null && $agencyPrices[$agencyId] !== '') {
                    $syncData[$agencyId]['price'] = $agencyPrices[$agencyId];
                } else {
                    $syncData[$agencyId]['price'] = null;
                }
            }
            
            $product->agencies()->sync($syncData);

            return $product->load('agencies');
        });
    }
}

