<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class AdminController extends Controller
{
    public function getSuperAdmin($id = null){
        $superAdminId = $id ? $id : Auth::user()->id;
        $currentUser = User::select(
                        'id',
                        'firstname',
                        'lastname',
                        'username',
                        'email',
                        'phone',
                        'age',
                        'gender',
                        DB::raw('CONCAT("' . env("APP_URL") . '", profile_photo) AS profile_photo'),
                        'role',
                        'created_at',
                    )->where('id', $superAdminId)
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

    public function updateProfilePhotoAdmin(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'profile_photo' => $request->profile_photo ? 'file|mimes:jpg,jpeg,png|max:2048' : '',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid Image',
                'error' => $inputValidation->errors()->first('profile_photo'),
            ], 422);
        }
        $user = User::find(Auth::user()->id);
        try{
            if($request->hasFile('profile_photo')) {
                $randomNumber = random_int(1000, 9999);
                $file = $request->profile_photo;
                $date = date('YmdHis');
                $filename = "IMG_" . $randomNumber . "_" . $date;
                $extension = strtolower($file->getClientOriginalExtension());
                $imageName = $filename . '.' . $extension;
                $directory = 'uploads/profileImages/';
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }
                $imageUrl = $directory . $imageName;
                $file->move($directory, $imageName);
                $image = $imageUrl;
                if($user->profile_photo != "" && file_exists($user->profile_photo)) {
                    unlink($user->profile_photo);
                }

                $userUpdated = $user->update([
                    "profile_photo" => $image,
                ]);
                if( $userUpdated ){
                    return response()->json([
                        'status' => true,
                        'message' => "Profile Photo updated",
                    ], 200);
                }
            }
            return response()->json([
                'status' => true,
                'message' => 'No Profile Photo Selected',
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => 'Some error occured',
            ], 401);
        }
    }

    public function updateAccountAdmin(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'phone' => (Auth::user()->phone != $request->phone) ? 'required|unique:users|digits:10' : '',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $user = User::find(Auth::user()->id);

        $userUpdated = $user->update([
            "firstname" => $request->firstname,
            "lastname" => $request->lastname,
            "gender" => $request->gender,
            "phone" => $request->phone,
            "age" => (int) $request->age,
        ]);
        if( $userUpdated ){
            return response()->json([
                'status' => true,
                'message' => "Account successfully updated",
            ], 200);
        }
        return response()->json([
            'message' => 'Some error occured',
        ], 401);
    }

    public function updatePasswordAdmin(Request $request){
        $inputValidation = Validator::make($request->all(), [
            "old_password" => 'required',
            "new_password" => 'required|confirmed'
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        if($request->old_password == $request->new_password){
            return response()->json([ 'status' => false, 'message' => "Old and New passwords are same. Please choose different password", ], 422);
        }
        try{
            $user = User::find( Auth::user()->id );
            if(Hash::check($request->old_password, $user->password)){
                $user->update([
                    'password' => Hash::make($request->new_password)
                ]);
                // Get the current token being used for authentication
                $currentToken = Auth::user()->currentAccessToken();
                // Remove all tokens except the current one
                $user->tokens->each(function ($token) use ($currentToken) {
                    if ($token->id !== $currentToken->id) {
                        $token->delete();
                    }
                });
                return response()->json([
                    'status' => true,
                    'message' => "Password updated successfully",
                ], 200);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Old Password does not match",
                ], 400);
            }
        }catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => "Some exception occured",
            ], 400);
        }
    }
}
