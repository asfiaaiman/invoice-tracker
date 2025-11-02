<?php

namespace App\Actions\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function execute(array $data, array $agencyIds): Product
    {
        return DB::transaction(function () use ($data, $agencyIds) {
            $product = Product::create($data);
            $product->agencies()->sync($agencyIds);

            return $product->load('agencies');
        });
    }
}

