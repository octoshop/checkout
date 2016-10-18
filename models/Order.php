<?php namespace Octoshop\Checkout\Models;

use Model;

class Order extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'octoshop_orders';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'items' => 'Octoshop\Checkout\Models\OrderItem',
    ];
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
    ];

    public function beforeCreate()
    {
        $this->hash = str_random(36);
    }

    public static function findByHash($hash)
    {
        if (!$hash) {
            return;
        }

        return (new static())->whereHash($hash)->first();
    }
}
