<div class="auth-modal-signup modal-wrapper" style="display:none">
    <div class="modal-content modal-header-none">
        <div class="sign-up-new">

            <div class="sign-up-new__header">
                <div class="sign-up-new__close-btn">
                    <svg class="icon icon-close-3">
                        <use xlink:href="#icons-close-3"></use>
                    </svg>
                </div>
            </div>
            
            <div class="sign-up-new__body">
                {{-- 表格 --}}
                <div class="sign-up-new__left">
                    <div class="sign-up-new__left-top">
                        <header class="sign-up-new__title">@lang('wncms::word.register')</header>
                        <form class="sign-up-new-form sign-up-new__center-wrapper" action="{{ route('frontend.members.register') }}" method="POST">
                            @csrf
                            <div class="sign-up-new-form__field">
                                <input class="block input sign-up-new-form__input sign-up-new-form__input--with-icon text-default theme-transparent-light-10" type="text" name="username" maxlength="25" placeholder="@lang('wncms::word.username')">
                            </div>
                            <div class="sign-up-new-form__field">
                                <input class="block input sign-up-new-form__input sign-up-new-form__input--with-icon text-default theme-transparent-light-10" type="text" name="email" placeholder="@lang('wncms::word.email')">
                            </div>
                            <div class="sign-up-new-form__field">
                                <input class="block input sign-up-new-form__input sign-up-new-form__input--with-icon text-default theme-transparent-light-10" type="password" name="password" placeholder="@lang('wncms::word.password')">
                            </div>
                            <div class="sign-up-new-form__field">
                                <input class="block input sign-up-new-form__input sign-up-new-form__input--with-icon text-default theme-transparent-light-10" type="password" name="password_confirmation" placeholder="@lang('wncms::word.password_confirmation')">
                            </div>
                          
                            <div class="sign-up-new-form__field sign-up-new-form__field--hidden">
                                <div class="sign-up-new-form__input-wrapper"><input class="block email-default input sign-up-new-form__input theme-transparent-light-10" type="email" id="sign_up_input_email" maxlength="255" placeholder="电子邮件" autocapitalize="none" value=""></div>
                                <div class="sign-up-new-form__field-description">我们将会把密码发送到此邮箱</div>
                            </div>
                            <div class="sign-up-new-form__submit"><button class="btn btn-block btn-login-alternative btn-medium sign-up-new-form__submit-btn" type="submit">创建免费帐户</button></div>
                        </form>
                    </div>
                    <div class="sign-up-new__left-bottom">
                        <div class="sign-up-new__center-wrapper sign-up-new__center-wrapper--with-signup-without-email sign-up-new__third-party-authorization third-party-authorization third-party-authorization--full-width third-party-authorization--new-style">
                            <div class="third-party-authorization__separator"><span class="third-party-authorization__separator-label">或者继续与</span></div>
                            <div class="third-party-authorization__wrapper">
                                <div class="twitter-auth-wrapper" id="twitter_authorization_id">
                                    <div class="twitter-auth">
                                        <div class="twitter-auth-icon"><svg class="icon icon-twitter" style="height: 20px; width: 20px;">
                                                <use xlink:href="#icons-twitter"></use>
                                            </svg></div>
                                        <div class="twitter-auth-label">Twitter</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sign-up-new__form-bottom-actions">
                            <div class="media-after-l-hidden sign-up-new__terms-warning">通过注册，即表示您同意 <a target="_blank" href="/terms">使用条款</a></div>
                            <div class="media-up-to-m-hidden sign-up-new__login-proposal">已有账户？<a class="sign-up-new__login-link" href="/login">登录</a></div>
                        </div>
                    </div>
                </div>

                {{-- 圖片 --}}
                <div class="sign-up-new__right">
                    <div class="sign-up-new-banner-image sign-up-new-banner-image--user sign-up-new__banner" style="background-image: linear-gradient(0deg, rgb(0, 0, 0) 11.03%, rgba(0, 0, 0, 0) 93.53%), url(&quot;https://cdn.strpst.com/assets/users/components/ui/SignUp/images/jpg-x2/photo-girls-asia-wc-1.jpg&quot;);">
                        <ul class="sign-up-new-benefits">
                            <li class="sign-up-new-benefits__benefit"><svg class="icon icon-who-can-chat sign-up-new-benefits__icon">
                                    <use xlink:href="#icons-who-can-chat"></use>
                                </svg><span class="sign-up-new-benefits__text sign-up-new-benefits__text--shadow">与<span class="sign-up-new-benefits__accent-word">主播</span>聊天</span></li>
                            <li class="sign-up-new-benefits__benefit"><svg class="icon icon-lovense sign-up-new-benefits__icon">
                                    <use xlink:href="#icons-lovense"></use>
                                </svg><span class="sign-up-new-benefits__text sign-up-new-benefits__text--shadow">使用<span class="sign-up-new-benefits__accent-word">互动玩具</span></span></li>
                            <li class="sign-up-new-benefits__benefit"><svg class="icon icon-heart-fill sign-up-new-benefits__icon">
                                    <use xlink:href="#icons-heart-fill"></use>
                                </svg><span class="sign-up-new-benefits__text sign-up-new-benefits__text--shadow">在<span class="sign-up-new-benefits__accent-word">私人表演</span>中享受乐趣</span></li>
                            <li class="sign-up-new-benefits__benefit"><svg class="icon icon-gift sign-up-new-benefits__icon">
                                    <use xlink:href="#icons-gift"></use>
                                </svg><span class="sign-up-new-benefits__text sign-up-new-benefits__text--shadow">参加<span class="sign-up-new-benefits__accent-word">赠品</span></span></li>
                            <li class="media-up-to-s-hidden sign-up-new-benefits__benefit"><svg class="icon icon-bookmark-filled sign-up-new-benefits__icon">
                                    <use xlink:href="#icons-bookmark-filled"></use>
                                </svg><span class="sign-up-new-benefits__text sign-up-new-benefits__text--shadow">保存<span class="sign-up-new-benefits__accent-word">最爱的主播们和内容</span></span></li>
                        </ul>
                        <div class="sign-up-new-banner-image__caption"></div>
                    </div>
                </div>
            </div>

            <div class="sign-up-new__footer media-after-l-hidden"><span class="sign-up-new__login-proposal">已经是会员？<a class="sign-up-new__login-link" href="/login">在此登录</a></span></div>
            <div class="sign-up-new__footer media-up-to-m-hidden">
                <div class="sign-up-new__terms-warning">通过注册，即表示您同意 <a target="_blank" href="/terms">使用条款</a></div>
            </div>
        </div>
    </div>
    <div class="ios-with-keyboard-scroll-fix"></div>
