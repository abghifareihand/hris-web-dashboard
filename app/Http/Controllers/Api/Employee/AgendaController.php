<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgendaController extends Controller
{
    /**
     * Get list of agendas for the authenticated employee
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Agenda::where('user_id', $user->id);

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('status')) {
            if ($request->status === 'priority') {
                $query->where('is_priority', true);
            } elseif ($request->status === 'non_priority') {
                $query->where('is_priority', false);
            }
        }

        $agendas = $query->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate($this->getPerPage($request));

        $agendas->getCollection()->transform(function ($agenda) {
            return [
                'id' => $agenda->id,
                'title' => $agenda->title,
                'date' => $agenda->date->format('Y-m-d'),
                'start_time' => Carbon::parse($agenda->start_time)->format('H:i'),
                'end_time' => Carbon::parse($agenda->end_time)->format('H:i'),
                'notes' => $agenda->notes,
                'is_priority' => $agenda->is_priority,
                'created_at' => $agenda->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->paginatedResponse($agendas, 'Agenda berhasil diambil.');
    }

    /**
     * Store a new agenda
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
            'is_priority' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $agenda = Agenda::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'notes' => $request->notes,
            'is_priority' => $request->is_priority ?? false,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Agenda berhasil dibuat.'
        ], 201);
    }

    /**
     * Update an agenda
     */
    public function update(Request $request, $id)
    {
        $agenda = Agenda::where('user_id', $request->user()->id)->find($id);

        if (!$agenda) {
            return response()->json([
                'status' => false,
                'message' => 'Agenda tidak ditemukan.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i',
            'notes' => 'nullable|string',
            'is_priority' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $agenda->update($request->only([
            'title', 'date', 'start_time', 'end_time', 'notes', 'is_priority'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Agenda berhasil diperbarui.',
            'data' => [
                'id' => $agenda->id,
                'title' => $agenda->title,
                'date' => $agenda->date->format('Y-m-d'),
                'start_time' => Carbon::parse($agenda->start_time)->format('H:i'),
                'end_time' => Carbon::parse($agenda->end_time)->format('H:i'),
                'notes' => $agenda->notes,
                'is_priority' => $agenda->is_priority,
            ]
        ]);
    }

    /**
     * Delete an agenda
     */
    public function destroy(Request $request, $id)
    {
        $agenda = Agenda::where('user_id', $request->user()->id)->find($id);

        if (!$agenda) {
            return response()->json([
                'status' => false,
                'message' => 'Agenda tidak ditemukan.'
            ], 404);
        }

        $agenda->delete();

        return response()->json([
            'status' => true,
            'message' => 'Agenda berhasil dihapus.'
        ]);
    }
}
