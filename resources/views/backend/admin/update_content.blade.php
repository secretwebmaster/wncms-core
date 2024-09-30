<div class="accordion" id="update_log">
    @foreach($result['data'] ?? [] as $itemType => $itemData)
    {{-- @dd($itemData) --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="update_log_header_{{ $itemData['name'] }}">
                <button class="accordion-button fs-4 fw-bold bg-dark text-gray-100 shadow-none p-3 accordion-arrow-white" type="button" data-bs-toggle="collapse" data-bs-target="#update_log_body_{{ $itemData['name'] }}" aria-expanded="true" aria-controls="update_log_body_{{ $itemData['name'] }}">
                    <span>
                        <span>{{ $itemData['name'] }}</span>
                        <small class="text-warning ms-3">@lang('word.latest_version'): {{  $itemData['latest_version'] ?? '-'  }}</small>
                    </span>
                </button>
            </h2>

            <div id="update_log_body_{{ $itemData['name'] }}" class="accordion-collapse collapse @if($loop->iteration == 1) show @endif" aria-labelledby="update_log_header_{{ $itemData['name'] }}" data-bs-parent="#update_log">
                <div class="accordion-body  border border-dark border-3 p-2">
                    @forelse($itemData['updates'] as $update)
                    {{-- @dd($update) --}}
                        <div class="card card-flush mb-10 px-3">
                            {{-- <div class="card-header bg-secondary"> --}}
                            <div class="border-bottom border-3 border-secondary pb-2 mt-3">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <h3 class="card-label fw-bold me-3 m-0 d-flex align-item-center">
                                        <span>@lang('word.version') {{ $update['version'] ?? '' }}</span>
                                        @if($loop->index == 0)<span class="badge badge-sm badge-exclusive badge-danger fw-boldpx-2 py-1 ms-2">New</span>@endif
                                        @if(($update['version'] ?? '') == gss('version'))<span class="badge badge-sm badge-exclusive badge-info fw-boldpx-2 py-1 ms-2">@lang('word.your_version')</span>@endif
                                    </h3>
                                    <span class="text-gray-400 fw-bold fs-6">{{ \Carbon\Carbon::parse($update['released_at'] ?? '')->format('Y-m-d') }}</span>
                                </div>
                            </div>

                            <div class="card-body pt-6 px-3">
                                <div class="timeline-label">
                                    {{-- Content --}}
                                    @foreach($update['content'] as $update_type => $update_items)
                                        {{-- Item --}}
                                        @foreach ($update_items as $item_index => $update_item)
                                            <div class="timeline-item d-flex align-items-center mb-3 text-break">
                                                <div class="timeline-label fw-bold text-{{ $colors[$update_type] ?? 'dark' }} fs-6">@if($item_index == 0)@lang('word.' . $update_type)@endif</div>
                                                <div class="timeline-badge">
                                                    <i class="fa fa-genderless text-{{ $colors[$update_type] ?? 'dark' }} fs-1"></i>
                                                </div>
                                                <div class="d-flex align-items-center"><span class="fw-bold text-gray-800 px-3">{{ $update_item }}</span></div>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    @empty
                        
                        <div class="card card-flush mb-10">
                            <div class="card-body pt-6 px-3">
                                <span>@lang('word.no_update_yet')</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach

</div>