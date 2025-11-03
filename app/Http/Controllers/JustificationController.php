<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Justification;
use App\Models\UniversityClass;
use App\Models\JustificationDocument;
use App\Models\ClassGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class JustificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Justification::with(['class.faculty', 'student', 'documents'])
            ->where('student_id', auth()->id())
            ->when($request->filled('search'), function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('description', 'like', '%'.$request->search.'%')
                          ->orWhereHas('class', function($q) use ($request) {
                              $q->where('name', 'like', '%'.$request->search.'%');
                          });
                });
            })
            ->orderBy(
                $request->get('sort_by', 'start_date'),
                $request->get('sort_dir', 'desc')
            )
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return view('justifications.index', [
            'justifications' => $query,
            'filters' => $request->all(),
        ]);
    }

    public function create()
    {
        $justification = new Justification();
        $classes = UniversityClass::with(['faculty','groups.days'])->get();
        return view('justifications.create', compact('justification', 'classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'university_class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) use ($request) {
                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($request->end_date);
                    $days = [];

                    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                        $days[] = $date->dayOfWeek;
                    }

                    $hasValidDays = ClassGroup::where('class_id', $value)
                        ->whereHas('days', function($q) use ($days) {
                            $q->whereIn('weekday', array_unique($days));
                        })->exists();

                    if (!$hasValidDays) {
                        $fail('La clase seleccionada no tiene horarios en las fechas indicadas.');
                    }
                }
            ],
            'documents.*' => 'required|file|max:2048|mimes:pdf,jpg,png'
        ]);

        DB::transaction(function () use ($data, $request) {
            $justification = Justification::create([
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'university_class_id' => $data['university_class_id'],
                'student_id' => auth()->id(),
                // Estado inicial por defecto válido
                'status' => Justification::STATUS_PENDING,
            ]);

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {

                    $justification->documents()->create([
                        'file_content' => file_get_contents($file->getRealPath()),
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ]);
                }
            }
        });

        return redirect()->route('justifications.index')->with('alert', [
            'type' => 'success',
            'message' => 'Justificación creada exitosamente.'
        ]);
    }

    public function show(Justification $justification)
    {
        if ($justification->student_id !== auth()->id()) {
            abort(403);
        }

        return view('justifications.show', [
            'justification' => $justification->load(['class.faculty', 'student', 'documents'])
        ]);
    }

    public function edit(Justification $justification)
    {
        if ($justification->student_id !== auth()->id()) {
            abort(403);
        }


        $classes = UniversityClass::with(['faculty','groups.days'])->get();
        return view('justifications.edit', [
            'justification' => $justification->load(['class.faculty', 'student', 'documents']),
            'classes' => $classes
        ]);
    }

    public function update(Request $request, Justification $justification)
    {
        if ($justification->student_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validate([
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'university_class_id' => [
                'required',
                'exists:classes,id',
                function ($attribute, $value, $fail) use ($request) {
                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($request->end_date);

                    $days = [];

                    for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                        $days[] = $date->dayOfWeek;
                    }

                    $hasValidDays = ClassGroup::where('class_id', $value)
                        ->whereHas('days', function($q) use ($days) {
                            $q->whereIn('weekday', array_unique($days));
                        })->exists();

                    if (!$hasValidDays) {
                        $fail('La clase seleccionada no tiene horarios en las fechas indicadas.');
                    }
                }
            ],
            'documents.*' => 'sometimes|file|max:2048|mimes:pdf,jpg,png',
            'remove_documents' => 'sometimes|array',
            'remove_documents.*' => 'exists:justification_documents,id'
        ]);

        DB::transaction(function () use ($data, $request, $justification) {
            $justification->update([
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'university_class_id' => $data['university_class_id'],
            ]);

            if ($request->has('remove_documents')) {
                foreach ($request->remove_documents as $documentId) {
                    $document = $justification->documents()->find($documentId);
                    if ($document) {
                        $document->delete();
                    }
                }
            }

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {

                    $justification->documents()->create([
                        'file_content' => file_get_contents($file->getRealPath()),
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ]);
                }
            }
        });

        return redirect()->route('justifications.index')->with('alert', [
            'type' => 'success',
            'message' => 'Justificación actualizada exitosamente.'
        ]);
    }

    public function destroy(Justification $justification)
    {
        if ($justification->student_id !== auth()->id()) {
            abort(403);
        }

        DB::transaction(function () use ($justification) {
            $justification->documents()->delete();
            $justification->delete();
        });

        return redirect()->route('justifications.index')->with('alert', [
            'type' => 'success',
            'message' => 'Justificación eliminada exitosamente.'
        ]);
    }

    public function downloadDocument(Justification $justification, JustificationDocument $document)
    {
        if ($justification->student_id !== auth()->id()) {
            abort(403, 'No tienes permisos para descargar este documento.');
        }

        if ($document->justification_id !== $justification->id) {
            abort(404, 'El documento no pertenece a esta justificación.');
        }

        return response($document->file_content, 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'attachment; filename="'.$document->file_name.'"');
    }


    public function getAvailableClasses(Request $request)
    {
        $validated = $request->validate([
            'weekday' => 'required|integer|between:0,6'
        ]);

        $weekday = $validated['weekday'];

        $classes = UniversityClass::query()
            ->with(['faculty', 'groups.days'])
            ->whereHas('groups.days', function($query) use ($weekday) {
                $query->where('weekday', $weekday);
            })
            ->get()
            ->map(function ($class) use ($weekday) {
                $class->setRelation('groups', $class->groups->filter(function ($group) use ($weekday) {
                    $group->setRelation('days', $group->days->filter(function ($day) use ($weekday) {
                        return $day->weekday == $weekday;
                    }));
                    return $group->days->where('weekday', $weekday)->isNotEmpty();
                })->values());
                return $class;
            })
            ->filter(function ($class) {
                return $class->groups->isNotEmpty();
            })
            ->values();

        return response()->json($classes->toArray());
    }


    public function updateStatus(Request $request, Justification $justification)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Justification::STATUS_PENDING,
                Justification::STATUS_APPROVED,
                Justification::STATUS_REJECTED,
            ])],
        ]);

        $justification->update(['status' => $data['status']]);

        return back()->with('alert', [
            'type'    => 'success',
            'message' => 'Estado actualizado correctamente.',
        ]);
    }

}
