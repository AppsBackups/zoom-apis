<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class EventController extends Controller
{
    // 1. Register Event
    public function registerEvent(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
            'eventname' => 'required|string',
            'eventdescription' => 'required|string',
            'eventlevel' => 'required|integer',
            'meetingid' => 'required|string',
            'meetingpassword' => 'required|string',
            'sportstype' => 'required|string',
        ]);

        $event = Event::create([
            'superid' => $request->superid,
            'eventname' => $request->eventname,
            'eventdescription' => $request->eventdescription,
            'eventlevel' => $request->eventlevel,
            'meetingid' => $request->meetingid,
            'meetingpassword' => $request->meetingpassword,
            'sportstype' => $request->sportstype,
            'estatus' => false,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Event registered successfully',
            'event' => $event
        ]);
    }

    // 2. Event List
    public function eventList(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
        ]);

        $events = Event::where('superid', $request->superid)
            ->get();

        return response()->json([
            'empty' => $events->isEmpty(),
            'error' => false,
            'event' => $events
        ]);
    }

    // 3. Toggle Live Status
    public function toggleLive(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
            'eventid' => 'required|integer',
            'estatus' => 'required|boolean',
        ]);

        $event = Event::where('id', $request->eventid)
            ->where('superid', $request->superid)
            ->first();

        if (!$event) {
            return response()->json(['error' => true, 'message' => 'Event not found']);
        }

        $event->estatus = $request->estatus;
        $event->save();

        return response()->json([
            'error' => false,
            'message' => 'Event State Changed successfully',
            'event' => $event
        ]);
    }

    // 4. Update Event
    public function updateEvent(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
            'eventid' => 'required|integer',
            'eventname' => 'required|string',
            'eventdescription' => 'required|string',
            'eventlevel' => 'required|integer',
            'meetingid' => 'required|string',
            'meetingpassword' => 'required|string',
        ]);

        $event = Event::where('id', $request->eventid)
            ->where('superid', $request->superid)
            ->first();

        if (!$event) {
            return response()->json(['error' => true, 'message' => 'Event not found']);
        }

        $event->update($request->only([
            'eventname',
            'eventdescription',
            'eventlevel',
            'meetingid',
            'meetingpassword'
        ]));

        return response()->json([
            'error' => false,
            'message' => 'Event Update successfully',
            'event' => $event
        ]);
    }

    public function deleteEvent(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
            'eventid' => 'required|integer',
        ]);

        $event = Event::where('id', $request->eventid)
            ->where('superid', $request->superid)
            ->first();

        if (!$event) {
            return response()->json(['error' => true, 'message' => 'Event not found']);
        }

        $event->delete();

        return response()->json([
            'error' => false,
            'message' => 'Event deleted successfully'
        ]);
    }



    public function getEventById(Request $request)
    {
        $request->validate([
            'superid' => 'required|integer',
            'eventid' => 'required|integer',
        ]);

        $event = Event::where('id', $request->eventid)
            ->where('superid', $request->superid)
            ->first();

        if (!$event) {
            return response()->json(['error' => true, 'message' => 'Event not found']);
        }

        return response()->json([
            'error' => false,
            'event' => $event
        ]);
    }


    public function listLiveEvents()
    {
        $events = Event::where('estatus', true)->get();
        //offlince events are not shown to users, only live events are listed
        // $offlinceEvents = Event::where('estatus', false)->get();

        return response()->json([
            'error' => false,
            'events' => $events,
            // 'offline_events' => $offlinceEvents
        ]);
    }




    public function generateEventJwtold(Request $request)
    {
        $request->validate([
            'eid' => 'required|exists:events,id',
            'userdeviceid' => 'required|string'
        ]);

        $event = Event::find($request->eid);

        if (!$event || !$event->estatus) {
            return response()->json([
                'error' => true,
                'message' => 'Event not found or inactive'
            ]);
        }

        // $sdkKey = config('services.zoom.sdk_key');
        $sdkKey = 'WW2q78PkTumfgClrjkRpcA';
        // $sdkSecret = config('services.zoom.sdk_secret');
        $sdkSecret = 'LHh85zD9vG4awE1f7yE8lKAxzU3rJ2Wo';

        $iat = time();
        $exp = $iat + (60 * 60 * 2); // 2 hours

        $payload = [
            'sdkKey' => $sdkKey,
            'mn' => $event->meetingid,
            'role' => 0,
            'iat' => $iat,
            'exp' => $exp,
            'appKey' => $sdkKey,
            'tokenExp' => $exp
        ];

        $jwt = JWT::encode($payload, $sdkSecret, 'HS256');

        return response()->json([
            'error' => false,
            'signature' => $jwt
        ]);
    }


    public function generateEventJwt()
    {
        $sdkKey = 'WW2q78PkTumfgClrjkRpcA';
        $sdkSecret = 'LHh85zD9vG4awE1f7yE8lKAxzU3rJ2Wo';

        $iat = time();
        $exp = $iat + (60 * 60 * 2);

        $payload = [
            'appKey' => $sdkKey,
            'iat' => $iat,
            'exp' => $exp,
            'tokenExp' => $exp
        ];

        $jwt = JWT::encode($payload, $sdkSecret, 'HS256');

        return response()->json([
            'error' => false,
            'token' => $jwt
        ]);
    }
}
