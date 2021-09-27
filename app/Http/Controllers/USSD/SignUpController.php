<?php

namespace App\Http\Controllers\USSD;

use App\Http\Controllers\Controller;
use App\Models\SignUp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SignUpController extends Controller
{
    use MenuTrait;
    public function ussdRequestHandler(Request $request)
    {
        $sessionId   = $request["sessionId"];
        $serviceCode = $request["serviceCode"];
        $phone       = '256' . substr($request["phoneNumber"], -9);
        $text        = $request["text"];

        header('Content-type: text/plain');

        $this->startSession($text,$phone);
    }

    public function startSession($ussd_string, $phone)
    {
        $input = $this->getInput($ussd_string,$phone);

        $level = $input['level'];

        if(empty($ussd_string) or $level == 0) {
            //$user = SignUp::where('MSISDN', '=', '256' . substr($phone, -9))->first();
            if ($input['status']==1)
            {
                $this->ussd_proceed('Are you '.$input['first_name'].' '.$input['last_name'].'?'.PHP_EOL.'1.Yes'.PHP_EOL.'2. No');
            }else{
                if (!empty($input['registration']) && $level == 2)
                {
                    $this->ussd_stop('VIP already registered. We shall contact You soon.');
                    exit();
                }elseif ($input['registration_status']=='registered')
                {
                    $this->ussd_stop('VIP already registered. We shall contact You soon.');
                    exit();
                }
                else
                {
                    $this->welcomeMenu($input['error']); // main menu
                }

            }
        }elseif($level >= 2)
        {
            $this->register($phone,$input);
        }

        switch ($level) {
            case 1:
                $this->enterLastName($input['error']);
                break;
            case 2:
                $this->enterGender($input['error']);
                break;
            case 3:
                $this->confirmPhone($input['error'],$input['exploded_text'][0],$phone);
                break;
            case 4:
                if ($input['phone'] == 2){
                    $this->enterPhone($input['error']);
                }else
                {
                    $this->chooseClass($input['error']);
                }

                break;
            case 5:
                if ($input['phone'] == 2){
                    $this->chooseClass($input['error']);
                }else
                {
                    $this->enterAge($input['error']);
                }

                break;
            case 6:
                if ($input['phone'] == 2){
                    $this->enterAge($input['error']);
                }else
                {
                    $this->enterDistrict($input['error']);
                }
                break;
            case 7:
                if ($input['phone'] == 2){
                    $this->enterDistrict($input['error']);
                }else
                {
                    if (!empty($input['registration']))
                    {
                        $end = "VIP already registered. We shall contact You soon.".PHP_EOL;
                    }else
                    {
                        $end = "Thanks fo signing up. Ask friends to signup. Be a VIP.We shall contact You soon".PHP_EOL;
                    }
                    $this->ussd_stop($end);
                }
                break;
            case 8:
                //$this->register($phone,$input);
                if (!empty($input['registration']))
                {
                    $end = "VIP already registered. We shall contact You soon.".PHP_EOL;
                }else
                {
                    $end = "Thanks fo signing up. Ask friends to signup. Be a VIP.We shall contact You soon".PHP_EOL;
                }
                $this->ussd_stop($end);
                break;
        }
    }

    public function register($phone,$input){
        $status = 'pending';
        if(!empty($input['district']))
        {
            $status = 'registered';
        }
        $user = SignUp::where('MSISDN',$phone)->where('first_name',$input['fname'])->where('last_name',$input['lname'])->first();
        if ($user)
        {
            $user->phone_choice = $input['phone']==''?0:$input['phone'];
            $user->first_name=$input['fname'];
            $user->last_name =$input['lname'];
            $user->gender =$input['gender'];
            $user->MSISDN ='256'.substr($phone,-9);
            $user->phone_number =$input['phone_number'];
            $user->class =$input['class'];
            $user->age =$input['age']==''?0:$input['age'];
            $user->district =$input['district'];
            $user->district_actual =$input['district'];
            $user->status =$status;
            $user->ussd_current_level =$input['level'];
            $user->ussd_string =json_encode($input['exploded_text']);
            $user->save();
        }

    }

    protected function getInput($text,$phone)
    {
        $input = [];
        if (empty($text)) {
            $input['level'] = 0;
            $input['message'] = "";
            $input['error'] ='';
            $input['status'] = '';
            $input['registration_status'] = 'pending';
            Cache::flush();
            $user = SignUp::where('MSISDN', '=', '256' . substr($phone, -9))->orderBy('created_at', 'desc')->first();

            if($user)
            {
                $input['status'] = 1;
                $input['registration_status'] = $user->status;
                $input['first_name'] = $user->first_name;
                $input['last_name'] = $user->last_name;
                Cache::put($phone.'-user',$user,now()->addMinutes(10));
                Cache::put($phone.'-registration',1,now()->addMinutes(10));

            }
            Cache::put($phone.'-level',0,now()->addMinutes(10));
        } else {
            $exploded_text = explode('*', $text);
            $input['exploded_text'] = $exploded_text;
            $input['language'] = "en";
            $input['error'] = '';
            //$exploded_text = Cache::get($phone.'-exploded_string');
            $input['level'] = Cache::get($phone . '-level');
            $input['message'] = end($exploded_text);
            $last_item = end($exploded_text);
            $level = Cache::get($phone . '-level');

            $input['phone_number'] = Cache::get($phone . '-phone_number');
            $input['age'] = Cache::get($phone . '-age');
            $input['class'] = Cache::get($phone . '-class');
            $input['gender'] = Cache::get($phone . '-gender');
            $input['phone'] = Cache::get($phone . '-phone');
            $input['fname'] = Cache::get($phone . '-fname');
            $input['lname'] = Cache::get($phone . '-lname');
            $input['district'] = Cache::get($phone . '-district');
            $input['status'] = '';
            $input['registration'] = '';
            $input['registration_status'] = 'pending';

            if (Cache::get($phone . '-registration') == 1 && $exploded_text[0] == 1) {
                $user = Cache::get($phone . '-user');
                Cache::put($phone . '-phone_number', $phone, now()->addMinutes(10));
                Cache::put($phone . '-fname', $user->first_name == null ? '' : $user->first_name, now()->addMinutes(10));
                Cache::put($phone . '-phone', $user->phone_choice == null ? '' : $user->phone_choice, now()->addMinutes(10));
                Cache::put($phone . '-lname', $user->last_name == null ? '' : $user->last_name, now()->addMinutes(10));
                Cache::put($phone . '-level', $user->ussd_current_level, now()->addMinutes(10));
                Cache::put($phone . '-age', $user->age == null ? '' : $user->age, now()->addMinutes(10));
                Cache::put($phone . '-class', $user->class == null ? '' : $user->class, now()->addMinutes(10));
                Cache::put($phone . '-district', $user->district == null ? '' : $user->district, now()->addMinutes(10));
                Cache::put($phone . '-gender', $user->gender == null ? '' : $user->gender, now()->addMinutes(10));
                $input['exploded_text'] = $user->ussd_string;
                $exploded_text = json_decode($user->ussd_string);
                $input['level'] = $user->ussd_current_level;
                $input['message'] = end($exploded_text);
                $last_item = end($exploded_text);
                $level = $user->ussd_current_level;
                $input['phone_number'] = $user->MSISDN == null ? '' : $user->MSISDN;
                $input['age'] = $user->age == null ? 0 : $user->age;
                $input['class'] = $user->class == null ? '' : $user->class;
                $input['gender'] = $user->gender == null ? '' : $user->gender;
                $input['phone'] = $user->phone_choice == null ? '' : $user->phone_choice;
                $input['fname'] = $user->first_name == null ? '' : $user->first_name;
                $input['lname'] = $user->last_name == null ? '' : $user->last_name;
                $input['district'] = $user->district == null ? '' : $user->district;
                $input['registration'] = 1;
                $input['registration_status'] = $user->status;
                //Cache::put($phone . '-registration', 0, now()->addMinutes(10));
                array_pop($exploded_text);
                $exploded_text = array_values($exploded_text);
            } elseif (Cache::get($phone . '-registration') == 1 && $exploded_text[0] == 2) {
                $user = Cache::get($phone . '-user');
                $input['registration'] = 2;
                $input['registration_status'] = 'pending';//$user->status;
                //Cache::put($phone.'-level',0,now()->addMinutes(10));
                $input['level'] = 0;//Cache::get($phone . '-level');
                //Cache::put($phone . '-registration', 0, now()->addMinutes(10));
                array_pop($exploded_text);
                //Cache::put($phone.'-registration',0,now()->addMinutes(10));
                //$this->ussd_stop('Thank you, We shall contact you.');
                $exploded_text = array_values($exploded_text);
            }

            if (Cache::get($phone . '-registration') != 1 && empty($input['fname']) && $level == 0) {

                $name = trim($last_item);

                $input['error'] = '';
                if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
                    //unset($exploded_text[0]);
                    //array_pop($exploded_text);
                    $input['error'] = "First Name must not contain numbers, try again!";
                    Cache::forget($phone . '-fname');
                } elseif ($name == trim($name) && strpos($name, ' ') !== false) {
                    //array_pop($exploded_text);
                    $input['error'] = "First Name field must not contain spaces!";
                    Cache::forget($phone . '-fname');
                } else {
                    Cache::put($phone . '-level', 1, now()->addMinutes(10));
                    Cache::put($phone . '-fname', $name, now()->addMinutes(10));
                }
                $exploded_text = array_values($exploded_text);
            }

            //$check = !empty(Cache::get($phone . '-registration')) && count($exploded_text)> 1;
            if(Cache::get($phone . '-registration') == 0)
            {
            if (!empty($input['fname']) && $level == 1) {
                $name = trim($last_item);
                $input['error'] = '';
                if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
                    //array_pop($exploded_text);
                    $input['error'] = "Last Name must not contain numbers, try again!";
                    Cache::forget($phone . '-lname');
                    Cache::put($phone . '-level', 1, now()->addMinutes(10));
                } elseif ($name == trim($name) && strpos($name, ' ') !== false) {
                    //array_pop($exploded_text);
                    $input['error'] = "Last Name field must not contain spaces!";
                    Cache::forget($phone . '-lname');
                    Cache::put($phone . '-level', 1, now()->addMinutes(10));
                } else {
                    Cache::put($phone . '-level', 2, now()->addMinutes(10));
                    Cache::put($phone . '-lname', $name, now()->addMinutes(10));

                    $user = new SignUp();
                    $user->phone_choice = 0;
                    $user->first_name = $input['fname'];
                    $user->last_name = $name;
                    $user->MSISDN = '256' . substr($phone, -9);
                    $user->phone_number = '256' . substr($phone, -9);
                    $user->status = "incomplete";
                    $user->ussd_current_level = $input['level'];
                    $user->ussd_string = json_encode([]);
                    $user->save();
                }
                $exploded_text = array_values($exploded_text);
            }

            if (!empty($input['lname']) && $level == 2) {
                if ($last_item == 1) {
                    $gender = "Girl";
                    $input['error'] = '';
                    $input['gender'] = $gender;
                    Cache::put($phone . '-level', 3, now()->addMinutes(10));
                    Cache::put($phone . '-gender', $gender, now()->addMinutes(10));
                } elseif ($last_item == 2) {
                    $gender = "Boy";
                    $input['error'] = '';
                    $input['gender'] = $gender;
                    Cache::put($phone . '-level', 3, now()->addMinutes(10));
                    Cache::put($phone . '-gender', $gender, now()->addMinutes(10));
                } else {
                    $gender = "";
                    //array_pop($exploded_text);
                    Cache::forget($phone . '-gender');
                    $input['error'] = "You've entered a wrong value!";
                }

                $exploded_text = array_values($exploded_text);

            }

            if (!empty($input['gender']) && $level == 3) {
                $input['error'] = '';
                $myarray = [1, 2];
                if (!in_array($last_item, $myarray)) {
                    //array_pop($exploded_text);
                    $input['error'] = "You've entered a wrong value!";
                    Cache::forget($phone . '-phone');
                } else {
                    Cache::put($phone . '-level', 4, now()->addMinutes(10));
                    Cache::put($phone . '-phone', $last_item, now()->addMinutes(10));
                }
                $exploded_text = array_values($exploded_text);
            }

            //$this->ddd($level);
            if (!empty($input['phone']) && $level == 4 && empty($input['phone_number'])) {
                if ($input['phone'] == "2") {
                    if (!is_numeric($last_item) || strlen($last_item) > 10) {
                        //array_pop($exploded_text);
                        $input['error'] = "Invalid phone number, Use format(07********) and try again!";
                        Cache::forget($phone . '-phone_number');
                    } else {
                        if (!preg_match('/^(0)[1-9]\d{8}$/', $last_item)) {
                            //array_pop($exploded_text);
                            $input['error'] = "Invalid phone number, Use format(07********) and try again!";
                            Cache::forget($phone . '-phone_number');

                        } else {
                            Cache::put($phone . '-level', 5, now()->addMinutes(10));
                            Cache::put($phone . '-phone_number', '256' . substr($last_item, -9), now()->addMinutes(10));
                        }
                    }
                    $exploded_text = array_values($exploded_text);
                } else {
                    Cache::put($phone . '-level', 5, now()->addMinutes(10));
                    Cache::put($phone . '-phone_number', '256' . substr($phone, -9), now()->addMinutes(10));
                }
            }

            if (!empty($input['phone'])) {
                if ($input['phone'] == "2" && $level == 5) {
                    $input['error'] = '';
                    if ($last_item == 1) {
                        $class = "S1-S3";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 2) {
                        $class = "S4";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 3) {
                        $class = "S5";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 4) {
                        $class = "S6";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 5) {
                        $class = "Secondary school leaver";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 6) {
                        $class = "Not in school";
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                        $input['error'] = '';
                    } else {
                        $class = "";
                        //array_pop($exploded_text);
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::forget($phone . '-class');
                        $input['error'] = "You've entered a wrong value!";
                    }
                } elseif ($input['phone'] == "1" && $level == 4) {
                    $input['error'] = '';
                    if ($last_item == 1) {
                        $class = "S1-S3";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 2) {
                        $class = "S4";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 3) {
                        $class = "S5";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 4) {
                        $class = "S6";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 5) {
                        $class = "Secondary school leaver";
                        $input['error'] = '';
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                    } elseif ($last_item == 6) {
                        $class = "Not in school";
                        Cache::put($phone . '-level', 5, now()->addMinutes(10));
                        Cache::put($phone . '-class', $class, now()->addMinutes(10));
                        $input['error'] = '';
                    } else {
                        $class = "";
                        //array_pop($exploded_text);
                        Cache::put($phone . '-level', 4, now()->addMinutes(10));
                        Cache::forget($phone . '-class');
                        $input['error'] = "You've entered a wrong value!";
                    }
                }

                $exploded_text = array_values($exploded_text);
            }

            if (!empty($input['class'])) {
                if ($input['phone'] == "2" && $level == 6) {
                    $age = trim($last_item);
                    $input['error'] = '';
                    if (!is_numeric($age)) {
                        //array_pop($exploded_text);
                        Cache::forget($phone . '-age');
                        $input['error'] = "Age must be a number, try again!";
                    } elseif ($age < 12 || $age > 24) {
                        Cache::forget($phone . '-age');
                        $input['error'] = "Age must be between 12 years and 24 Years, try again!";
                    } else {
                        Cache::put($phone . '-level', 7, now()->addMinutes(10));
                        Cache::put($phone . '-age', $age, now()->addMinutes(10));
                    }
                    $exploded_text = array_values($exploded_text);
                } elseif ($input['phone'] == "1" && $level == 5) {
                    $age = trim($last_item);
                    $input['error'] = '';
                    if (!is_numeric($age)) {
                        //array_pop($exploded_text);
                        Cache::forget($phone . '-age');
                        $input['error'] = "Age must be a number, try again!";
                    } elseif ($age < 12 || $age > 24) {
                        Cache::forget($phone . '-age');
                        $input['error'] = "Age must be between 12 years and 24 Years, try again!";
                    } else {
                        Cache::put($phone . '-level', 6, now()->addMinutes(10));
                        Cache::put($phone . '-age', $age, now()->addMinutes(10));
                    }
                    $exploded_text = array_values($exploded_text);
                }

            }

            if (!empty($input['age'])) {
                $name = trim($last_item);
                $input['error'] = '';
                if ($input['phone'] == "2" && $level == 7) {
                    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
                        //array_pop($exploded_text);
                        $input['error'] = "District must be a string, try again!";
                        Cache::forget($phone . '-district');
                    } elseif (($name == trim($name) && strpos($name, ' ') !== false)) {
                        $input['error'] = "District must not contain spaces, try again!";
                        Cache::forget($phone . '-district');
                    } else {
                        Cache::put($phone . '-level', 8, now()->addMinutes(10));
                        Cache::put($phone . '-district', $name, now()->addMinutes(10));
                    }
                    $exploded_text = array_values($exploded_text);
                } elseif ($input['phone'] == "1" && $level == 6) {
                    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
                        //array_pop($exploded_text);
                        $input['error'] = "District must be a string, try again!";
                        Cache::forget($phone . '-district');
                    } elseif (($name == trim($name) && strpos($name, ' ') !== false)) {
                        $input['error'] = "District must not contain spaces, try again!";
                        Cache::forget($phone . '-district');
                    } else {
                        Cache::put($phone . '-level', 7, now()->addMinutes(10));
                        Cache::put($phone . '-district', $name, now()->addMinutes(10));
                    }
                    $exploded_text = array_values($exploded_text);
                }
            }
            }
            if (Cache::get($phone . '-registration') == 1)
            {
                Cache::put($phone . '-registration', 0, now()->addMinutes(10));
            }
            //repetion: will handle later
            $input['phone_number'] =Cache::get($phone.'-phone_number');
            $input['age'] =Cache::get($phone.'-age');
            $input['class'] =Cache::get($phone.'-class');
            $input['phone'] =Cache::get($phone.'-phone');
            $input['gender'] =Cache::get($phone.'-gender');
            $input['fname'] =Cache::get($phone.'-fname');
            $input['lname'] =Cache::get($phone.'-lname');
            $input['district'] =Cache::get($phone.'-district');
            //
            $exploded_text = array_values($exploded_text);
            $to_be_modified = array_values($exploded_text);
            $input['exploded_text'] = array_values($exploded_text);
            $input['level'] = Cache::get($phone.'-level');
            $input['message'] = end($exploded_text);
        }

        return $input;
    }

    public function ddd($item)
    {
        print_r($item);
        exit();
    }
    public function ussd_proceed($ussd_text) {
        echo "CON $ussd_text";
    }
    public function ussd_stop($ussd_text) {

        echo "END $ussd_text";
    }
}