</div>


@push('foot_js')
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            $('.sidebar-trigger').off().on('click', function(e){
                e.stopPropagation();
                console.log($('.sidebar-dialog-open').length);
                if(window.innerWidth < 768){
                    if(!$('.sidebar-dialog-open').length){
                        console.log('opening');
                        show_sidebar()
                    }else{
                        console.log('closnig');
                        hide_sidebar();
                    }
                }else{
                    // alert('d')
                }
            })

            function show_sidebar(){
                $('body').css('height', '');
                $('body').css('overflow-y', '');
                $('body').css('position', '');
                $('.sidebar-overlay-overlay').addClass('sidebar-overlay-overlay-open');
                $('.app-sidebar-content.modal-base-body.sidebar-dialog').addClass('sidebar-dialog-open');

                //點擊任何地方都可以關
                $("body").off().on('click', function (e) {
                    if(window.innerWidth < 768){
                        if(!e.target.closest('.sidebar-dialog-open')){
                            console.log('click anywhere out side')
                            hide_sidebar()
                        }
                    }
                });
            }


            function hide_sidebar(){
                $('body').css('height', '100vh');
                $('body').css('overflow-y', 'hidden');
                $('body').css('position', 'fixed');
                $('.sidebar-overlay-overlay').removeClass('sidebar-overlay-overlay-open');
                $('.app-sidebar-content.modal-base-body.sidebar-dialog').removeClass('sidebar-dialog-open');
            }

            //search
            $('.model-search.model-search--compact').off().on('click', function(){

                var compact_search = $('.model-search.model-search--compact:not(.model-search--expanded)');
                var expanded_search = $('.model-search.model-search--compact.model-search--expanded');

                compact_search.hide();
                expanded_search.show();
                compact_search.closest('.container').addClass('search-expanded');

                expanded_search.find('.close-button').off().on('click', function(e){
                    e.stopPropagation();
                    compact_search.show();
                    expanded_search.hide();
                    compact_search.closest('.container').removeClass('search-expanded');
                });
            })

            //sign up
            $('.btn-signup').on('click', function(){
                $('html').addClass("disable-scroll");
                $('body').css('padding-right',0);
                $('body').css('margin-top',0);
                var model_sign_up =  $('.auth-modal-signup')
                
                model_sign_up.show();
                model_sign_up.find('.sign-up-new__close-btn').off().on('click', function(){
                    model_sign_up.hide();
                    $('html').removeClass("disable-scroll");
                    $('body').css('padding-right',"");
                    $('body').css('margin-top',"");
                })
            })


        })
    </script>
@endpush