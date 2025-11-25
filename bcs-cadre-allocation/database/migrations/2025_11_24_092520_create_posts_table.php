<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('cadre_code');
            $table->integer('total_post')->default(0);
            $table->integer('total_post_left')->default(0);
            $table->integer('mq_post')->default(0);
            $table->integer('mq_post_left')->default(0);
            $table->integer('cff_post')->default(0);
            $table->integer('cff_post_left')->default(0);
            $table->integer('em_post')->default(0);
            $table->integer('em_post_left')->default(0);
            $table->integer('phc_post')->default(0);
            $table->integer('phc_post_left')->default(0);
            $table->integer('allocated_post_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
