<?php

namespace App\Http\Controllers;

use App\Demmande;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Auth;

class DemmandeController extends Controller
{
    public static function distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000){
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        
        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);
        
        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        if($user->specialiste){
            request()->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric'
            ]);
            $collection = Demmande::all();
            $filtered_collection = $collection->filter(function ($demmande) {
                return ((DemmandeController::distance(request()->lat, request()->lng, $demmande->lat, $demmande->lng) <= env('MAX_DISTANCE_DEMMANDE')) && !$demmande->etat &&
                        ( $demmande->tags()->find(6) || !Auth::user()->tags()->get()->intersect($demmande->tags()->get())->isEmpty())) 
                        || (Auth::user()->interessepar()->find($demmande->id));
            })->values();
            return $filtered_collection;
        }else{
            $collection = Demmande::all();
            $filtered_collection = $collection->filter(function ($demmande) {
                return Auth::user()->id == $demmande->user_id;
            })->values();
            return $filtered_collection;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'titre' => 'required|string',
            'description' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'estimation' => 'required|numeric'
        ]);
        $demmande = new Demmande([
            'user_id' => $user->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'estimation' => $request->estimation
        ]);
        $demmande->save();
        return response()->json([
            'message' => 'Successfully created demmande!',
            'data' => $demmande
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Demmande  $demmande
     * @return \Illuminate\Http\Response
     */
    public function show(Demmande $demmande)
    {
        $user = Auth::user();
        if($user->specialiste || $user->id == $demmande->user_id){
            return $demmande;
        }else{
            return response()->json([
                'message' => 'Access Denied!'
            ], 403);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Demmande  $demmande
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Demmande $demmande)
    {
        if(!$demmande->etat){
            $user = Auth::user();
            if($user->specialiste){
                if(!$user->interessepar()->find($demmande->id))$user->interessepar()->attach($demmande->id);
                else $user->interessepar()->detach($demmande->id);
                //(!Auth::user()->interesse()->get()->intersect($demmande->tags()->get())->isEmpty()
                //$user->interesse()->save(Demmande::find($demmande->id));
                //else $user->interesse()->detach(Demmande::find($demmande->id));
            }elseif($user->id == $demmande->user_id){
                if(isset($request->titre))$demmande->titre = $request->titre;
                if(isset($request->description))$demmande->description = $request->description;
                if(isset($request->lat))$demmande->lat = $request->lat;
                if(isset($request->lng))$demmande->lng = $request->lng;
                if(isset($request->estimation))$demmande->estimation = $request->estimation;
                if(isset($request->etat)){
                    if(isset($request->specialiste)&& isset($request->feedback)){
                        $demmande->etat = $request->etat;
                        $specialiste= User::find($request->specialiste);
                        $specialiste->excellence+=$request->feedback;
                        $specialiste->save();
                    }
                    
                }
                if(isset($request->tags_id)){
                    $demmande->tags()->detach();
                    $tags_id = $request->tags_id;
                    if(is_array ( $tags_id )){
                        foreach ($tags_id as $value) {
                            $demmande->tags()->save(Tag::find($value));
                        }
                    }else{
                        $demmande->tags()->save(Tag::find($tags_id));
                    }
                }
                $demmande->save();
            }else{
                return response()->json([
                    'message' => 'Access Denied!'
                ], 403);
            }
            
        }
        return response()->json([
            'message' => 'Demmande modifier avec succes!',
            'data' => $demmande
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Demmande  $demmande
     * @return \Illuminate\Http\Response
     */
    public function destroy(Demmande $demmande)
    {
        $user = Auth::user();
        if($user->id == $demmande->user_id){
            $demmande->delete();
            return response()->json([
                'message' => 'Demmande supprimer avec succes!'
            ], 200);
        }else{
            return response()->json([
                'message' => 'Access Denied!'
            ], 403);
        }
    }
}
