<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\BehaviorStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RespondentController extends Controller
{
    /**
     * Show the (filterable) respondent data table.
     */
    public function index(Request $request): Response
    {
        $respondents = Respondent::query()
            ->with('simulationEvent')
            ->when($request->filled('class_group'), fn ($query) => $query->where('class_group', $request->string('class_group')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('session_token', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Respondent $respondent) => [
                'id' => $respondent->id,
                'token' => $respondent->session_token,
                'name' => $respondent->name,
                'class_group' => $respondent->class_group,
                'email' => $respondent->email,
                'status' => $respondent->status->value,
                'status_label' => $respondent->status->label(),
                'behavior_status' => $respondent->simulationEvent?->behavior_status?->value,
                'behavior_label' => $respondent->simulationEvent?->behavior_status?->label(),
                'device_type' => $respondent->simulationEvent?->device_type?->value,
                'sent_at' => $respondent->simulationEvent?->sent_at?->toIso8601String(),
                'first_access_at' => $respondent->simulationEvent?->first_access_at?->toIso8601String(),
                'response_at' => $respondent->simulationEvent?->response_at?->toIso8601String(),
            ]);

        return Inertia::render('respondents/index', [
            'respondents' => $respondents,
            'filters' => $request->only('class_group', 'status', 'search'),
            'classGroups' => Respondent::query()->distinct()->orderBy('class_group')->pluck('class_group'),
            'statuses' => collect(RespondentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'behaviorStatuses' => collect(BehaviorStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    /**
     * Delete a respondent from the database.
     */
    public function destroy(Respondent $respondent): \Illuminate\Http\RedirectResponse
    {
        $respondent->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Data responden berhasil dihapus.',
        ]);

        return back();
    }
}
