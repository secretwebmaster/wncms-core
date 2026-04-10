<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Wncms\Models\Channel;

class ClickController extends ApiV2Controller
{
    public function summary(Request $request)
    {
        try {
            $modelClass = wncms()->getModelClass('click');
            $q = $modelClass::query();

            if ($request->filled('name')) {
                $q->where('name', $request->name);
            }
            if ($request->filled('value')) {
                $q->where('value', $request->value);
            }
            if ($request->filled('clickable_id')) {
                $q->where('clickable_id', $request->clickable_id);
            }
            if ($request->filled('clickable_type')) {
                $q->where('clickable_type', $request->clickable_type);
            }
            if ($request->filled('channel')) {
                $channel = Channel::query()->where('slug', $request->channel)->first();
                if ($channel) {
                    $q->where('channel_id', $channel->id);
                }
            }
            if ($request->filled('keyword')) {
                $q->where(function ($subq) use ($request) {
                    $subq->where('ip', 'like', $request->keyword)
                        ->orWhere('name', 'like', '%' . $request->keyword . '%')
                        ->orWhere('value', 'like', '%' . $request->keyword . '%')
                        ->orWhere('referer', 'like', '%' . $request->keyword . '%');
                });
            }

            $start = $request->filled('start_datetime')
                ? Carbon::parse($request->start_datetime)
                : now()->subDays(30)->startOfDay();
            $end = $request->filled('end_datetime')
                ? Carbon::parse($request->end_datetime)
                : now()->endOfDay();
            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }
            $q->whereBetween('created_at', [$start, $end]);

            $totalClicks = (clone $q)->count();
            $perPage = max(1, min((int) $request->input('page_size', 20), 100));

            $summaryRows = (clone $q)
                ->selectRaw('clickable_type, clickable_id, COUNT(*) as total, MAX(id) as record_id')
                ->groupBy('clickable_type', 'clickable_id')
                ->orderByDesc('total')
                ->paginate($perPage);

            $dateList = collect();
            $current = $start->copy()->startOfDay();
            $endDate = $end->copy()->startOfDay();
            while ($current <= $endDate) {
                $dateList->push($current->format('Y-m-d'));
                $current->addDay();
            }
            $dateList = $dateList->reverse()->values();

            $dailyCountsByKey = collect();
            if ($summaryRows->isNotEmpty()) {
                $pairs = $summaryRows->getCollection()->map(function ($row) {
                    return [
                        'clickable_type' => $row->clickable_type,
                        'clickable_id' => $row->clickable_id,
                    ];
                });

                $dailyRows = (clone $q)
                    ->selectRaw('clickable_type, clickable_id, DATE(created_at) as date, COUNT(*) as count')
                    ->where(function ($pairQuery) use ($pairs) {
                        foreach ($pairs as $pair) {
                            $pairQuery->orWhere(function ($subq) use ($pair) {
                                $subq->where('clickable_type', $pair['clickable_type'])
                                    ->where('clickable_id', $pair['clickable_id']);
                            });
                        }
                    })
                    ->groupBy('clickable_type', 'clickable_id', DB::raw('DATE(created_at)'))
                    ->get();

                $dailyCountsByKey = $dailyRows
                    ->groupBy(fn($row) => $row->clickable_type . '|' . $row->clickable_id)
                    ->map(fn($rows) => $rows->pluck('count', 'date'));
            }

            $rows = $summaryRows->getCollection()->map(function ($row) use ($dateList, $dailyCountsByKey) {
                $key = $row->clickable_type . '|' . $row->clickable_id;
                $daily = $dailyCountsByKey->get($key, collect());
                return [
                    'record_id' => $row->record_id,
                    'clickable_type' => $row->clickable_type,
                    'clickable_type_label' => class_basename((string) $row->clickable_type),
                    'clickable_id' => $row->clickable_id,
                    'total' => (int) $row->total,
                    'daily_counts' => $dateList->mapWithKeys(fn($date) => [$date => (int) ($daily[$date] ?? 0)]),
                ];
            })->values();

            return $this->ok($rows, 'success', 200, [
                'pagination' => [
                    'current_page' => $summaryRows->currentPage(),
                    'per_page' => $summaryRows->perPage(),
                    'total' => $summaryRows->total(),
                    'last_page' => $summaryRows->lastPage(),
                ],
                'summary' => [
                    'total_clicks' => $totalClicks,
                    'date_list' => $dateList,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }
}
