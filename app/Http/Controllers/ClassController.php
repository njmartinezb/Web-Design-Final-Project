<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;


use App\Models\Faculty;
use App\Models\Professor;

use Illuminate\Support\Facades\DB;
use App\Models\UniversityClass;
use App\Models\ClassGroup;
use App\Models\ClassDay;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = UniversityClass::query()
            ->with("faculty:id,name")
            ->withCount("groups")
            ->when($request->filled('name'), fn($q) =>
                $q->where('name', 'like', '%'.$request->name.'%')
            )
            ->orderBy(
                $request->get('sort_by', 'name'),
                $request->get('sort_dir', 'asc')
            )
            ->paginate(
                $request->get('per_page', 15)
            )
            ->withQueryString();
            return view('classes.index', [
                'classes' => $query,
                'filters' => $request->all(),
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $faculties = Faculty::all();
        $professors = Professor::all();
        return view('classes.create',compact('faculties','professors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'faculty_id'             => 'required|integer|exists:faculties,id',
            'groups'                 => 'required|array',
            'groups.*.class_id'      => 'nullable|integer|exists:classes,id',
            'groups.*.professor_id'  => 'required|integer|exists:professors,id',
            'groups.*.days'          => 'required|array|min:1',
            'groups.*.days.*.weekday'         => 'required|string',
            'groups.*.days.*.start_time'      => 'required|date_format:H:i',
            'groups.*.days.*.duration_in_min' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        $class = UniversityClass::create([
            'name'       => $data['name'],
            'faculty_id' => $data['faculty_id'],
        ]);

        foreach ($data['groups'] as $groupPayload) {
            $groupPayload['class_id'] = $class->id;
            $daysPayload = $groupPayload['days'];
            unset($groupPayload['days']);

            $group = ClassGroup::create($groupPayload);

            foreach ($daysPayload as $dayPayload) {
                $dayPayload['group_id'] = $group->id;
                ClassDay::create($dayPayload);
            }
        }

        DB::commit();

        $full = UniversityClass::with([
            'faculty',
            'groups',
            'groups.professor',
            'groups.days',
        ])->findOrFail($data['groups'][0]['class_id'] ?? $class->id);


        return redirect()->route('classes.index')
            ->with('alert', [
                'type'    => 'success',
                'message' => 'Clase creada exitosamente.'
            ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $faculties = Faculty::all();
        $professors = Professor::all();
        $class = UniversityClass::findOrFail($id);
        return view('classes.edit', compact('faculties', 'professors', 'class'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
         $data = $request->validate([
            'name'                   => 'required|string|max:255',
            'faculty_id'             => 'required|integer|exists:faculties,id',
            'groups'                 => 'required|array',
            'groups.*.id'            => 'sometimes|integer|exists:class_groups,id',
            'groups.*.professor_id'  => 'required|integer|exists:professors,id',
            'groups.*.days'          => 'required|array|min:1',
            'groups.*.days.*.id'              => 'sometimes|integer|exists:class_days,id',
            'groups.*.days.*.weekday'         => 'required|integer|between:0,6',
            'groups.*.days.*.start_time'      => 'required|date_format:H:i:s',
            'groups.*.days.*.duration_in_min' => 'required|integer|min:1',
        ]);

        Log::info("request " . $request);

        DB::transaction(function() use ($data, $id) {
            $class = UniversityClass::with('groups.days')->findOrFail($id);

            $class->update([
                'name'       => $data['name'],
                'faculty_id' => $data['faculty_id'],
            ]);

            $existingGroupIds = $class->groups->pluck('id')->all();
            $incomingGroupIds = [];

            foreach ($data['groups'] as $gp) {
                if (! empty($gp['id'])) {
                    $group = $class->groups->firstWhere('id', $gp['id']);
                    $group->update(['professor_id' => $gp['professor_id']]);
                } else {
                    $group = $class->groups()->create([
                        'professor_id' => $gp['professor_id'],
                    ]);
                }
                $incomingGroupIds[] = $group->id;
                $existingDayIds  = $group->days->pluck('id')->all();
                $incomingDayIds  = [];

                foreach ($gp['days'] as $dp) {
                    $dayData = [
                        'weekday'         => $dp['weekday'],
                        'start_time'      => $dp['start_time'],
                        'duration_in_min' => $dp['duration_in_min'],
                    ];

                    if (!empty($dp['id'])) {
                        $day = $group->days->firstWhere('id', $dp['id']);
                        $day->update($dayData);
                    } else {
                        $day = $group->days()->create($dayData);
                    }
                    $incomingDayIds[] = $day->id;
                }

                $daysToDelete = array_diff($existingDayIds, $incomingDayIds);
                if (count($daysToDelete)) {
                    ClassDay::whereIn('id', $daysToDelete)->delete();
                }
            }

            $groupsToDelete = array_diff($existingGroupIds, $incomingGroupIds);
            if (count($groupsToDelete)) {
                ClassGroup::whereIn('id', $groupsToDelete)->delete();
            }
        });

        $updated = UniversityClass::with([
            'faculty',
            'groups.professor',
            'groups.days',
        ])->findOrFail($id);

        return redirect()->route('classes.index')
            ->with('alert', [
                'type'    => 'success',
                'message' => 'Clase actualizada exitosamente.'
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function() use ($id) {
            $class = UniversityClass::with('groups.days')->findOrFail($id);

            foreach ($class->groups as $group) {
                $group->days()->delete();
            }

            $class->groups()->delete();

            $class->delete();
        });

        return response()->json(null, 204);
    }

    public function details(UniversityClass $class) {
        $class->load([
            'faculty:id,name',
            'groups.professor:id,first_name,last_name',
            'groups.days:id,group_id,weekday,start_time,duration_in_min',
        ]);

        return response()->json($class);
    }
}
