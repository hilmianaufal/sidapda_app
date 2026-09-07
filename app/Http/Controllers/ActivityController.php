<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $category = $this->categoryFromRequest($request);
        $activities = Activity::query()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('order')
            ->get();

        return view('activities.index', compact('activities', 'category'));
    }

    public function edit(Request $request, Activity $activity)
    {
        $returnCategory = $this->categoryFromRequest($request) ?? $activity->category;

        return view('activities.edit', compact('activity', 'returnCategory'));
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:routine,manual'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'between:0,6'],
            'category' => ['required', 'in:umum,diniyah'],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'late_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'is_active' => ['nullable'],
        ]);

        $activity->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'category' => $data['category'],
            'days' => $data['type'] === 'routine' ? ($data['days'] ?? []) : null,
            'event_date' => $data['type'] === 'manual' ? ($data['event_date'] ?? null) : null,
            'start_time' => strlen($data['start_time']) === 5 ? $data['start_time'].':00' : $data['start_time'],
            'end_time' => strlen($data['end_time']) === 5 ? $data['end_time'].':00' : $data['end_time'],
            'late_minutes' => (int) $data['late_minutes'],
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('activities.index', ['category' => $data['category']])
            ->with('success', 'Jadwal kegiatan berhasil diperbarui.');
    }

    public function create(Request $request)
    {
        $category = $this->categoryFromRequest($request);

        return view('activities.create', compact('category'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:routine,manual'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'between:0,6'],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'category' => ['required', 'in:umum,diniyah'],
            'late_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'is_active' => ['nullable'],
        ]);

        Activity::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'category' => $data['category'],
            'days' => $data['type'] === 'routine'
                ? ($data['days'] ?? [])
                : null,

            'event_date' => $data['type'] === 'manual'
                ? ($data['event_date'] ?? null)
                : null,

            'order' => Activity::max('order') + 1,

            'start_time' => strlen($data['start_time']) === 5
                ? $data['start_time'].':00'
                : $data['start_time'],

            'end_time' => strlen($data['end_time']) === 5
                ? $data['end_time'].':00'
                : $data['end_time'],

            'late_minutes' => (int) $data['late_minutes'],
            'is_active' => (bool) $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('activities.index', ['category' => $data['category']])
            ->with('success', 'Kegiatan berhasil ditambahkan.');
    }


    public function destroy(Request $request, Activity $activity)
    {
        $category = $this->categoryFromRequest($request) ?? $activity->category;
        $activity->delete();

        return redirect()
            ->route('activities.index', ['category' => $category])
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    private function categoryFromRequest(Request $request): ?string
    {
        $category = $request->query('category');

        return in_array($category, ['umum', 'diniyah'], true) ? $category : null;
    }
}
