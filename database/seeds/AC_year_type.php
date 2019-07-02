<?php

use Illuminate\Database\Seeder;

class AC_year_type extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Types=[
            [
                'name'=>"B",
                'segment_no'=>'1',
                'Years'=>[
                    [
                        'name' =>"2015"
                    ],
                    [
                        'name' =>"2016"
                    ],
                ]
            ],
            [
                'name'=> "A",
                'segment_no'=>'2',

                'Years'=>[
                    [
                        'name' =>"2019"
                    ],
                    [
                        'name' =>"2020"
                    ],
                ]
            ],
        ];

        foreach ($Types as $cat) {
            $newCat = \App\AcademicType::create(['name' => $cat['name'],'segment_no'=>$cat['segment_no']]);
            foreach ($cat['Years'] as $Year) {
                $newCat->AC_year()->create($Year);
            }
        }

    }
}
