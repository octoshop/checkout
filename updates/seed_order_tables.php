<?php namespace Octoshop\Checkout\Updates;

use Seeder;
use Octoshop\Checkout\Models\OrderStatus;

class SeedOrderTables extends Seeder
{
    protected $statuses = [
        'Draft' => '#ccc',
        'Abandoned' => '#666',
        'Pending' => '#e5a91a',
        'Complete' => '#95b753',
        'Cancelled' => '#c30',
    ];

    public function run()
    {
        foreach ($this->statuses as $name => $colour) {
            OrderStatus::create(compact(['name', 'colour']));
        }
    }
}
