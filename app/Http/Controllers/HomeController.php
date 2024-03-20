<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\User;

class HomeController extends Controller
{
    public function getTopPrimaryAdvertisers(Request $request){

        $city = $request->input('city', '');
        $currentDate = Carbon::now()->format('d/m/Y');
        $query = User::select(
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
                'users.updated_at',
            )
            ->where('plan', 'Premium')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('advertisements')
                    ->whereRaw('advertisements.advertiser_id = users.id');
            });

            $query1 = clone $query;

            $newQuery = $query->join('availabilities', 'users.id', '=', 'availabilities.advertiser_id')
            ->whereJsonContains('availabilities.dates', $currentDate);

            if(count($newQuery->get()) == 0){
                $newQuery = $query1;
            }

            $newQuery->distinct();
            if (!empty($city)) {
                $newQuery->where(function ($newQuery) use ($city) {
                    $newQuery->where('users.city', 'LIKE', "%$city%");
                });
            }
            //then just query without availabilities

        $topPrimaryAdvertisers = $newQuery->paginate(10);
        return response()->json([
            'status' => true,
            'topPrimaryAdvertisers' => $topPrimaryAdvertisers,
        ], 200);
    }
}
