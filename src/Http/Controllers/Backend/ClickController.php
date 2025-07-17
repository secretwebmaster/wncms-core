<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Models\Channel;
use Wncms\Models\Click;
use Wncms\Models\Link;
use Wncms\Models\Parameter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClickController extends BackendController
{
    public function getModelClass(): string
    {
        return config('wncms.models.click', \Wncms\Models\Click::class);
    }

    public function index(Request $request)
    {
        $q = Click::query();

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

        $clickableTypes = Click::pluck('clickable_type')->unique();
        $channels = Channel::all();

        return view('wncms::backend.clicks.index', [
            'page_title' => wncms_model_word('click', 'management'),
            'clicks' => $clicks,
            'channels' => $channels,
            'parameters' => $parameters,
            'clickableTypes' => $clickableTypes,
            'chartLabels' => $chartLabels,
            'chartCounts' => $chartCounts,
        ]);
    }


    public function create(?Click $click)
    {
        $click ??= new Click;

        return view('wncms::backend.clicks.create', [
            'page_title' =>  wncms_model_word('click', 'management'),
            'click' => $click,
        ]);
    }

    public function store(Request $request)
    {
        dd($request->all());

        $click = Click::create([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['clicks']);

        return redirect()->route('clicks.edit', [
            'click' => $click,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit(Click $click)
    {
        return view('wncms::backend.clicks.edit', [
            'page_title' =>  wncms_model_word('click', 'management'),
            'click' => $click,
        ]);
    }

    public function update(Request $request, Click $click)
    {
        dd($request->all());

        $click->update([
            'xxxx' => $request->xxxx,
        ]);

        wncms()->cache()->flush(['clicks']);

        return redirect()->route('clicks.edit', [
            'click' => $click,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function destroy(Click $click)
    {
        $click->delete();
        return redirect()->route('clicks.index')->withMessage(__('wncms::word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        if (!is_array($request->model_ids)) {
            $modelIds = explode(",", $request->model_ids);
        } else {
            $modelIds = $request->model_ids;
        }

        $count = Click::whereIn('id', $modelIds)->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('wncms::word.successfully_deleted_count', ['count' => $count]),
            ]);
        }

        return redirect()->route('clicks.index')->withMessage(__('wncms::word.successfully_deleted_count', ['count' => $count]));
    }
}
