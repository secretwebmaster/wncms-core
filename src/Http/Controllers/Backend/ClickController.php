<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Channel;
use Wncms\Models\Parameter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClickController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

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
            'page_title' => wncms_model_word('click', 'management'),
            'clicks' => $clicks,
            'channels' => $channels,
            'parameters' => $parameters,
            'clickableTypes' => $clickableTypes,
            'chartLabels' => $chartLabels,
            'chartCounts' => $chartCounts,
        ]);
    }
}
