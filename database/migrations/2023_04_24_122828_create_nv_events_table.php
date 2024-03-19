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
        Schema::create('nv_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->foreign('lead_id')->references('id')->on('nv_leads');
            $table->unsignedBigInteger('created_by')->comment("Team Member's ID.");
            $table->foreign('created_by')->references('id')->on('team_members');
            $table->string('event_name')->nullable();
            $table->dateTime('event_datetime')->nullable();
            $table->smallInteger('pax')->nullable()->comment("Number of guest");
            $table->string('event_slot')->nullable();
            $table->string('venue_name')->nullable();
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
        Schema::dropIfExists('nv_events');
    }
};
