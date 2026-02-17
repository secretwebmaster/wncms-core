<?php

namespace Wncms\Http\Controllers\Backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Wncms\Models\Channel;
use Wncms\Models\Parameter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClickController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        if ($request->name) {
            $q->where('name', $request->name);
        }

        if ($request->value) {
            $q->where('value', $request->value);
        }

        if ($request->clickable_id) {
            $q->where('clickable_id', $request->clickable_id);
        }

        if ($request->clickable_type) {
            $q->where('clickable_type', $request->clickable_type);
        }

        if ($request->channel) {
            $channel = Channel::where('slug', $request->channel)->first();
            if ($channel) {
                $q->where('channel_id', $channel->id);
            }
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
                $subq->where('ip', 'like', $request->keyword)
                    ->orWhere('name', 'like', "%$request->keyword%")
                    ->orWhere('value', 'like', "%$request->keyword%")
                    ->orWhere('referer', 'like', "%$request->keyword%");
            });
        }

        // Date filtering (and fallback for chart)
        $start = $request->filled('start_datetime')
            ? Carbon::parse($request->start_datetime)
            : now()->subDays(30)->startOfDay();

        $end = $request->filled('end_datetime')
            ? Carbon::parse($request->end_datetime)
            : now()->endOfDay();

        $q->whereBetween('created_at', [$start, $end]);

        // Chart Data
        $clicksQueryForChart = clone $q;

        // get paginated data for table
        $q->orderBy('id', 'desc');
        $q->with(['clickable', 'channel']);
        $clicks = $q->paginate($request->page_size ?? 100);
        $clicks->setCollection($clicks->getCollection()->map(function ($click) {
            $click->clickable_type_label = $this->resolveClickableTypeLabel($click->clickable_type);
            return $click;
        }));

        $parameters = Parameter::all();
        $dateRange = collect();
        $chartLabels = [];
        $current = $start->copy();

        while ($current <= $end) {
            $fullDate = $current->format('Y-m-d'); // for key matching
            $shortDate = $current->format('n.j');  // for label display
            $dateRange->push($fullDate);
            $chartLabels[] = $shortDate;
            $current->addDay();
        }

        $clickData = $clicksQueryForChart
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $chartCounts = $dateRange->map(fn($date) => $clickData[$date] ?? 0)->toArray();

        // dd(
        //     $chartLabels,
        //     $chartCounts,
        // );

        // $clickableTypes = $this->modelClass::pluck('clickable_type')->unique();

        // Get all clickable model types recorded in clicks
        $rawClickableTypes = $this->modelClass::pluck('clickable_type')->unique()->filter();

        // Map each clickable type to a display name
        $clickableTypes = $rawClickableTypes->mapWithKeys(function ($type) {
            if (class_exists($type) && method_exists($type, 'getModelName')) {
                return [$type => $type::getModelName()];
            }

            return [$type => class_basename($type)];
        });

        // dd($clickableTypes);
        $channels = Channel::all();

        return $this->view('backend.clicks.index', [
            'page_title' => wncms()->getModelWord('click', 'management'),
            'clicks' => $clicks,
            'channels' => $channels,
            'parameters' => $parameters,
            'clickableTypes' => $clickableTypes,
            'chartLabels' => $chartLabels,
            'chartCounts' => $chartCounts,
        ]);
    }

    public function summary(Request $request)
    {
        $q = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($q);

        if ($request->name) {
            $q->where('name', $request->name);
        }

        if ($request->value) {
            $q->where('value', $request->value);
        }

        if ($request->clickable_id) {
            $q->where('clickable_id', $request->clickable_id);
        }

        if ($request->clickable_type) {
            $q->where('clickable_type', $request->clickable_type);
        }

        if ($request->channel) {
            $channel = Channel::where('slug', $request->channel)->first();
            if ($channel) {
                $q->where('channel_id', $channel->id);
            }
        }

        if ($request->keyword) {
            $q->where(function ($subq) use ($request) {
                $subq->where('ip', 'like', $request->keyword)
                    ->orWhere('name', 'like', "%$request->keyword%")
                    ->orWhere('value', 'like', "%$request->keyword%")
                    ->orWhere('referer', 'like', "%$request->keyword%");
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

        $rawClickableTypes = (clone $q)->select('clickable_type')->distinct()->pluck('clickable_type')->filter();
        $clickableTypes = $rawClickableTypes->mapWithKeys(function ($type) {
            if (class_exists($type) && method_exists($type, 'getModelName')) {
                return [$type => $type::getModelName()];
            }
            return [$type => class_basename($type)];
        });

        $channels = Channel::all();
        $totalClicks = (clone $q)->count();

        $summaryRows = (clone $q)
            ->selectRaw('clickable_type, clickable_id, COUNT(*) as total, MAX(id) as record_id')
            ->groupBy('clickable_type', 'clickable_id')
            ->orderByDesc('total')
            ->paginate($request->page_size ?? 100);

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

        $clickableNamesByKey = $this->buildClickableNamesByKey($summaryRows->getCollection());

        $summaryRows->setCollection($summaryRows->getCollection()->map(function ($row) use ($dateList, $dailyCountsByKey, $clickableNamesByKey) {
            $key = $row->clickable_type . '|' . $row->clickable_id;
            $daily = $dailyCountsByKey->get($key, collect());

            $row->clickable_type_label = $this->resolveClickableTypeLabel($row->clickable_type);
            $row->clickable_name = $clickableNamesByKey[$key] ?? '';
            $row->daily_counts = $dateList->mapWithKeys(fn($date) => [$date => (int) ($daily[$date] ?? 0)]);

            return $row;
        }));

        return $this->view('backend.clicks.summary', [
            'page_title' => wncms()->getModelWord('click', 'summary'),
            'summaryRows' => $summaryRows,
            'totalClicks' => $totalClicks,
            'channels' => $channels,
            'clickableTypes' => $clickableTypes,
            'dateList' => $dateList,
        ]);
    }

    private function buildClickableNamesByKey(Collection $summaryRows): array
    {
        $clickableNamesByKey = [];

        foreach ($summaryRows->groupBy('clickable_type') as $type => $rows) {
            if (!is_string($type) || !class_exists($type) || !is_subclass_of($type, Model::class)) {
                continue;
            }

            $model = new $type;
            $ids = $rows->pluck('clickable_id')->filter(fn($id) => filled($id))->unique()->values();

            if ($ids->isEmpty()) {
                continue;
            }

            $items = $type::query()
                ->whereIn($model->getKeyName(), $ids->all())
                ->get();

            foreach ($items as $item) {
                $clickableNamesByKey[$type . '|' . $item->getKey()] = $this->resolveClickableName($item);
            }
        }

        return $clickableNamesByKey;
    }

    private function resolveClickableName(Model $clickable): string
    {
        foreach (['name', 'title', 'slug', 'key'] as $attr) {
            $value = $clickable->getAttribute($attr);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return (string) $clickable->getKey();
    }

    private function resolveClickableTypeLabel(?string $clickableType): string
    {
        if (is_string($clickableType) && class_exists($clickableType) && method_exists($clickableType, 'getModelName')) {
            return $clickableType::getModelName();
        }

        return class_basename((string) $clickableType);
    }
}
