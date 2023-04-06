<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurgePolicyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purging_policies', function (Blueprint $table) {
            $table->id();
            $table->string('table')->unique();
            $table->integer('days_to_live');
            $table->boolean('write_to_file')->default(false);
            $table->string('archive_table')->nullable();
            $table->date('last_purged_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purge_policies');
    }
}