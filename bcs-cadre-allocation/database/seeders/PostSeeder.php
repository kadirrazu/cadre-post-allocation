<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('posts')->insert(
            [
                [
                    'cadre_code' => 110, //ADMN
                    'total_post' => 3, 
                    'total_post_left' => 3, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 1, 
                    'cff_post_left' => 1, 
                    'em_post' => 0,  
                    'em_post_left' => 0,  
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 115, //FORN
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 117, //PLIC
                    'total_post' => 3, 
                    'total_post_left' => 3, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 1, 
                    'em_post_left' => 1, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 118, //ANSR
                    'total_post' => 1, 
                    'total_post_left' => 1, 
                    'mq_post' => 1, 
                    'mq_post_left' => 1, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 112, //AUDT
                    'total_post' => 4, 
                    'total_post_left' => 4, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 1, 
                    'cff_post_left' => 1, 
                    'em_post' => 1, 
                    'em_post_left' => 1, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 114, //TAXN
                    'total_post' => 3, 
                    'total_post_left' => 3, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 1, 
                    'phc_post_left' => 1, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ]
        ]);

        DB::table('posts')->insert(
            [
                [
                    'cadre_code' => 351, //RLME
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 1, 
                    'mq_post_left' => 1, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 1, 
                    'phc_post_left' => 1, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 331, //ROCE
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 227, //AGEX
                    'total_post' => 5, 
                    'total_post_left' => 5, 
                    'mq_post' => 3, 
                    'mq_post_left' => 3, 
                    'cff_post' => 1, 
                    'cff_post_left' => 1, 
                    'em_post' => 1, 
                    'em_post_left' => 1, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 410, //MEDI
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 311, //PWCE
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 332, //ROME
                    'total_post' => 2, 
                    'total_post_left' => 2, 
                    'mq_post' => 2, 
                    'mq_post_left' => 2, 
                    'cff_post' => 0, 
                    'cff_post_left' => 0, 
                    'em_post' => 0, 
                    'em_post_left' => 0, 
                    'phc_post' => 0, 
                    'phc_post_left' => 0, 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
        ]);
    }
}
