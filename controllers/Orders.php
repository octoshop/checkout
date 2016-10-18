<?php namespace Octoshop\Checkout\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Octoshop\Checkout\Models\Order;

class Orders extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.RelationController',
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $relationConfig = 'config_relation.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Octoshop.Core', 'octoshop', 'orders');
    }

    public function index()
    {
        $this->vars['orderTotal'] = Order::count();

        $this->vars['statusCounts'] = \DB::table('octoshop_orders AS o')
                                    ->select(\DB::raw('s.name, s.colour, count(*) as count'))
                                    ->leftJoin('octoshop_order_statuses AS s', 's.id', '=', 'o.status_id')
                                    ->groupBy('s.name')
                                    ->orderBy('s.id', 'DESC')
                                    ->get();

        $this->vars['orderCountClass'] = $this->scoreboardClass(
            $this->vars['orderCountLast'] = Order::createdLastMonth()->count(),
            $this->vars['orderCount'] = Order::createdThisMonth()->count()
        );

        return $this->asExtension('ListController')->index();
    }

    public function scoreboardClass($oldVal, $newVal)
    {
        if ($newVal > $oldVal) {
            return 'positive';
        }

        if ($oldVal > $newVal) {
            return 'negative';
        }

        return '';
    }
}
