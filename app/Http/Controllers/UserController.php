<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
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

    public function updateAdvertiser(Request $request){
        $inputValidation = Validator::make($request->all(), [
            // 'ethnicity' => 'required',
            // 'height' => 'required',
            // 'breastSize' => 'required',
            // 'eyeColor' => 'required',
            // 'hairColor' => 'required',
            // 'bodyType' => 'required',
            'profilePhoto' => $request->profilePhoto ? 'file|mimes:jpg,jpeg,png|max:2048' : '',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $user = User::find(Auth::user()->id);

        if($request->hasFile('profilePhoto')) {
            $randomNumber = random_int(1000, 9999);
            $file = $request->profilePhoto;
            $date = date('YmdHis');
            $filename = "IMG_" . $randomNumber . "_" . $date;
            $extension = strtolower($file->getClientOriginalExtension());
            $imageName = $filename . '.' . $extension;
            $directory = 'uploads/profileImages/';
            if (!file_exists(public_path($directory))) {
                mkdir(public_path($directory), 0777, true);
            }
            $imageUrl = $directory . $imageName;
            $file->move(public_path($directory), $imageName);
            $image = $imageUrl;
            if($user->profile_photo != "" && file_exists(public_path($user->profile_photo))) {
                unlink(public_path($user->profile_photo));
            }
        } else {
            $image = $user->profile_photo;
        }

        $userUpdated = $user->update([
            "ethnicity" => $request->ethnicity,
            "height" => $request->height,
            "breast_size" => $request->breastSize,
            "eye_color" => $request->eyeColor,
            "hair_color" => $request->hairColor,
            "body_type" => $request->bodyType,
            "profile_photo" => $image,
        ]);
        if( $userUpdated ){
            $updatedUser = User::select(
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
            )->find($user->id);
            return response()->json([
                'status' => true,
                'message' => "User successfully updated",
                'user' => $updatedUser,
            ], 200);
        }
        return response()->json([
            'message' => 'Some error occured',
        ], 401);
    }
}
