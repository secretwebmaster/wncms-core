@if($menu_item->type == 'page')
@php($menuItemPage = wncms()->page()->get(['id' => $menu_item->model_id]))
<li @if(!empty($li_class))class="{{ $li_class }}"@endif>
    <a @if(!empty($a_class))class="{{ $a_class }}"@endif href="{{ $menu_item->validated_url }}" title="{{ $menu_item->resolved_name }}">{{ $menu_item->resolved_name }}</a>
</li>

@elseif($menu_item->type == 'external_link')
<li @if(!empty($li_class))class="{{ $li_class }}"@endif>
    <a @if(!empty($a_class))class="{{ $a_class }}"@endif href="{{ $menu_item->validated_url }}" title="{{ $menu_item->resolved_name }}">{{ $menu_item->resolved_name }}</a>
</li>

@elseif($menu_item->type == 'post_category')
<li @if(!empty($li_class))class="{{ $li_class }}"@endif>
    <a @if(!empty($a_class))class="{{ $a_class }}"@endif href="{{ route('frontend.posts.post_taxonomy', ['post_taxonomy_type' => 'category', 'taxonomy_name' => $menu_item->resolved_name]) }}" title="{{ $menu_item->resolved_name }}">{{ $menu_item->resolved_name }}</a>
</li>

@elseif($menu_item->type == 'post_tag')
@endif
