<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Click;
use Wncms\Models\Traffic;
use Wncms\Models\Website;
use Carbon\Carbon;
use CyrildeWit\EloquentViewable\Support\Period;
use Illuminate\Http\Request;
use Wncms\Models\Tag;

class AnalyticsController extends Controller
{
    private $orders = [
        'order',
        'traffic_total',
        'traffic_week',
        'traffic_month',
        'clicks',
        'created_at',
        'expired_at',
        'updated_at',
    ];

    private $groups = [
        'none',
        'website',
        'link',
        'ip',
    ];

    public function index(Request $request)
    {
        dd("功能開發中，更換package，移除 CyrildeWit\EloquentViewable\Support\Period");
        //! Website data
        if(isAdmin()){
            $q = Website::query();
        }else{
            $q = auth()->user()->websites();
        }

        if($request->keyword){
            $q->where(function($subq) use($request){
                $subq->where('domain', 'like', "%$request->keyword%")
                ->orWhere('site_name', 'like', "%$request->keyword%");
            });
        }
        $q->orderBy('id', 'desc');
        $websites = $q->get();

        $websiteAnalyticsDataSets = [];
        // views($model)->collection($collection)->record();

        $periods = [
            'today' => Period::pastDays(0),
            'yesterday' => Period::pastDays(1),
            'recent_week' => Period::pastWeeks(1),
            'recent_month' => Period::pastMonths(1),
            'recent_year' => Period::pastYears(1),
            'total' => Period::upto(now()),
        ];

        foreach($websites as $website){
            foreach($periods as $periodName => $period){
                $count = views($website)->period($period)->collection('view')->count();
                $websiteAnalyticsDataSets[$website->domain][$periodName] = $count;
            }
        }

        return view('wncms::backend.analytics.index', [
            'page_title' => __('wncms::word.website_analytics'),
            'websites' => $websites,
            'websiteAnalyticsDataSets' => $websiteAnalyticsDataSets,
        ]);
    }

    public function show_traffic(Request $request)
    {
        if (isAdmin()) {
            $websites = Website::all();
        } else {
            $websites = auth()->user()->websites;
        }

        if ($websites->isEmpty()) return redirect()->route('links.index');

        $link_categories = Tag::getWithType('link_category')->pluck('name')->toArray();
        
        $traffics = Traffic::query()
            ->whereBelongsTo($websites)
            ->when($request->website, function ($q) use ($request) {
                $q->whereRelation('website', 'websites.id', $request->website);
            })
            ->when($request->keyword, function ($q) use ($request) {
                $q->where(function($q) use ($request){
                    $q->where('ip',$request->keyword)->orWhere('domain','like',"%". urlencode($request->keyword) ."%");
                });
            })
            ->latest()
            ->paginate(100);

            // dd($traffics->random()->first());
        return view('wncms::backend.analytics.traffic_log', [
            'websites' => $websites,
            'link_categories' => $link_categories,
            'traffics' => $traffics,
            'groups' => $this->groups,
            'page_title' => __('wncms::word.analytics_management')
        ]);
    }

    public function show_click(Request $request)
    {
        if (isAdmin()) {
            $websites = Website::all();
        } else {
            $websites = auth()->user()->websites;
        }
        $website_ids = $websites->pluck('id')->toArray();

        if ($websites->isEmpty()) return redirect()->route('links.index');

        $link_categories = Tag::getWithType('link_category')->pluck('name')->toArray();

        $clicks = Click::query()
            ->where(function ($q) use ($website_ids, $request) {
                if ($request->website) {
                    $q->whereHas('link', function ($q) use ($request) {
                        $q->whereHas('website', function ($q) use ($request) {
                            $q->where('id', $request->website);
                        });
                    });
                } else {
                    $q->whereHas('link', function ($q) use ($website_ids) {
                        $q->whereHas('website', function ($q) use ($website_ids) {
                            $q->whereIn('id', $website_ids);
                        });
                    });
                }
            })
            ->latest()
            ->paginate(100);

        return view('wncms::backend.analytics.click_log', [
            'websites' => $websites,
            'link_categories' => $link_categories,
            'clicks' => $clicks,
            'groups' => $this->groups,
            'page_title' => __('wncms::word.analytics_management')
        ]);
    }
}
