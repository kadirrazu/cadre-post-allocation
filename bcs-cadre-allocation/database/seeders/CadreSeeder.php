<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class CadreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cadres')->insert(
            [
                [
                    'cadre_code' => 110, 
                    'cadre_abbr' => 'ADMN', 
                    'cadre_name' => 'BCS (Administration)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 115, 
                    'cadre_abbr' => 'FORN', 
                    'cadre_name' => 'BCS (Foreign Affairs)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 117, 
                    'cadre_abbr' => 'PLIC', 
                    'cadre_name' => 'BCS (Police)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 118, 
                    'cadre_abbr' => 'ANSR', 
                    'cadre_name' => 'BCS (Ansar)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 112, 
                    'cadre_abbr' => 'AUDT', 
                    'cadre_name' => 'BCS (Audit & Accounts)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 114, 
                    'cadre_abbr' => 'TAXN', 
                    'cadre_name' => 'BCS (Taxation)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 119, 
                    'cadre_abbr' => 'COPG', 
                    'cadre_name' => 'BCS (Co-operative)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 125, 
                    'cadre_abbr' => 'RLGG', 
                    'cadre_name' => 'BCS (Railway Transportion & Commercial)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 121, 
                    'cadre_abbr' => 'INAD', 
                    'cadre_name' => 'BCS (Information) [Assistant Director/Information Officer/Research Officer])', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 122, 
                    'cadre_abbr' => 'INPO', 
                    'cadre_name' => 'BCS (Information) [Assistant Director (Programme)]', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 123, 
                    'cadre_abbr' => 'INNW', 
                    'cadre_name' => 'BCS (Information) [Ast. Controller of News]', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 116, 
                    'cadre_abbr' => 'POST', 
                    'cadre_name' => 'BCS (Postal) [General Post]', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 120, 
                    'cadre_abbr' => 'TRDG', 
                    'cadre_name' => 'BCS (Trade) [General Post]', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 124, 
                    'cadre_abbr' => 'FAML', 
                    'cadre_name' => 'BCS (Family Planning)', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 111, 
                    'cadre_abbr' => 'FODG', 
                    'cadre_name' => 'BCS (Food) [General Post]', 
                    'cadre_type' => 'GG', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
        ]);

        DB::table('cadres')->insert(
            [
                [
                    'cadre_code' => 353, 
                    'cadre_abbr' => 'RLNP', 
                    'cadre_name' => 'BCS (Railway Engineering) [Assistant Executive Engineer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 351, 
                    'cadre_abbr' => 'RLME', 
                    'cadre_name' => 'BCS (Railway Engineering) [Assistant Mechanical Engineer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 354, 
                    'cadre_abbr' => 'RLSE', 
                    'cadre_name' => 'BCS (Railway Engineer) [Ast. Signal & Tele. Comm. Engineer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 352, 
                    'cadre_abbr' => 'RLCE', 
                    'cadre_name' => 'BCS (Railway Engineer) [Ast. Controller of Machinery]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 355, 
                    'cadre_abbr' => 'RLEE', 
                    'cadre_name' => 'BCS (Railway Engineer) [Ast. Electrical Engineer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 530, 
                    'cadre_abbr' => 'INEN', 
                    'cadre_name' => 'BCS (Information) [Assistant Radio Engineer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 550, 
                    'cadre_abbr' => 'FORT', 
                    'cadre_name' => 'BCS (Forests) [Assistant Conservator of Forests]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 331, 
                    'cadre_abbr' => 'ROCE', 
                    'cadre_name' => 'BCS (Roads & Highways) [Assistant Engineer (Civil)]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 332, 
                    'cadre_abbr' => 'ROME', 
                    'cadre_name' => 'BCS (Roads & Highways) [Assistant Engineer (Mechanical)]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 240, 
                    'cadre_abbr' => 'FITO', 
                    'cadre_name' => 'BCS (Fisheries) [Upazilla Fisheries Officer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 270, 
                    'cadre_abbr' => 'VLSO', 
                    'cadre_name' => 'BCS (Livestock) [Veterinary Surgeon / SO / Thana Livestock Officer / Lecturer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 281, 
                    'cadre_abbr' => 'LPDO', 
                    'cadre_name' => 'BCS (Livestock) [PDO / APO / Zoo Officer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 227, 
                    'cadre_abbr' => 'AGEX', 
                    'cadre_name' => 'BCS (Agriculture) [Extension  Officer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 226, 
                    'cadre_abbr' => 'AGSO', 
                    'cadre_name' => 'BCS (Agriculture) [Scientific Officer]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 410, 
                    'cadre_abbr' => 'MEDI', 
                    'cadre_name' => 'BCS (Health) [Assistant Surgeon]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 450, 
                    'cadre_abbr' => 'DENT', 
                    'cadre_name' => 'BCS (Health Dentral) [Assistant Dental Surgeon]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 311, 
                    'cadre_abbr' => 'PWCE', 
                    'cadre_name' => 'BCS (Public Works) [Assistant Engineer (Civil)]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
                [
                    'cadre_code' => 312, 
                    'cadre_abbr' => 'PWEM', 
                    'cadre_name' => 'BCS (Public Works) [Assistant Engineer (E/M)]', 
                    'cadre_type' => 'TT', 
                    'subject_requirements' => '{}', 
                    'created_at' => Carbon::now(), 
                    'updated_at' => Carbon::now() 
                ],
        ]);
    }
}
