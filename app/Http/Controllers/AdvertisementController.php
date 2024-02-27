<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Advertisement;

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
}