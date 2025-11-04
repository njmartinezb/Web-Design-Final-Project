<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Justification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            // Para administradores: mostrar todas las justificaciones
            $justifications = Justification::with(['class.faculty', 'student', 'documents'])
                ->when($request->filled('status'), function($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->filled('search'), function($query) use ($request) {
                    $query->where(function($q) use ($request) {
                        $q->where('description', 'like', '%'.$request->search.'%')
                          ->orWhereHas('student', function($q) use ($request) {
                              $q->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('email', 'like', '%'.$request->search.'%');
                          })
                          ->orWhereHas('class', function($q) use ($request) {
                              $q->where('name', 'like', '%'.$request->search.'%');
                          });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();
        } else {
            // Para usuarios normales: mostrar solo sus justificaciones
            $justifications = Justification::with(['class.faculty', 'student', 'documents'])
                ->where('student_id', $user->id)
                ->when($request->filled('status'), function($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->filled('search'), function($query) use ($request) {
                    $query->where(function($q) use ($request) {
                        $q->where('description', 'like', '%'.$request->search.'%')
                          ->orWhereHas('class', function($q) use ($request) {
                              $q->where('name', 'like', '%'.$request->search.'%');
                          });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();
        }

        return view('dashboard', compact('justifications'));
    }

    public function approve(Justification $justification)
    {
        // Verificar que el usuario es administrador
        if (Auth::user()->role !== 'admin') {
            abort(403, 'No tienes permisos para aprobar justificaciones.');
        }

        // Ejecutar mediante Command Pattern
        \App\Jobs\CommandHandlerJob::dispatch(
            new \App\Justifications\Commands\ApproveJustification($justification->id)
        );

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'message' => 'Justificación aprobada exitosamente.'
        ]);
    }

    public function reject(Justification $justification)
    {
        // Verificar que el usuario es administrador
        if (Auth::user()->role !== 'admin') {
            abort(403, 'No tienes permisos para rechazar justificaciones.');
        }

        // Ejecutar mediante Command Pattern
        \App\Jobs\CommandHandlerJob::dispatch(
            new \App\Justifications\Commands\RejectJustification($justification->id)
        );

        return redirect()->back()->with('alert', [
            'type' => 'success',
            'message' => 'Justificación rechazada exitosamente.'
        ]);
    }

    public function showJustification(Justification $justification)
    {
        $justification->load(['class.faculty', 'student', 'documents']);
        // Solo admins o el dueño pueden ver
        if (auth()->user()->role !== 'admin' && $justification->student_id !== auth()->id()) {
            abort(403);
        }
        return view('dashboard.justification', compact('justification'));
    }
}
