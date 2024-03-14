<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Advertisement;
use App\Models\Availability;
use App\Models\AdvertiserPhoto;
use App\Models\AdvertiserVideo;
use Carbon\Carbon;

class AdvertisementController extends Controller
{
    public function getAdvertisement($id = null){
        $advertiserId = $id ? $id : Auth::user()->id;
        $Ads = Advertisement::where('advertiser_id', $advertiserId)->get();
        return response()->json([
            'status' => true,
            'allAds' => $Ads,
        ], 200);
    }

    public function getAdvertisementById($id){
        $ad = Advertisement::withTrashed()->where('id', $id)->first();
        return response()->json([
            'status' => true,
            'advertisement' => $ad,
        ], 200);
    }

    public function getAllAdvertisements(Request $request){
        $allAds = Advertisement::leftJoin('users', 'users.id', '=', 'advertisements.advertiser_id')
                ->select(DB::raw("CONCAT(users.firstname, ' ', users.lastname) AS advertiser_name"), 'advertisements.*')
                ->where('advertisements.paused', 0)->where('advertisements.paused_at', null)
                ->paginate(10);
        return response()->json([
            'status' => true,
            'allAds' => $allAds,
        ], 200);
    }

    public function createAdvertisement(Request $request){
        // dd($request->all());
        $inputValidation = Validator::make($request->all(), [
            'ad_name' => 'required',
            'publish_date' => 'required',
            'location' => 'required',
            'mile_radius' => 'required',
            'state' => 'required',
            'country' => 'required',
            'duration_price' => 'required|array',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }

        $adCreated = Advertisement::create([
            "advertiser_id" => Auth::user()->id,
            "ad_name" => $request->ad_name,
            "publish_date" => $request->publish_date,
            "location" => $request->location,
            "mile_radius" => $request->mile_radius,
            "state" => $request->state,
            "country" => $request->country,
            "duration_price" => $request->duration_price,
        ]);
        if( $adCreated ){
            
            return response()->json([
                'status' => true,
                'message' => "Advertisement Created Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function updateAdvertisement($id, Request $request){

        $inputValidation = Validator::make($request->all(), [
            'ad_name' => 'required',
            'publish_date' => 'required',
            'location' => 'required',
            'mile_radius' => 'required',
            'state' => 'required',
            'country' => 'required',
            'duration_price' => 'required|array',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }

        $adUpdated = Advertisement::where('id', $id)->update([
            "ad_name" => $request->ad_name,
            "publish_date" => $request->publish_date,
            "location" => $request->location,
            "mile_radius" => $request->mile_radius,
            "state" => $request->state,
            "country" => $request->country,
            "duration_price" => $request->duration_price,
        ]);
        if( $adUpdated ){
            
            return response()->json([
                'status' => true,
                'message' => "Advertisement Updated Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function deleteAdvertisement($id){
        
        $adDeleted = Advertisement::where('id', $id)->forceDelete();
        if( $adDeleted ){
            
            return response()->json([
                'status' => true,
                'message' => "Advertisement Deleted Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function trashAdvertisement($id){
        
        $adTrashed = Advertisement::find($id);
        if( $adTrashed ){
            $adTrashed->deleted = 1;
            $adTrashed->save();
            $adTrashed->delete();
            return response()->json([
                'status' => true,
                'message' => "Advertisement Soft Deleted Successfully",
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Advertisement not found',
            ], 401);
        }
    }

    public function restoreAdvertisement($id){
        
        $adRestored = Advertisement::withTrashed()->find($id);
        if( $adRestored ){
            $adRestored->deleted = 0;
            $adRestored->save();
            $adRestored->restore();
            return response()->json([
                'status' => true,
                'message' => "Advertisement Restored Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function getTrashedAdvertisements(Request $request) {

        $trashedAds = Advertisement::onlyTrashed()
            ->leftJoin('users', 'users.id', '=', 'advertisements.advertiser_id')
            ->select(DB::raw("CONCAT(users.firstname, ' ', users.lastname) AS advertiser_name"), 'advertisements.*')
            ->orderBy('advertisements.deleted_at', 'desc')
            ->paginate(10);
    
        return response()->json([
            'status' => true,
            'trashedAds' => $trashedAds,
        ], 200);
    }

    public function renewAdvertisement($id){
        
        $adRenewed = Advertisement::where('id', $id)->update([
            'expired' => 0,
            'expired_at' => null,
            'renew' => 1,
            'renew_at' => now(),
        ]);
        if( $adRenewed ){
            
            return response()->json([
                'status' => true,
                'message' => "Advertisement Renewed Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function getLatestAdvertisements(Request $request) {
        // Get the current date
        $currentDate = Carbon::now();
    
        // Calculate the date two weeks ago
        $twoWeeksAgo = $currentDate->subWeeks(2);

        // Retrieve advertisements within the last two weeks
        $latestAds = Advertisement::leftJoin('users', 'users.id', '=', 'advertisements.advertiser_id')
                ->select(DB::raw("CONCAT(users.firstname, ' ', users.lastname) AS advertiser_name"), 'advertisements.*')
                ->where('advertisements.paused', 0)->where('advertisements.paused_at', null)
                ->where('advertisements.created_at', '>=', $twoWeeksAgo)->orderBy('advertisements.created_at', 'desc')->paginate(10);
    
        return response()->json([
            'status' => true,
            'latestAds' => $latestAds,
        ], 200);
    }

    public function pauseAdvertisement($id) {

        $pausedAds = Advertisement::where('id', $id)
            ->update([
                "paused" => 1,
                "paused_at" => now(),
            ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Advertisement Paused Successfully',
        ], 200);
    }

    public function getPausedAdvertisements(Request $request) {

        $pausedAds = Advertisement::where('paused', '=', 1)
            ->orderBy('paused_at', 'desc')
            ->leftJoin('users', 'users.id', '=', 'advertisements.advertiser_id')
            ->select(DB::raw("CONCAT(users.firstname, ' ', users.lastname) AS advertiser_name"), 'advertisements.*') // Select desired columns from both tables
            ->paginate(10);
    
        return response()->json([
            'status' => true,
            'pausedAds' => $pausedAds,
        ], 200);
    }

    public function activatePausedAdvertisement($id){
        
        $ad = Advertisement::withTrashed()->find($id);
        if( $ad ){
            $ad->paused = 0;
            $ad->paused_at = null;
            $ad->save();
            return response()->json([
                'status' => true,
                'message' => "Advertisement Activated Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function createAvailability(Request $request){
        $inputValidation = Validator::make($request->all(), [
            'ad_id' => 'required',
            'dates' => 'required|array',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $createdAvailibility = Availability::create([
            'advertiser_id' => Auth::user()->id,
            'ad_id' => $request->ad_id,
            'dates' => $request->dates,
        ]);
        if( $createdAvailibility ){
            return response()->json([
                'status' => true,
                'message' => "Availability Created Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function getAvailabilities(Request $request){

        $avails = Availability::select('advertisements.ad_name', 'availabilities.*')
                    ->leftJoin('advertisements', 'availabilities.ad_id', '=', 'advertisements.id')
                    ->where('advertisements.advertiser_id', Auth::user()->id)
                    ->get();

        return response()->json([
            'status' => true,
            'availabilities' => $avails,
        ], 200);
    }

    public function updateAvailability($id, Request $request){
        $inputValidation = Validator::make($request->all(), [
            'dates' => 'required|array',
        ]);
        if($inputValidation->fails()){
            return response()->json([
                'message' => 'Invalid data entered',
                'errors' => $inputValidation->errors(),
            ], 422);
        }
        $updatedAvail = Availability::where('id', $id)->update([
            'dates' => $request->dates,
        ]);

        if( $updatedAvail ){
            return response()->json([
                'status' => true,
                'message' => "Availability Updated Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function deleteAvailability($id){
        $availDeleted = Availability::where('id', $id)->delete();
        if( $availDeleted ){
            return response()->json([
                'status' => true,
                'message' => "Availability Deleted Successfully",
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function deleteImageById($id){
        $photo = AdvertiserPhoto::find($id);
        if( $photo ){
            if(isset($photo->image) && !empty($photo->image)){
                if($photo->image != "" && file_exists($photo->image)) {
                    unlink($photo->image);
                }
                $photo->delete();
                return response()->json([
                    'status' => true,
                    'message' => "Image Deleted Successfully",
                ], 200);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }

    public function deleteVideoById($id){
        $video = AdvertiserVideo::find($id);
        if( $video ){
            if(isset($video->video) && !empty($video->video)){
                if($video->video != "" && file_exists($video->video)) {
                    unlink($video->video);
                }
                $video->delete();
                return response()->json([
                    'status' => true,
                    'message' => "Video Deleted Successfully",
                ], 200);
            }
        }
        return response()->json([
            'status' => false,
            'message' => 'Some error occured',
        ], 401);
    }
}
