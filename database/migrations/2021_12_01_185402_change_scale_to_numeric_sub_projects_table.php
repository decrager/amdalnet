<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeScaleToNumericSubProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            if (Schema::hasColumn('scale', 'sub_projects')) {
                $table->decimal('scale', 20,2)->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sub_projects', function (Blueprint $table) {
            //
        });
    }
}
