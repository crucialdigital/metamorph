<?php

namespace Database\Seeders;

use CrucialDigital\Metamorph\Models\CoreForm;
use CrucialDigital\Metamorph\Models\CoreFormInput;
use Illuminate\Database\Seeder;

class MetamorphSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $models = config('metamorph.path', []);
        foreach ($models as $model) {
            $data = $model;
            unset($data['inputs']);
            $form = CoreForm::updateOrCreate(['formType' => $model['formType'], 'readOnly' => true], $data);
            collect($model['inputs'])->each(function ($input) use ($form) {
                CoreFormInput::updateOrCreate([
                    'form_id' => $form->_id,
                    'field' => $input['field']
                ], $input);
            });
        }
    }
}
