<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Faq;
use Wncms\Models\Tag;
use Wncms\Models\User;
use Wncms\Models\Website;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    protected $website;
    protected $theme;
    protected $faq_tags;
    protected $cacheTag = ['faqs'];

    public function __construct()
    {
        $this->website = wncms()->website()->get();
        if (!$this->website) redirect()->route('websites.create')->send();
        $this->theme = $this->website->theme ?? 'default';
        $this->faq_tags = wncms()->tag()->getArray(tagType:"faq_tag",columnName:"name");
    }

    public function index(Request $request)
    {
        $q = Faq::query();

        $selectedWebsiteId = $request->website ?? session('selected_website_id');
        if($selectedWebsiteId){
            $q->whereHas('website', function ($subq) use ($selectedWebsiteId) {
                $subq->where('websites.id', $selectedWebsiteId);
            });
        }elseif(!$request->has('website')){
            $websiteId = wncms()->website()->get()?->id;
            $q->whereHas('website', function ($subq) use ($websiteId) {
                $subq->where('websites.id', $websiteId);
            });
        }

        if (in_array($request->status, Faq::STATUSES)) {
            $q->where('status', $request->status);
        }

        if ($request->keyword) {
            $q->where('slug', 'like', "%$request->keyword%")
                ->orWhereRaw("JSON_EXTRACT(question, '$.*') LIKE '%$request->keyword%'")
                ->orWhereRaw("JSON_EXTRACT(answer, '$.*') LIKE '%$request->keyword%'");
        }

        if ($request->tag) {
            $q->withAnyTags([$request->tag], 'faq_tag');
        }

        if (in_array($request->order, Faq::ORDERS)) {
            $q->orderBy($request->order, in_array($request->sort, ['asc', 'desc']) ? $request->sort : 'desc');
        }

        $q->with(['tags', 'website']);

        $q->orderBy('created_at', 'desc');
        $q->orderBy('id', 'desc');

        $faqs = $q->paginate($request->page_size ?? 50);

        $faq_tag_parants = Tag::where('type','faq_tag')->whereNull('parent_id')->with('children')->get();


        return view('wncms::backend.faqs.index', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'faqs' => $faqs,
            'faq_tag_parants' => $faq_tag_parants,
            'orders' => Faq::ORDERS,
            'statuses' => Faq::STATUSES,
            'websites' => wncms()->website()->getList(),
        ]);
    }

    public function create(Faq $faq = null)
    {
        $websites = wncms()->website()->getList();
        $isCloning = !empty($faq) ? true : false;
        return view('wncms::backend.faqs.create', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'statuses' => Faq::STATUSES,
            'faq' => $faq ??= new Faq,
            'faq_tags' => $this->faq_tags,
            'websites' => $websites,
            'isCloning' => $isCloning,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        if(empty($request->website_id)) return back()->withInput()->withErrors(['message' => __('word.website_is_not_selected')]);

        $faq = Faq::create([
            'website_id' => $request->website_id,
            'status' => $request->status,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('faqs'),
            'question' => $request->question,
            'answer' => $request->answer,
            'label' => $request->label,
            'remark' => $request->remark,
            'order' => $request->order,
            'is_pinned' => $request->is_pinned == 1 ? true : false,
        ]);

        $faq->syncTagsFromTagify($request->faq_tags, 'faq_tag');

        wncms()->cache()->flush($this->cacheTag);

        return redirect()->route('faqs.edit', [
            'faq' => $faq,
        ])->withMessage(__('word.successfully_created'));
    }

    public function edit(Faq $faq)
    {
        if (isAdmin()) {
            $users = User::all();
            $websites = Website::all();
        } else {
            $users = User::where('id', auth()->id())->get();
            $websites = auth()->user()->websites;
        }

        return view('wncms::backend.faqs.edit', [
            'page_title' =>  wncms_model_word('faq', 'management'),
            'faq' => $faq,
            'statuses' => Faq::STATUSES,           
            'faq_tags' => $this->faq_tags,
            'websites' => $websites,
        ]);
    }

    public function update(Request $request, Faq $faq)
    {
        // dd($request->all());
        $faq->update([
            'website_id' => $request->website_id,
            'status' => $request->status,
            'slug' => $request->slug ?? wncms()->getUniqueSlug('faqs'),
            'question' => $request->question,
            'answer' => $request->answer,
            'label' => $request->label,
            'remark' => $request->remark,
            'order' => $request->order,
            'is_pinned' => $request->is_pinned == 1 ? true : false,
        ]);

        $faq->syncTagsFromTagify($request->faq_tags, 'faq_tag');

        wncms()->cache()->flush($this->cacheTag);
        
        return redirect()->route('faqs.edit', [
            'faq' => $faq,
        ])->withMessage(__('word.successfully_updated'));
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('faqs.index')->withMessage(__('word.successfully_deleted'));
    }

    public function bulk_delete(Request $request)
    {
        Faq::whereIn('id', explode(",", $request->model_ids))->delete();
        return redirect()->route('faqs.index')->withMessage(__('word.successfully_deleted'));
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Frontend routes
     * ----------------------------------------------------------------------------------------------------
     */
    public function single($slug)
    {
        $faq = wncms()->faq()->getBySlug($slug);
        if (!$faq) return redirect()->route('frontend.pages.blog');
        // record view can now be toggle in theme option and call dynamically in template
        // RecordViews::dispatch($faq->id);
        return wncms_view('frontend.theme.' . $this->theme . '.faqs.single', [
            'faq' => $faq,
        ]);
    }

    public function category($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('faq_category', $tagName);
    }

    public function tag($tagName = null)
    {
        if(empty($tagName)){
            return route('frontend.pages.home');
        }
        return $this->archive('faq_tag', $tagName);
    }

    public function archive($tagType, $tagName = null)
    {
        // dd($tagName,$tagType);
        if(empty($tagName)){
            return route('frontend.pages.home');
        }

        $tag = wnTag()->getByName(
            tagName:$tagName,
            tagType:$tagType,
            withs: ['siblings', 'children'],
        );
        
        if(!$tag) return redirect()->route('frontend.faqs.search_result', [
            'keyword' => $tagName,
        ]);


        // dd($tag->siblings);
        $modelName = explode("_", $tagType)[0] ?? '';

        $count = gto('faq_limit', 0);
        $page = request()->page ?? 0;
        $pageSize = gto('faq_page_size', 10);
        $faqs = wncms()->faq()->getList(count:$count, page: $page, pageSize:$pageSize, tagType: 'faq_tag', tags: $tagName);

        return wncms_view('frontend.theme.' . $this->theme . '.faqs.archive', [
            'pageTitle' => __('word.latest_tag_models', ['tagName' => $tagName, 'modelName' => __('word.' . $modelName)]),
            'tagName' => $tagName,
            'tagType' => $tagType,
            'faqs' => $faqs,
            'tag' => $tag,
        ]);
    }



    public function search(Request $request)
    {
        // TODO: search limit

        // TODO: filter keywords

        //search keyword
        return redirect()->route('frontend.faqs.search_result', [
            'keyword' => $request->keyword,
        ]);
    }

    public function search_result(Request $request, $keyword)
    {
        // TODO: add to gss or gto
        $pageSize = gto('archive_faq_count', 10);
        $faqs = wncms()->faq()->search(
            keyword: $keyword,
            pageSize: $pageSize,
            page:$request->page,
        );

        return wncms_view('frontend.theme.' . $this->theme . '.faqs.search', [
            'pageTitle' => __('word.search_result_of', ['keyword' => $keyword]),
            'faqs' => $faqs,
            'keyword' => $keyword,
        ]);
    }
}
