<?php namespace Octoshop\Checkout\Models;

use Carbon\Carbon;
use Model;

class OrderItem extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'octoshop_order_items';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['basket_row_id', 'name', 'quantity', 'price', 'subtotal'];

    /**
     * @var array Validation rules
     */
    protected $rules = [
        'name' => ['required', 'between:4,255'],
        'quantity' => ['required', 'numeric', 'between:1,1000000'],
        'price' => ['required', 'numeric', 'between:0,1000000'],
        'subtotal' => ['required', 'numeric', 'between:0,1000000'],
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'order' => 'Octoshop\Checkout\Models\Order',
    ];
}
