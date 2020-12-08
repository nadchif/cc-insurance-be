<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('all_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date_insured')->nullable(false)->useCurrentOnUpdate();
            $table->bigInteger('entity')->unsigned()->nullable(false);
            $table->foreign('entity')->references('id')->on('entities');
            $table->string('erf');
            $table->string('address');
            $table->string('type')->nullable(false);
            $table->string('description')->nullable(false);
            $table->string('serial');
            $table->string('fnCT');
            $table->integer('value1617');
            $table->integer('value1718');
            $table->integer('value_current')->nullable(false);
            $table->string('account');
            $table->timestamp('last_updated')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('all_entries');
    }
}
