<?php

namespace CrucialDigital\Metamorph\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class GeoPointRule implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        $coords = explode(',', $value);
        if (count($coords) < 2) {
            $fail('The :attribute field must be a valide geo coordinates');
        }
        $lat = (float)$coords[0];
        $long = (float)$coords[1];
        if ($lat > 90 || $lat < -90 || $long > 180 || $long < -180) {
            $fail('The :attribute field must be a valide geo coordinates');
        }
    }
}
