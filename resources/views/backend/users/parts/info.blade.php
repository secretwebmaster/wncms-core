<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        {{-- Details --}}
        <div class="">
            {{-- Pic --}}
            <div class="me-7 mb-4 text-center">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    <img src="{{ auth()->user()->avatar }}" alt="image">
                    <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px"></div>
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1">
                {{-- Title --}}
                <div class="d-flex justify-content-center align-items-start flex-wrap mb-2 ">
                    {{-- User --}}
                    <div class="d-flex flex-column align-items-center">
                        {{-- Name --}}
                        <div class="d-flex align-items-center mb-3">
                            <a href="#" class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ auth()->user()->username }}</a>
                            <a href="#">@include('common.svg.verified')</a>
                            <a href="#" class="btn btn-sm btn-light-primary border border-1 border-primary fw-bold ms-2 fs-8 py-1 px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_upgrade_plan">{{ auth()->user()->active_subscription->plan?->name ?? __('word.free_plan') }}</a>
                        </div>
        
        
                        {{-- Info --}}
                        {{-- <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <a href="javascript:;" title="已使用字數10萬時解鎖" class="d-flex align-items-center text-gray-400 text-hover-warning me-5 mb-2"><i class="fa-solid fa-medal me-1"></i><span class="fw-bold">忘我寫手</span></a>
                            <a href="javascript:;" title="生成圖片數量1000時解鎖" class="d-flex align-items-center text-gray-400 text-hover-warning me-5 mb-2"><i class="fa-solid fa-medal me-1"></i><span class="fw-bold">瘋狂畫家</span></a>
                            <a href="javascript:;" title="建立10個網站時解鎖" class="d-flex align-items-center text-gray-400 text-hover-warning me-5 mb-2"><i class="fa-solid fa-medal me-1"></i><span class="fw-bold">網站狂魔</span></a>
                        </div> --}}
        
                    </div>
    
                </div>

            </div>
        </div>

    </div>
</div>


