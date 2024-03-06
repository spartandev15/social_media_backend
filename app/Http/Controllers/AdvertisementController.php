<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Advertisement;
use App\Models\Availability;

class AdvertisementController extends Controller
{
    public function getAdvertisement(Request $request){
        $Ads = Advertisement::where('advertiser_id', Auth::user()->id)->get();
        return response()->json([
            'status' => true,
            'allAds' => $Ads,
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
        
        $adDeleted = Advertisement::where('id', $id)->delete();
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
}
