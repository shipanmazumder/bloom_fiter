<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Pleo\BloomFilter\BloomFilter;

class UserEmail implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(!Storage::exists("bloom.json"))
        {
            $approximateItemCount = 1000000;
            $falsePositiveProbability =0.00001;
            $bf = BloomFilter::init($approximateItemCount, $falsePositiveProbability);
            $serialized = json_encode($bf); // you can store/transfer this places!
            Storage::put("bloom.json",$serialized);
            unset($bf);
        }
        $file=Storage::get("bloom.json");
        $bf=BloomFilter::initFromJson(json_decode($file,true));
        return !$bf->exists($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Email Already Exits!';
    }
}
