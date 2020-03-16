Firstly See This
[Bloom Filter](https://github.com/pleonasm/bloom-filter)

Let’s run a command:

php artisan make:rule UserEmail
Laravel generates a file app/Rules/UserEmail.php:

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class UserEmail implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}

As I said, it’s similar to Requests classes for validation. We fill in the methods. passes() should return true/false depending on $value condition, which is this in our case:

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
Next, we fill in the error message to be this:

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Email Already Exits!';
    }
    
How we use this class? In Register Users validator() method we have this code:

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255',new UserEmail()],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
   Finally, how we use this class? In controller's register() method we have this code:
   
     /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();
        event(new Registered($user = $this->create($request->all())));
        $file=Storage::get("bloom.json");
        $bf=BloomFilter::initFromJson(json_decode($file,true));
        $bf->add($user->email);
        $serialized = json_encode($bf);
        Storage::put("bloom.json",$serialized);
        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson()
                    ? new Response('', 201)
                    : redirect($this->redirectPath());
    }
