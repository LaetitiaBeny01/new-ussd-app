<?php
namespace App\Http\Controllers\USSD;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait MenuTrait{

    public function notes()
    {
        if (Cache::has('key')) {
            //
        }
        $value = Cache::get('key', 'default');

        $seconds = 100;
        Cache::put('key', 'value', $seconds);
        Cache::forever('key', 'value');
        Cache::forget('key');
    }

    public function welcomeMenu($error){
        $start  = "VIP Bootcamp registration closed.".PHP_EOL;
//        $start .= "What is your first name?".PHP_EOL;
        $this->ussd_stop($start);
//        $start = '';
//        if (!empty($error)) {
//            $start .=$error.PHP_EOL;
//            $start .= "What is your first name?".PHP_EOL;
//            $this->ussd_proceed($start);
//        }else
//        {
//            $start  = "Welcome! To sign-up for the Educate! VIP Bootcamp answer 10 questions.".PHP_EOL;
//            $start .= "What is your first name?".PHP_EOL;
//            $this->ussd_proceed($start);
//        }

    }

    public function enterLastName($error)
    {
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "What is your Last Name".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function enterGender($error)
    {
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "Are you a boy or girl?".PHP_EOL;
        $continue .= "1. Girl".PHP_EOL;
        $continue .= "2. Boy".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function confirmPhone($error,$name,$msisdn){
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "What phone number can we best reach you on {$name}?".PHP_EOL;
        $continue .= "1. This one {$msisdn}".PHP_EOL;
        $continue .= "2. A different number".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function enterPhone($error){
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "Enter phone number,Use format(07********):".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function chooseClass($error){
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "In the last term, what class were you?".PHP_EOL;
        $continue .= "1. S1-S3".PHP_EOL;
        $continue .= "2. S4".PHP_EOL;
        $continue .= "3. S5".PHP_EOL;
        $continue .= "4. S6".PHP_EOL;
        $continue .= "5. Secondary school leaver".PHP_EOL;
        $continue .= "6. not in school".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function enterAge($error){
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue .= "How old are you?".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function enterDistrict($error){
        $continue = '';
        if (!empty($error)) {
            $continue .=$error.PHP_EOL;
        }
        $continue  .= "Which district do you live in now?".PHP_EOL;
        $this->ussd_proceed($continue);
    }

    public function finish()
    {
        $end = "Thanks for signing up. Ask friends to signup. Be a VIP. We shall contact You soon".PHP_EOL;
        $this->ussd_stop($end);
    }
}
