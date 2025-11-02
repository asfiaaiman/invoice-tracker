<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function execute(Product $product, array $data, array $agencyIds): Product
    {
        return DB::transaction(function () use ($product, $data, $agencyIds) {
            $product->update($data);
            $product->agencies()->sync($agencyIds);

            return $product->load('agencies');
        });
    }
}

