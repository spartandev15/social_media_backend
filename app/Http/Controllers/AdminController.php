<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                        'profile_photo',
                        'role',
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
}
