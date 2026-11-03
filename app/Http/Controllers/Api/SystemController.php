<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use App\Models\Classes;
use App\Models\Room;

class SystemController extends Controller
{
    public function config()
    {
        $config = SystemConfiguration::current();

        return response()->json([
            'success' => true,
            'data' => [
                'active_cycle'       => $config->active_cycle,
                'is_schedule_locked' => $config->is_schedule_locked,
            ]
        ]);
    }

    public function classes()
    {
        $classes = Classes::with([
            'homeroomTeacher:id,fullname,class_id',
            'students:id,fullname,class_id',
        ])
            ->orderBy('class_name')
            ->get(['id', 'class_name']);
        $classes->each(function (Classes $class) {
            $class->homeroomTeacher?->makeHidden('class_id');
            $class->students->each(fn ($student) => $student->makeHidden('class_id'));
        });

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    public function rooms()
    {
        $rooms = Room::orderBy('room_name')->get(['id', 'room_name']);

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }
}