@extends('wncms::layouts.backend')

@push('head_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
@endpush

@section('content')

@include('wncms::backend.parts.message')

{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ ($activeTab ?? 'basic') === 'basic' ? 'active' : '' }}"
           data-bs-toggle="tab" href="#post-main" role="tab">
            @lang('wncms::word.basic')
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ ($activeTab ?? 'basic') === 'comments' ? 'active' : '' }}"
           data-bs-toggle="tab" href="#post-comments" role="tab">
            @lang('wncms::word.comments')
        </a>
    </li>
    @php($hookTabHeaders = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tabs', [$post, request()])))
    @foreach ($hookTabHeaders as $hookTabHeader)
        {!! $hookTabHeader !!}
    @endforeach
</ul>

<div class="tab-content">
    {{-- Main Post Form --}}
    <div class="tab-pane fade {{ ($activeTab ?? 'basic') === 'basic' ? 'show active' : '' }}" id="post-main" role="tabpanel">
        <form class="form" method="POST" action="{{ route('posts.update', ['id' => $post->id]) }}" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            <input type="hidden" name="active_tab" class="js-active-tab-input" value="{{ $activeTab ?? 'basic' }}">
            @include('wncms::backend.posts.form-items')

            <div class="mt-5">
                <button type="submit" class="btn btn-dark fw-bold">@lang('wncms::word.save')</button>
            </div>
        </form>
    </div>

    {{-- Comments Tab --}}
    <div class="tab-pane fade {{ ($activeTab ?? 'basic') === 'comments' ? 'show active' : '' }}" id="post-comments" role="tabpanel">
        @include('wncms::backend.posts.comment-list', [
            'comments' => $comments,
            'commentOrder' => $commentOrder ?? 'newest',
        ])
    </div>
    @php($hookTabContents = array_filter(\Illuminate\Support\Facades\Event::dispatch('backend_posts_edit_tab_contents', [$post, request()])))
    @foreach ($hookTabContents as $hookTabContent)
        {!! $hookTabContent !!}
    @endforeach
</div>

@endsection

@push('foot_js')
@include('wncms::common.js.tinymce')

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
        const activeTabInputs = document.querySelectorAll('.js-active-tab-input');
        const guestOptionValue = '__guest__';
        const commentsUserSearchUrl = @json(route('comments.users'));
        const commentGuestText = @json(__('wncms::word.guest'));
        const searchAuthorPlaceholder = @json(__('wncms::word.search') . ' ' . __('wncms::word.author'));

        const syncActiveTab = (href) => {
            const activeTab = href === '#post-comments' ? 'comments' : 'basic';
            activeTabInputs.forEach((input) => {
                input.value = activeTab;
            });
        };

        tabLinks.forEach(link => {
            link.addEventListener("shown.bs.tab", function (e) {
                syncActiveTab(e.target.getAttribute("href"));
            });
        });

        const initialActiveLink = document.querySelector('a[data-bs-toggle="tab"].active');
        if (initialActiveLink) {
            syncActiveTab(initialActiveLink.getAttribute('href'));
        }

        const debounce = (callback, delay = 250) => {
            let timer = null;

            return (...args) => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => callback(...args), delay);
            };
        };
        const commentUserTagifyInstances = [];

        const normalizeCommentUser = (user) => ({
            value: String(user.id ?? guestOptionValue),
            name: user.username ?? commentGuestText,
            email: user.email ?? ''
        });

        const getGuestUserOption = () => ({
            value: guestOptionValue,
            name: commentGuestText,
            email: ''
        });

        const mergeCommentUserOptions = (options) => {
            const seen = new Set();

            return options.filter((option) => {
                if (!option || typeof option.value === 'undefined') {
                    return false;
                }

                const optionValue = String(option.value);
                if (seen.has(optionValue)) {
                    return false;
                }

                seen.add(optionValue);
                option.value = optionValue;

                return true;
            });
        };

        const commentUserTagTemplate = function (tagData) {
            return `
                <tag title="${tagData.email || tagData.name}"
                    contenteditable="false"
                    spellcheck="false"
                    tabIndex="-1"
                    class="${this.settings.classNames.tag} ${tagData.class ? tagData.class : ''}"
                    ${this.getAttributes(tagData)}>
                    <x title="" class="tagify__tag__removeBtn" role="button" aria-label="remove tag"></x>
                    <div class="d-flex align-items-center">
                        <span class="tagify__tag-text">${tagData.name}</span>
                    </div>
                </tag>
            `;
        };

        const commentUserSuggestionTemplate = function (tagData) {
            return `
                <div ${this.getAttributes(tagData)}
                    class="tagify__dropdown__item d-flex align-items-center ${tagData.class ? tagData.class : ''}"
                    tabindex="0"
                    role="option">
                    <div class="d-flex flex-column">
                        <strong>${tagData.name}</strong>
                        <span>${tagData.email || '&nbsp;'}</span>
                    </div>
                </div>
            `;
        };

        const updateCommentUserHiddenInput = (container, tagData) => {
            const hiddenInput = container.querySelector('.js-comment-user-id');

            if (!hiddenInput) {
                return;
            }

            hiddenInput.value = tagData && tagData.value !== guestOptionValue ? tagData.value : '';
        };

        const buildInitialCommentUser = (input) => {
            const initialId = input.dataset.initialId || '';
            const initialIsGuest = input.dataset.initialIsGuest === '1';

            if (!initialId && !initialIsGuest) {
                return null;
            }

            return {
                value: initialId || guestOptionValue,
                name: input.dataset.initialName || commentGuestText,
                email: input.dataset.initialEmail || ''
            };
        };

        const loadCommentUsers = debounce(async (tagify, query) => {
            const selectedOption = tagify.value[0] || buildInitialCommentUser(tagify.DOM.originalInput);
            const requestUrl = new URL(commentsUserSearchUrl, window.location.origin);

            if (query) {
                requestUrl.searchParams.set('keyword', query);
            }

            try {
                const response = await fetch(requestUrl.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const payload = await response.json();
                const userOptions = Array.isArray(payload.users) ? payload.users.map(normalizeCommentUser) : [];

                tagify.whitelist = mergeCommentUserOptions([
                    getGuestUserOption(),
                    ...(selectedOption ? [selectedOption] : []),
                    ...userOptions
                ]);

                tagify.dropdown.show(query);
            } catch (error) {
                console.error('Failed to search comment users.', error);
            }
        }, 300);

        const closeOtherCommentUserDropdowns = (currentTagify) => {
            commentUserTagifyInstances.forEach((instance) => {
                if (instance !== currentTagify) {
                    instance.dropdown.hide();
                }
            });
        };

        const closeAllCommentUserDropdowns = () => {
            closeOtherCommentUserDropdowns(null);
        };

        const initCommentUserSelector = (container) => {
            const input = container.querySelector('.js-comment-user-tagify-input');

            if (!input || input.dataset.tagifyReady === '1') {
                return;
            }

            const initialUser = buildInitialCommentUser(input);
            const tagify = new Tagify(input, {
                tagTextProp: 'name',
                enforceWhitelist: true,
                skipInvalid: true,
                maxTags: 1,
                dropdown: {
                    enabled: 0,
                    maxItems: 50,
                    classname: 'comment-user-dropdown',
                    searchKeys: ['name', 'email'],
                    highlightFirst: true
                },
                templates: {
                    tag: commentUserTagTemplate,
                    dropdownItem: commentUserSuggestionTemplate
                },
                whitelist: mergeCommentUserOptions([
                    getGuestUserOption(),
                    ...(initialUser ? [initialUser] : [])
                ])
            });
            commentUserTagifyInstances.push(tagify);

            input.dataset.tagifyReady = '1';
            input.setAttribute('placeholder', searchAuthorPlaceholder);

            if (initialUser) {
                tagify.addTags([initialUser], true, true);
                updateCommentUserHiddenInput(container, initialUser);
            }

            tagify.on('focus', () => {
                closeOtherCommentUserDropdowns(tagify);
                loadCommentUsers(tagify, '');
            });
            tagify.on('input', (event) => {
                closeOtherCommentUserDropdowns(tagify);
                loadCommentUsers(tagify, event.detail.value.trim());
            });
            tagify.on('dropdown:select', (event) => {
                const selectedUser = event.detail.data;

                if (!selectedUser) {
                    return;
                }

                tagify.removeAllTags(true);
                tagify.addTags([selectedUser], true, true);
                updateCommentUserHiddenInput(container, selectedUser);
                tagify.dropdown.hide();
            });
            tagify.on('dropdown:show', () => closeOtherCommentUserDropdowns(tagify));
            tagify.on('add', (event) => updateCommentUserHiddenInput(container, event.detail.data));
            tagify.on('remove', () => updateCommentUserHiddenInput(container, null));
        };

        if (typeof Tagify !== 'undefined') {
            document.querySelectorAll('[data-comment-user-selector]').forEach((container) => {
                initCommentUserSelector(container);
            });
        }

        document.addEventListener('click', (event) => {
            const dropdownElement = event.target.closest('.comment-user-selector, .tagify__dropdown');

            if (!dropdownElement) {
                closeAllCommentUserDropdowns();
            }
        });

        const closeCommentReply = (commentItem) => {
            const replyPanel = commentItem.querySelector('.js-comment-reply-panel');
            if (!replyPanel) {
                return;
            }

            bootstrap.Collapse.getOrCreateInstance(replyPanel, { toggle: false }).hide();
        };

        const closeCommentEdit = (commentItem) => {
            const editPanel = commentItem.querySelector('.js-comment-edit-panel');
            const viewPanel = commentItem.querySelector('.js-comment-view-panel');

            if (!editPanel) {
                return;
            }

            editPanel.classList.add('d-none');
            if (viewPanel) {
                viewPanel.classList.remove('d-none');
            }
        };

        document.querySelectorAll('.js-comment-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const commentItem = button.closest('.js-comment-item');
                const target = button.dataset.commentToggle;

                if (!commentItem || !target) {
                    return;
                }

                if (target === 'reply') {
                    closeCommentEdit(commentItem);
                    const replyPanel = commentItem.querySelector('.js-comment-reply-panel');
                    bootstrap.Collapse.getOrCreateInstance(replyPanel, { toggle: false }).toggle();
                }

                if (target === 'edit') {
                    closeCommentReply(commentItem);
                    const editPanel = commentItem.querySelector('.js-comment-edit-panel');
                    const viewPanel = commentItem.querySelector('.js-comment-view-panel');
                    const isOpen = !editPanel.classList.contains('d-none');

                    if (isOpen) {
                        editPanel.classList.add('d-none');
                        if (viewPanel) {
                            viewPanel.classList.remove('d-none');
                        }
                    } else {
                        editPanel.classList.remove('d-none');
                        if (viewPanel) {
                            viewPanel.classList.add('d-none');
                        }
                    }
                }
            });
        });

        document.querySelectorAll('.js-comment-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const commentItem = button.closest('.js-comment-item');
                const target = button.dataset.commentCancel;

                if (!commentItem || !target) {
                    return;
                }

                if (target === 'reply') {
                    closeCommentReply(commentItem);
                }

                if (target === 'edit') {
                    closeCommentEdit(commentItem);
                }
            });
        });
    });
</script>
@endpush
