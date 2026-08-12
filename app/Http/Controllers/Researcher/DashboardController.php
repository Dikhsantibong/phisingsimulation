<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\BehaviorStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the researcher overview with study-progress statistics.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'total' => Respondent::count(),
                'clicked' => SimulationEvent::whereNotNull('first_access_at')->count(),
                'behaviorCompleted' => SimulationEvent::whereNotNull('response_at')->count(),
                'questionnaireCompleted' => QuestionnaireResult::count(),
            ],
            'statusBreakdown' => $this->breakdown(
                Respondent::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                RespondentStatus::cases(),
            ),
            'behaviorBreakdown' => $this->breakdown(
                SimulationEvent::whereNotNull('response_at')
                    ->selectRaw('behavior_status, count(*) as total')
                    ->groupBy('behavior_status')
                    ->pluck('total', 'behavior_status'),
                BehaviorStatus::cases(),
            ),
        ]);
    }

    /**
     * Build a label/value breakdown covering every enum case (including zeros).
     *
     * @param  Collection<string, int>  $counts
     * @param  array<int, BehaviorStatus|RespondentStatus>  $cases
     * @return array<int, array{key: string, label: string, value: int}>
     */
    private function breakdown($counts, array $cases): array
    {
        return collect($cases)->map(fn ($case) => [
            'key' => $case->value,
            'label' => $case->label(),
            'value' => (int) ($counts[$case->value] ?? 0),
        ])->all();
    }
}
