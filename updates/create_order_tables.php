<?php namespace Octoshop\Checkout\Updates;

use Schema;
use Octoshop\Core\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;

class CreateOrderTables extends Migration
{
    public function up()
    {
        Schema::create('octoshop_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('hash', 36)->unique();
            $table->integer('user_id')->unsigned();
            $this->addAddressCols($table, 'billing');
            $this->addAddressCols($table, 'shipping');
            $table->timestamps();
        });
    }

    protected function addAddressCols(&$table, $type)
    {
        $table->string($type.'_name')->nullable();
        $table->string($type.'_company')->nullable();
        $table->string($type.'_line1')->nullable();
        $table->string($type.'_line2')->nullable();
        $table->string($type.'_town')->nullable();
        $table->string($type.'_region')->nullable();
        $table->string($type.'_postcode')->nullable();
        $table->string($type.'_country')->nullable();
    }

    public function down()
    {
        Schema::dropIfExists('octoshop_orders');
    }
}
