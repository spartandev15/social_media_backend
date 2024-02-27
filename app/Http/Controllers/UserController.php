<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Models\User;
use App\Models\AdvertiserPhoto;
use App\Models\AdvertiserVideo;

class UserController extends Controller
{

    public function getAdvertiser($id = null){
        $advertiserId = $id ? $id : Auth::user()->id;
        $currentUser = User::select(
            'users.id',
            'users.firstname',
            'users.lastname',
            'users.username',
            'users.email',
            'users.phone',
            'users.age',
            'users.gender',
            'users.ethnicity',
            'users.height',
            'users.breast_size',
            'users.eye_color',
            'users.hair_color',
            'users.body_type',
            DB::raw('CONCAT("' . env("APP_URL") . '", users.profile_photo) AS profile_photo'),
            'users.role',
            'users.plan',
            'users.created_at',
        )
        ->with('advertiserPhotos:id,image')
        ->with('advertiserVideos:id,video')
        ->where('users.id', $advertiserId)
        ->first();
        
        // Transform advertiser photos into an array of arrays
        $images = Auth::user()->advertiserPhotos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'image' => env("APP_URL") . $photo->image,
            ];
        })->toArray();

        // Transform advertiser videos into an array of arrays
        $videos = Auth::user()->advertiserVideos->map(function ($video) {
            return [
                'id' => $video->id,
                'video' => env("APP_URL") . $video->video,
            ];
        })->toArray();
        
        // Merge the images into the $currentUser object
        $currentUser->images = $images;
        $currentUser->videos = $videos;
        
        // Remove the advertiserPhotos property as it's no longer needed
        unset($currentUser->advertiserPhotos);
        unset($currentUser->advertiserVideos);
        
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
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            $imageUrl = $directory . $imageName;
            $file->move($directory, $imageName);
            $image = $imageUrl;
            if($user->profile_photo != "" && file_exists($user->profile_photo)) {
                unlink($user->profile_photo);
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
                'users.id',
                'users.firstname',
                'users.lastname',
                'users.username',
                'users.email',
                'users.phone',
                'users.age',
                'users.gender',
                'users.ethnicity',
                'users.height',
                'users.breast_size',
                'users.eye_color',
                'users.hair_color',
                'users.body_type',
                DB::raw('CONCAT("' . env("APP_URL") . '", users.profile_photo) AS profile_photo'),
                'users.role',
                'users.plan',
                'users.created_at',
            )
            ->with('advertiserPhotos:id,image')
            ->with('advertiserVideos:id,video')
            ->find($user->id);
            $images = Auth::user()->advertiserPhotos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'image' => env("APP_URL") . $photo->image,
                ];
            })->toArray();
            // Transform advertiser videos into an array of arrays
            $videos = Auth::user()->advertiserVideos->map(function ($video) {
                return [
                    'id' => $video->id,
                    'video' => env("APP_URL") . $video->video,
                ];
            })->toArray();
            $updatedUser->images = $images;
            $updatedUser->videos = $videos;

            unset($updatedUser->advertiserVideos);
            unset($updatedUser->advertiserPhotos);

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

    public function updateImages(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'images' => 'array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Please select a file of type jpg, jpeg or png. Max size 2MB',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $advImageIds = Auth::user()->advertiserPhotos->pluck('id')->toArray();
        $oldImages = $request->oldImageIds != "" ? explode(",", $request->oldImageIds) : [];
        $oldImagesCount = count($oldImages);

        $AdvImages = AdvertiserPhoto::where('advertiser_id', Auth::user()->id)->get();
        $totalImagesCount = count($AdvImages);
        if($oldImagesCount != $totalImagesCount){
            $oldImages = array_map('intval', $oldImages);
            $idsToRemove = array_diff($advImageIds, $oldImages);
            $idsToRemove = array_values($idsToRemove);
            foreach ($idsToRemove as $idToRemove) {
                // Find the AdvertiserPhoto record with the ID to remove
                $photoToRemove = AdvertiserPhoto::find($idToRemove);
                if ($photoToRemove) {
                    // Delete the image file if it exists
                    if (file_exists($photoToRemove->image)) {
                        unlink($photoToRemove->image);
                    }
                    $photoToRemove->delete();
                }
            }
        }
        if($request->hasFile('images')){
            // if(($oldImagesCount + count($request->file('images'))) > 4){
            //     return response()->json([
            //         'message' => 'You have already 4 images uploaded. Please first remove images.',
            //     ], 422);
            // }
            foreach($request->file('images') as $image){
                try{
                    $randomNumber = random_int(1000, 9999);
                    $file = $image;
                    $date = date('YmdHis');
                    $filename = "IMG_" . $randomNumber . "_" . $date;
                    $extension = strtolower($file->getClientOriginalExtension());
                    $imageName = $filename . '.' . $extension;
                    $directory = 'uploads/advertiserImages/';
                    if (!file_exists($directory)) {
                        mkdir($directory, 0777, true);
                    }
                    $imageUrl = $directory . $imageName;
                    $file->move($directory, $imageName);
                    $image = $imageUrl;
                    AdvertiserPhoto::create([
                        "advertiser_id" => Auth::user()->id,
                        "image" => $image,
                    ]);
                }catch(\Exception $e){
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Image successfully updated",
        ], 200);
        
        // return response()->json([
        //     'message' => 'Some error occured',
        // ], 401);
    }

    public function updateVideos(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'videos' => 'array',
            'videos.*' => 'file|mimes:mp4',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Please select a file of type jpg, jpeg or png. Max size 2MB',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $advVideoIds = Auth::user()->advertiserVideos->pluck('id')->toArray();
        $oldvideos = $request->oldVideoIds != "" ? explode(",", $request->oldVideoIds) : [];
        $oldVideosCount = count($oldvideos);

        $advVideos = AdvertiserVideo::where('advertiser_id', Auth::user()->id)->get();
        $totalvideosCount = count($advVideos);
        if($oldVideosCount != $totalvideosCount){
            $oldvideos = array_map('intval', $oldvideos);
            $idsToRemove = array_diff($advVideoIds, $oldvideos);
            $idsToRemove = array_values($idsToRemove);
            foreach ($idsToRemove as $idToRemove) {
                // Find the AdvertiserVideo record with the ID to remove
                $videoToRemove = AdvertiserVideo::find($idToRemove);
                if ($videoToRemove) {
                    // Delete the video file if it exists
                    if (file_exists($videoToRemove->video)) {
                        unlink($videoToRemove->video);
                    }
                    $videoToRemove->delete();
                }
            }
        }
        if($request->hasFile('videos')){
            // if(($oldVideosCount + count($request->file('videos'))) > 4){
            //     return response()->json([
            //         'message' => 'You have already 4 videos uploaded. Please first remove videos.',
            //     ], 422);
            // }
            foreach($request->file('videos') as $video){
                try{
                    $randomNumber = random_int(1000, 9999);
                    $file = $video;
                    $date = date('YmdHis');
                    $filename = "VID_" . $randomNumber . "_" . $date;
                    $extension = strtolower($file->getClientOriginalExtension());
                    $videoName = $filename . '.' . $extension;
                    $directory = 'uploads/advertiserVideos/';
                    if (!file_exists($directory)) {
                        mkdir($directory, 0777, true);
                    }
                    $videoUrl = $directory . $videoName;
                    $file->move($directory, $videoName);
                    $video = $videoUrl;
                    AdvertiserVideo::create([
                        "advertiser_id" => Auth::user()->id,
                        "video" => $video,
                    ]);
                }catch(\Exception $e){
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Videos successfully updated",
        ], 200);
        
        // return response()->json([
        //     'message' => 'Some error occured',
        // ], 401);
    }

    public function updateMyAccount(Request $request){
        $inputValidation = Validator::make($request->all(), [
            // 'ethnicity' => 'required',
            // 'height' => 'required',
            // 'breast_size' => 'required',
            // 'eye_color' => 'required',
            // 'hair_color' => 'required',
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
            "street_address" => $request->street_address,
            "city" => $request->city,
            "state" => $request->state,
            "country" => $request->country,
            "zip" => $request->zip,
            "phone" => $request->phone,
        ]);
        if( $userUpdated ){
            return response()->json([
                'status' => true,
                'message' => "Accout successfully updated",
            ], 200);
        }
        return response()->json([
            'message' => 'Some error occured',
        ], 401);
    }
}
