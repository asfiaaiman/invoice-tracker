<?php

namespace Database\Seeders;

use App\Actions\Invoice\CreateInvoiceAction;
use App\Actions\Invoice\GenerateInvoiceNumberAction;
use App\DTOs\InvoiceDTO;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InvoiceTrackerSeeder extends Seeder
{
    public function run(): void
    {
        $createInvoiceAction = app(CreateInvoiceAction::class);
        $generateInvoiceNumberAction = app(GenerateInvoiceNumberAction::class);

        $agency1 = Agency::create([
            'name' => 'Digital Solutions Agency',
            'tax_id' => '105123456',
            'address' => 'Kneza Mihaila 15',
            'city' => 'Belgrade',
            'zip_code' => '11000',
            'country' => 'Serbia',
        ]);

        $agency2 = Agency::create([
            'name' => 'Creative Works Studio',
            'tax_id' => '105789012',
            'address' => 'Nemanjina 28',
            'city' => 'Belgrade',
            'zip_code' => '11000',
            'country' => 'Serbia',
        ]);

        $agency3 = Agency::create([
            'name' => 'Tech Innovations Ltd',
            'tax_id' => '105345678',
            'address' => 'Terazije 27',
            'city' => 'Belgrade',
            'zip_code' => '11000',
            'country' => 'Serbia',
        ]);

        $clients = [];

        for ($i = 1; $i <= 6; $i++) {
            $clients[$i] = Client::create([
                'name' => "Client Company {$i}",
                'tax_id' => "200{$i}123456",
                'address' => "Client Street {$i}",
                'city' => 'Belgrade',
                'zip_code' => '11000',
                'country' => 'Serbia',
                'email' => "client{$i}@example.com",
                'phone' => "+381 11 {$i}23 456",
            ]);
        }

        $agency1->clients()->attach([$clients[1]->id, $clients[2]->id, $clients[3]->id, $clients[4]->id, $clients[5]->id]);
        $agency2->clients()->attach([$clients[2]->id, $clients[3]->id, $clients[4]->id, $clients[5]->id, $clients[6]->id]);
        $agency3->clients()->attach([$clients[1]->id, $clients[3]->id, $clients[4]->id, $clients[5]->id, $clients[6]->id]);

        $products = [];

        $productNames = ['Web Development', 'Graphic Design', 'Marketing Services', 'Consulting', 'Software License'];
        foreach ($productNames as $index => $name) {
            $products[] = Product::create([
                'name' => $name,
                'description' => "Description for {$name}",
                'price' => (($index + 1) * 50000),
                'unit' => 'hour',
            ]);
        }

        foreach ([$agency1, $agency2, $agency3] as $agency) {
            foreach ($products as $product) {
                $agency->products()->attach($product->id, ['price' => $product->price]);
            }
        }

        $invoiceData = [
            [
                'agency_id' => $agency1->id,
                'client_id' => $clients[1]->id,
                'issue_date' => now()->subMonths(2)->format('Y-m-d'),
                'due_date' => now()->subMonths(1)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $products[0]->id,
                        'quantity' => 40,
                        'unit_price' => $products[0]->price,
                    ],
                    [
                        'product_id' => $products[1]->id,
                        'quantity' => 20,
                        'unit_price' => $products[1]->price,
                    ],
                ],
            ],
            [
                'agency_id' => $agency1->id,
                'client_id' => $clients[2]->id,
                'issue_date' => now()->subMonths(1)->format('Y-m-d'),
                'due_date' => now()->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $products[2]->id,
                        'quantity' => 30,
                        'unit_price' => $products[2]->price,
                    ],
                ],
            ],
            [
                'agency_id' => $agency2->id,
                'client_id' => $clients[3]->id,
                'issue_date' => now()->subDays(15)->format('Y-m-d'),
                'due_date' => now()->addDays(15)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $products[0]->id,
                        'quantity' => 50,
                        'unit_price' => $products[0]->price,
                    ],
                    [
                        'product_id' => $products[3]->id,
                        'quantity' => 25,
                        'unit_price' => $products[3]->price,
                    ],
                ],
            ],
            [
                'agency_id' => $agency2->id,
                'client_id' => $clients[4]->id,
                'issue_date' => now()->subDays(30)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $products[4]->id,
                        'quantity' => 1,
                        'unit_price' => 200000,
                    ],
                ],
            ],
            [
                'agency_id' => $agency3->id,
                'client_id' => $clients[5]->id,
                'issue_date' => now()->subDays(10)->format('Y-m-d'),
                'items' => [
                    [
                        'product_id' => $products[1]->id,
                        'quantity' => 35,
                        'unit_price' => $products[1]->price,
                    ],
                    [
                        'product_id' => $products[2]->id,
                        'quantity' => 15,
                        'unit_price' => $products[2]->price,
                    ],
                ],
            ],
        ];

        foreach ($invoiceData as $data) {
            $data['invoice_number'] = $generateInvoiceNumberAction->execute($data['agency_id']);
            $dto = InvoiceDTO::fromArray($data);
            $createInvoiceAction->execute($dto);
        }
    }
}

