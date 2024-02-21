<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserController extends Controller
{

    public function getAdvertiser($id = null){
        $advertiserId = $id ? $id : Auth::user()->id;
        $currentUser = User::select(
                        'id',
                        'firstname',
                        'lastname',
                        'username',
                        'email',
                        'phone',
                        'age',
                        'gender',
                        'ethnicity',
                        'height',
                        'breast_size',
                        'eye_color',
                        'hair_color',
                        'body_type',
                        'profile_photo',
                        'role',
                        'plan',
                        'created_at',
                    )->where('id', $advertiserId)
                                ->first();
        if($currentUser){
            return response()->json([
                'status' => true,
                'user' => $currentUser,
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => "User not found",
            ], 404);
        }
    }

}
