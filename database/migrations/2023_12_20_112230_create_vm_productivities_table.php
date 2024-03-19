<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('vm_productivities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->comment('VM id');
            $table->foreign('team_id')->references('id')->on('team_members');
            $table->smallInteger('wb_recce_target');
            $table->date('date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('vm_productivities');
    }
};
