<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\User;
use App\Models\EmailVerification;
use App\Models\PasswordReset;

class AuthController extends Controller
{
    public function sendOTP(Request $request){
        $inputValidation = Validator::make($request->all(), [
            "email" => 'required|email|unique:users,email',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Please enter a valid email',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        try{
            $randOtp = random_int(100000, 999999);
            $useremail = $request->email;

            $data = [
                'otp' => $randOtp,
            ];

            if( 
                Mail::send('auth.otpEmail', ['data' => $data], function ($message) use ($useremail){
                $message->from('testspartanbots@gmail.com', 'Social Media');
                $message->to($useremail)->subject('Email Verification OTP'); }) 
            ){
                EmailVerification::updateOrCreate(
                    ['email' => $useremail],
                    [
                        "email" => $useremail,
                        "otp" => $randOtp
                    ]
                );
                return response()->json([
                    'status' => true,
                    'messsage' => 'Otp sent successfully',
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Some error occured',
            ], 401);
        }
    }

    public function register(Request $request){
        // dd($request->all());
        $inputValidation = Validator::make($request->all(), [
            "username" => 'required',
            "firstname" => 'required',
            "lastname" => 'required',
            "email" => 'required|email:filter|unique:users,email',
            "password" => 'required|min:6|confirmed',
            "otp" => 'required',
            'phone' => 'required|regex:/^[0-9]{10}$/',
            "age" => 'required',
            "age_confirmed" => 'required',
            "gender" => 'required',
            // "street_address" => 'required',
            // "city" => 'required',
            // "country" => 'required',
            // "zip" => 'required',
            "payment_received" => 'required',
            "plan" => 'required',

        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        if( $request->payment_received != 1 ){
            return response()->json([
                "status" => false,
                "message" => 'Payment required for registration.',
            ], 422);
        }
        if( User::where('phone', $request->phone)->exists() ){
            return response()->json([
                "status" => false,
                'message' => 'Phone number already exists. Please use another number',
            ], 422);
        }
        if( EmailVerification::where('email', $request->email)->where('otp', $request->otp)->exists() ){
            $timeVerification = EmailVerification::select('created_at')->where('email', $request->email)->where('otp', $request->otp)->first();
            if($timeVerification){
                $to = Carbon::createFromFormat('Y-m-d H:i:s', $timeVerification->created_at);
                $from = Carbon::createFromFormat('Y-m-d H:i:s', now());
                $diff_in_minutes = $to->diffInMinutes($from);
                EmailVerification::where('email', $request->email)->where('otp', $request->otp)->delete();
                if($diff_in_minutes > 30 ){
                    return response()->json([
                        'status' => false,
                        'message' => 'OTP Expired',
                    ], 400);
                }
            }
        }else{
            return response()->json([
                'status' => false,
                'message' => "Invalid OTP",
            ], 422);
        }

        $user = User::create([
            "firstname" => $request->firstname,
            "lastname" => $request->lastname,
            "username" => $request->username,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "phone" => $request->phone,
            "age" => (int) $request->age,
            "age_confirmed" => $request->age_confirmed,
            "gender" => $request->gender,
            // "street_address" => $request->street_address,
            // "city" => $request->city,
            // "country" => $request->country,
            // "zip" => $request->zip,
            "payment_received" => $request->payment_received,
            "plan" => $request->plan,
            "email_verified" => 1,
        ]);
        if( $user ){
            return response()->json([
                'status' => true,
                'message' => "User successfully registered",
            ], 200);
        }
        return response()->json([
            'message' => 'Some error occured',
        ], 401);
    }

    public function login(Request $request){

        $inputValidation = Validator::make($request->all(), [
            "email" => 'required|email',
            "password" => 'required',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }

        if( Auth::attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
            ]) ){
            $user = Auth::select('username', 'firstname', 'lastname', 'email', 'phone', 'age', 'gender', 'role')->user();
            $token = $user->createToken($user->email.'_api_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'user' => $user,
                'token' => $token,
                'role' => "user",
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'messsage' => "Email and password do not match.",
            ], 401); 
        }
    }

    public function forgotpassword(Request $request){
        $inputValidation = Validator::make($request->all(), [
            "email" => 'required|email',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Invalid Email',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $useremail = $request->email;
        if(User::where('email', '=', $useremail)->exists()){
            $uid =  Str::uuid()->toString();
            $domain =  env('APP_URL');

            $data = [
                'link' => $domain.'reset-password?token='.$uid,
            ];
            Mail::send('auth.forgotPassEmailTemp', ['data' => $data], function ($message) use ($useremail){
                $message->from('testspartanbots@gmail.com', 'Social Media');
                $message->to($useremail)->subject('Password Reset');
            });

            $datetime = Carbon::now()->format('Y-m-d H:i:s');
            PasswordReset::updateOrCreate(
                ['email' => $useremail],
                [
                    'email' => $useremail,
                    'token' => $uid,
                    'created_at' => $datetime
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Email sent successfully',
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Email id not registered',
            ], 401);
        }
        
    }

    public function resetPassword(Request $request){

        $inputValidation = Validator::make($request->all(), [
            "password" => 'required|confirmed',
            "token" => 'required'
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        try{
            $email = DB::table('password_reset_tokens')
                ->select('email')
                ->where('token', $request->token)
                ->value('email');
            
            if( $email ){
                $user = User::where('email',$email)->first();
                $user->fill([
                    "password" => Hash::make($request->password)
                ]);
                $user->save();

                $user->tokens()->delete();
                PasswordReset::where('email', $email)->delete();

                return response()->json([
                    'status' => true,
                    'message' => 'Password reset successfully'
                ], 200);

            } else{
                return response()->json([
                    'status' => false,
                    'message' => 'Some error occured please ask for reset link again',
                ], 401);
            }
        }catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => "Some exception occured",
            ], 400);
        }
    }

    public function isTokenValid(Request $request){
        if($request->token != ''){
            $tokenExists = PasswordReset::select('created_at')->where('token', $request->token)->first();
            if($tokenExists){
                $to = Carbon::createFromFormat('Y-m-d H:i:s', $tokenExists->created_at);
                $from = Carbon::createFromFormat('Y-m-d H:i:s', now());
                $diff_in_minutes = $to->diffInMinutes($from);

                if($diff_in_minutes <= 10 ){
                    return response()->json([
                        'status' => true,
                        'message' => 'Token is valid',
                    ], 200);
                }else{
                    PasswordReset::where('token', $request->token)->delete();
                    return response()->json([
                        'status' => false,
                        'message' => 'Token not valid',
                    ], 400);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => 'Token not found',
                ], 404);
            }
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Token not found',
            ], 404);
        }
    }
}
