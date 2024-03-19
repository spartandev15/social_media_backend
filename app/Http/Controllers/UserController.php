<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

use App\Models\User;
use App\Models\AdvertiserPhoto;
use App\Models\AdvertiserVideo;
use App\Models\Availability;
use App\Models\Advertisement;

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
            DB::raw('CONCAT("' . env("APP_URL") . '", users.cover_photo) AS cover_photo'),
            'users.street_address',
            'users.city',
            'users.state',
            'users.country',
            'users.zip',
            'users.role',
            'users.plan',
            'users.created_at',
        )
        ->with('advertiserPhotos:id,image')
        ->with('advertiserVideos:id,video')
        ->where('users.id', $advertiserId)
        ->first();
        
        // Transform advertiser photos into an array of arrays
        $images = AdvertiserPhoto::where('advertiser_id', $advertiserId)
            ->get(['id', 'image'])
            ->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'image' => env("APP_URL") . $photo->image,
                ];
            })
            ->toArray();

        // Transform advertiser videos into an array of arrays
        $videos = AdvertiserVideo::where('advertiser_id', $advertiserId)
            ->get(['id', 'video'])
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'video' => env("APP_URL") . $video->video,
                ];
            })
            ->toArray();
        
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

    public function getLatestAdvertisers(Request $request){
        $searchValue = $request->input('searchText', '');
        $currentDate = Carbon::now();
    
        // Calculate the date two weeks ago
        $twoWeeksAgo = $currentDate->subWeeks(2);

        $query = User::select(
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
            DB::raw('CONCAT("' . env("APP_URL") . '", profile_photo) AS profile_photo'),
            'street_address',
            'city',
            'state',
            'country',
            'zip',
            'role',
            'plan',
            'created_at',
            'updated_at',
            )
        ->where('role', 'Advertiser')
        ->where('created_at', '>=', $twoWeeksAgo)->orderBy('created_at', 'desc');
    
        if (!empty($searchValue)) {
            $query->where(function ($query) use ($searchValue) {
                $query->where('firstname', 'LIKE', "%$searchValue%")
                    ->orWhere('lastname', 'LIKE', "%$searchValue%")
                    ->orWhere('username', 'LIKE', "%$searchValue%")
                    ->orWhere('email', 'LIKE', "%$searchValue%")
                    ->orWhere('phone', 'LIKE', "%$searchValue%");
            });
        }
        $advertisers = $query->paginate(10);
        return response()->json([
            'status' => true,
            'latestAdvertisers' => $advertisers,
        ], 200);
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
                DB::raw('CONCAT("' . env("APP_URL") . '", users.cover_photo) AS cover_photo'),
                'users.street_address',
                'users.city',
                'users.state',
                'users.country',
                'users.zip',
                'users.role',
                'users.plan',
                'users.created_at',
            )
            ->with('advertiserPhotos:id,image')
            ->with('advertiserVideos:id,video')
            ->find($user->id);
            // Transform advertiser photos into an array of arrays
            $images = AdvertiserPhoto::where('advertiser_id', $user->id)
                ->get(['id', 'image'])
                ->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'image' => env("APP_URL") . $photo->image,
                    ];
                })
                ->toArray();

            // Transform advertiser videos into an array of arrays
            $videos = AdvertiserVideo::where('advertiser_id', $user->id)
                ->get(['id', 'video'])
                ->map(function ($video) {
                    return [
                        'id' => $video->id,
                        'video' => env("APP_URL") . $video->video,
                    ];
                })
                ->toArray();
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

    public function updateCoverPhoto(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'cover_photo' => $request->cover_photo ? 'file|mimes:jpg,jpeg,png|max:2048' : '',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid Image',
                'error' => $inputValidation->errors()->first('cover_photo'),
            ], 422);
        }
        $user = User::find(Auth::user()->id);
        try{
            if($request->hasFile('cover_photo')) {
                $randomNumber = random_int(1000, 9999);
                $file = $request->cover_photo;
                $date = date('YmdHis');
                $filename = "COVER_IMG_" . $randomNumber . "_" . $date;
                $extension = strtolower($file->getClientOriginalExtension());
                $imageName = $filename . '.' . $extension;
                $directory = 'uploads/coverImages/';
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }
                $imageUrl = $directory . $imageName;
                $file->move($directory, $imageName);
                $image = $imageUrl;
                if($user->cover_photo != "" && file_exists($user->cover_photo)) {
                    unlink($user->cover_photo);
                }

                $userUpdated = $user->update([
                    "cover_photo" => $image,
                ]);
                if( $userUpdated ){
        
                    return response()->json([
                        'status' => true,
                        'message' => "Cover Photo updated",
                    ], 200);
                }
            }
        }catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => 'Some error occured',
            ], 401);
        }
            
    }

    public function updatePassword(Request $request){
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

    public function deleteAdvertiser(){
        $user = User::find(Auth::user()->id);
        if($user){
            try{
                // delete all the tokens
                $user->tokens()->delete();
                // Now delete the user
                $user->delete();
                return response()->json([ 'status' => true, 'message' => "Acoount Deleted Successfully", ], 200);
            }catch(\Exception $e){
                return response()->json([ 'status' => false, 'message' => "Some error occured", ], 400);
            }
        }else{
            return response()->json([ 'status' => false, 'message' => "Some error occured", ], 400);
        }
    }

    public function deleteUserPermanently($id){
        $user = User::find($id);
        try{
            if($user){
                // delete associated Availability
                Availability::where('advertiser_id', $id)->delete();

                // delete associated photos from directory and record
                $userAdsPhotos = AdvertiserPhoto::where('advertiser_id', $id)->get();
                foreach($userAdsPhotos as $photo){
                    $this->deleteFile($photo->image);
                }
                AdvertiserPhoto::where('advertiser_id', $id)->delete();

                // delete associated Videos from directory and record
                $userAdsVideos = AdvertiserVideo::where('advertiser_id', $id)->get();
                foreach($userAdsVideos as $video){
                    $this->deleteFile($video->video);
                }
                AdvertiserVideo::where('advertiser_id', $id)->delete();

                $userAds = Advertisement::where('advertiser_id', $id)->forceDelete();
                // delete profile photo
                $this->deleteFile($user->profile_photo);
                // delete all the tokens
                $user->tokens()->delete();
                // Now delete the user
                $user->forceDelete();

                return response()->json([ 'status' => true, 'message' => "Account Deleted Successfully", ], 200);
            }else{
                return response()->json([ 'status' => false, 'message' => "No user found", ], 400);
            }
        }catch(\Exception $e){
            dd($e);
            // return response()->json([ 'status' => false, 'message' => "Some error occured", ], 400);
        }
    }

    function deleteFile($filePath){
        if($filePath != "" && file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
