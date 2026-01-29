/**
 * Menu Editor
 * Handles menu item management with drag-and-drop functionality
 */

// Global variables that will be set from PHP
let currentMenuData = [];
let currentLocale = 'en';
let defaultLocale = 'en';

/**
 * Initialize menu editor
 */
function initMenuEditor(menuData, locale, defLocale) {
    currentMenuData = menuData;
    currentLocale = locale;
    defaultLocale = defLocale;

    // Initialize Nestable plugin
    $('#nestable-json').nestable({
        json: currentMenuData
    });

    // Generate menu list
    fillMenu(currentMenuData);

    // Bind toggle events for expand/collapse
    $('.toggle-children').on('click', function() {
        $(this).toggleClass('collapsed');
        $(this).closest('.dd-item').children('.dd-list').toggleClass('d-none');
    });

    // Bind all event handlers
    bindEventHandlers();

    // Check if tags still exist
    checkTagIds();
}

/**
 * Bind all event handlers
 */
function bindEventHandlers() {
    // Add external link to menu
    $('.add_external_link_to_menu').on('click', handleAddExternalLink);

    // Add theme page to menu
    $('.add_theme_page_to_menu').on('click', handleAddThemePage);

    // Update menu
    $('.update_menu').off().on('click', handleUpdateMenu);

    // Expand all menu items
    $('.dd_expand_all').on('click', function() {
        $('.dd-item').removeClass('dd-collapsed');
    });

    // Collapse all menu items
    $('.dd_collapse_all').on('click', function() {
        $('.dd-item').addClass('dd-collapsed');
    });

    // Submit menu item edit from modal
    $("#modal_submit_form_edit_menu_item").click(handleModalSubmit);
}

/**
 * Handle adding external link
 */
function handleAddExternalLink() {
    var name = $("#menu_option_external_link").find('input[name="external_link_name"]').val();
    var url = $("#menu_option_external_link").find('input[name="external_link_url"]').val();
    var type = 'external_link';

    // Check if name and type are valid
    if (name !== undefined && name) {
        var newItem = {
            name: name,
            url: url,
            type: type,
        };
        $('#nestable-json').nestable('add', newItem);
    }

    var json = $('#nestable-json').nestable('serialize');
    fillMenu(json);
}

/**
 * Handle adding theme page
 */
function handleAddThemePage() {
    // Find all checked checkboxes
    var checkedCheckboxes = $("#menu_option_theme_page input[data-type='theme_page']:checked");

    // Process each checkbox
    checkedCheckboxes.each(function() {
        var name = $(this).data("name");
        var url = $(this).data("url");
        var type = $(this).data("type");

        // Generate item object
        if (name !== undefined && name) {
            var newItem = {
                name: name,
                url: url,
                type: type,
            };
            $('#nestable-json').nestable('add', newItem);
        }
    });

    // Convert item objects to json
    var json = $('#nestable-json').nestable('serialize');

    // Add new items to menu
    fillMenu(json);
}

/**
 * Handle menu update
 */
function handleUpdateMenu(e) {
    e.preventDefault();
    $(this).prop('disabled', true);
    var json = $('#nestable-json').nestable('serialize');
    var input = $("<input>").attr("type", "hidden").attr("name", "new_menu").val(JSON.stringify(json));
    $('#form_menu_update').append(input);
    $('#form_menu_update').submit();
}

/**
 * Handle modal submit for menu item edit
 */
function handleModalSubmit(e) {
    e.preventDefault();
    var btn = $(this);
    var menuItemId = $('#modal_edit_menu_item_id').val();

    // Get form data
    var form = $('#form_edit_menu_item')[0];
    var form_data = new FormData(form);

    // Send AJAX request to server
    $.ajax({
        url: window.wncmsMenuRoutes.editMenuItem,
        type: 'POST',
        processData: false,
        contentType: false,
        data: form_data,
        success: function(data) {
            if (data.status == 'success') {
                // Initialize Nestable with updated data
                $('#nestable-json').nestable({
                    json: data.menu
                });

                // Update the custom data attributes
                updateMenuItemDisplay(menuItemId, data.menu_item);

                form.reset();
                btn.prop('disabled', false);

                if (data.hide_modal) {
                    // Hide modal
                    $("#modal_edit_menu_item").modal("hide");
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: window.wncmsMenuTranslations.failed,
                });
            }
        }
    });
}

/**
 * Update menu item display after editing
 */
function updateMenuItemDisplay(menuItemId, menuItem) {
    // Update the custom data attributes
    $(`.dd-item[data-id="${menuItemId}"]`).data('name', menuItem.name).attr('data-name', menuItem.name);
    $(`.dd-item[data-id="${menuItemId}"]`).data('url', menuItem.url).attr('data-url', menuItem.url);
    $(`.dd-item[data-id="${menuItemId}"]`).data('description', menuItem.description).attr('data-description', menuItem.description);
    $(`.dd-item[data-id="${menuItemId}"]`).data('icon', menuItem.icon).attr('data-icon', menuItem.icon);
    $(`.dd-item[data-id="${menuItemId}"]`).data('is_new_window', menuItem.is_new_window).attr('data-is_new_window', menuItem.is_new_window);

    // Update the value of the input/element
    $(`.dd-item[data-id="${menuItemId}"] .dd-handle-name`).text(menuItem.name);
    $(`.dd-item[data-id="${menuItemId}"] input.menu_item_url`).val(menuItem.url).attr('value', menuItem.url);
    $(`.dd-item[data-id="${menuItemId}"] input.menu_item_is_new_window`).prop('checked', menuItem.is_new_window);
}

/**
 * Fill menu with data
 */
function fillMenu(data, parent) {
    var html = '';
    $.each(data, function(index, item) {
        // Get item name based on locale
        var item_name = item.name[currentLocale] != undefined ? item.name[currentLocale] :
            (item.name[defaultLocale] != undefined ? item.name[defaultLocale] : item.name);

        // Get item description
        var item_description = '';
        if (item.description != undefined) {
            if (item.description[currentLocale] != undefined) {
                item_description = item.description[currentLocale];
            }
        }

        // Render menu item data to the list
        html += '<li class="dd-item" data-id="' + (item.id ? item.id : generateRandomId()) +
            '" data-model-type="' + (item.model_type ?? item.modelType) +
            '" data-model-id="' + (item.model_id ?? item.modelId) +
            '" data-name="' + item_name +
            '" data-url="' + item.url +
            '" data-description="' + item_description +
            '" data-type="' + item.type +
            '" data-icon="' + item.icon +
            '" data-thumbnail="' + item.thumbnail +
            '" data-is_new_window="' + item.is_new_window + '">';
        
        // Collapse/expand buttons
        html += '<button class="dd-collapse" data-action="collapse" type="button">Collapse</button><button class="dd-expand" data-action="expand" type="button">Expand</button>';
        
        // Handle container with flex layout
        html += '<div class="dd-handle h-35px d-flex justify-content-between align-items-center">';
        
        // Left section: Name and URL
        html += '<div class="d-flex align-items-center gap-2">';
        html += '<span class="dd-handle-name">' + item_name + '</span>';
        html += '<span class="small text-muted fw-normal d-none d-inline">(' + item.type + ')</span>';
        
        // URL display
        if (item.type == 'external_link') {
            // Editable input for external links
            html += '<div class="d-inline-flex align-items-center dd-nodrag ms-2"><input class="menu_item_url" type="text" value="' + item.url + '"></div>';
        } else if (item.url && item.url !== 'null' && item.url !== 'undefined') {
            // Plain text display for other types
            html += '<span class="small text-muted ms-2">' + item.url + '</span>';
        }
        html += '</div>';
        
        // Right section: Action buttons and controls
        html += '<div class="d-flex align-items-center gap-2 dd-nodrag">';
        
        // Type hint
        html += '<span class="me-1 text-gray-400 d-none d-sm-inline small">' + item.type + '</span>';
        
        // New window checkbox
        html += '<div class="form-check form-check-sm form-check-custom form-check-solid mb-0 d-inline check_new_window">';
        html += '<input class="form-check-input menu_item_is_new_window" type="checkbox" title="' + window.wncmsMenuTranslations.newWindow + '"' + ((item.is_new_window || item.newWindow) && item.newWindow != 'undefined' ? 'checked' : '') + '>';
        html += '<label class="form-check-label small d-none d-inline ms-1">' + window.wncmsMenuTranslations.newWindow + '</label>';
        html += '</div>';

        // Edit icon
        if (item.id && !item.is_new) {
            html += '<i class="fa fa-edit text-info show_modal_edit_menu_item ms-2" style="cursor: pointer;"></i>';
        }
        
        // Delete icon
        html += '<i class="fa fa-trash text-danger remove_menu_item ms-2" style="cursor: pointer;"></i>';
        
        html += '</div>'; // Close right section
        html += '</div>'; // Close handle container

        // Render children
        if (item.children && item.children.length > 0) {
            html += '<ol class="dd-list">';
            html += fillMenu(item.children, item.id);
            html += '</ol>';
        }
        html += '</li>';
    });
    
    if (parent) {
        return html;
        $('[data-id="' + parent + '"]').children('.dd-list').html(html);
    } else {
        $('.dd-list').html(html);
    }
    
    bindClickEvents();
}

/**
 * Bind click events for menu items
 * This needs to be called every time items are generated
 */
function bindClickEvents() {
    // Remove menu item
    $('.remove_menu_item').on('click', function(e) {
        e.preventDefault();
        var menu_item_id = $(this).closest('.dd-item').data('id');
        $('.dd').nestable('remove', menu_item_id);
        var input = $("<input>")
            .attr("type", "hidden")
            .attr("name", "removes[]")
            .val(menu_item_id);
        $('#form_menu_update').append(input);
    });

    // Add checked items to Nestable list
    $('.add_to_menu').off().on('click', function() {
        var checkedItems = $(this).closest('.accordion-body').find('input:checked');
        var newItems = [];

        checkedItems.each(function() {
            // Check if name and type are valid
            if ($(this).data('name') !== undefined && $(this).data('type') !== undefined) {
                var newItem = {
                    id: null,
                    name: $(this).data('name'),
                    type: $(this).data('type'),
                    model_type: $(this).data('model-type'),
                    model_id: $(this).data('model-id'),
                    url: $(this).data('url') ? $(this).data('url') : null,
                    is_new_window: 0, // Default: not open in new window
                    is_new: true
                };
                newItems.push(newItem);
                $('#nestable-json').nestable('add', newItem);
            }
        });
        
        var json = $('#nestable-json').nestable('serialize');
        fillMenu(json);
        checkedItems.prop("checked", false);
    });

    // Update URL for external links
    $('.menu_item_url').off().on('change', function() {
        var new_url = $(this).val();
        var item = $(this).closest('.dd-item');
        item.attr('data-url', new_url);
        item.attr('data-id', item.data('id').toString());
    });

    // Toggle new window checkbox
    $('.check_new_window input[type="checkbox"]').on('change', function() {
        var new_window = $(this).closest('.dd-item');
        if (new_window.attr('data-is_new_window') == 1) {
            new_window.attr('data-is_new_window', 0);
        } else {
            new_window.attr('data-is_new_window', 1);
        }
    });

    // Show modal to edit menu item
    $(".show_modal_edit_menu_item").off().click(function() {
        // Get the dd-item element
        var dd_item = $(this).closest('.dd-item');
        
        // Extract the data attributes
        var id = dd_item.data('id');
        var url = dd_item.data('url');
        var description = dd_item.data('description');
        var thumbnail = dd_item.data('thumbnail');
        var type = dd_item.data('type');
        var model_type = dd_item.data('model-type');
        var model_id = dd_item.data('model-id');
        var is_new_window = dd_item.data('new-window');
        var icon = dd_item.data('icon');

        // Populate the form fields
        $('#modal_edit_menu_item_id').val(id);
        $('#modal_edit_menu_item_url').val(url);
        $('#modal_edit_menu_item_description').val(description);
        $('#current_menu_item_thumbnail').attr('src', thumbnail);
        $('#modal_edit_menu_item_type').val(type);
        if (model_type != "undefined") $('#modal_edit_menu_item_model_type').val(model_type);
        if (model_id != "undefined") $('#modal_edit_menu_item_model_id').val(model_id);
        $('#modal_edit_menu_item_is_new_window').prop('checked', is_new_window);
        $('#modal_edit_menu_item_icon').val(icon);

        // Activate button to load tag languages
        if (model_type == 'Tag') {
            $('.btn-fetch-tag-languages').show();
            $('.btn-fetch-tag-languages').attr('data-tag-id', model_id);
            $('.btn-fetch-tag-languages').off().on('click', function() {
                fetchTagLanguages(model_id);
            });
        }

        // Update modal with item data via AJAX
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: window.wncmsMenuRoutes.getMenuItem,
            data: {
                menu_item_id: id,
            },
            type: "POST",
            success: function(data) {
                // Handle translations
                data.translations.forEach(function(translation) {
                    const locale = translation.locale;
                    const value = translation.value;
                    $(`#modal_edit_menu_item input[name="menu_item_name[${locale}]"]`).val(value);
                });

                $(`#modal_edit_menu_item input[name="menu_item_url"]`).val(data.url);
                $(`#modal_edit_menu_item input[name="menu_item_description"]`).val(data.description);
                $(`#modal_edit_menu_item input[name="menu_item_order"]`).val(data.order);
                $(`#modal_edit_menu_item input[name="menu_item_icon"]`).val(data.icon);
                $(`#modal_edit_menu_item input[name="menu_item_new_window"]`).prop('checked', data.is_new_window);
            }
        });

        // Show modal
        $("#modal_edit_menu_item").modal("show");
    });
}

/**
 * Fetch tag languages
 */
function fetchTagLanguages(modelId) {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: window.wncmsMenuRoutes.getTagLanguages,
        data: {
            model_id: modelId,
        },
        type: "POST",
        success: function(response) {
            const modal_edit_menu_item_name_inputs = document.querySelectorAll('.modal_edit_menu_item_name');
            modal_edit_menu_item_name_inputs.forEach(input => {
                const localeKey = input.getAttribute('name').match(/\[(.*?)\]/)[1];
                if (response.translations.name[localeKey]) {
                    input.value = response.translations.name[localeKey];
                }
            });
        }
    });
}

/**
 * Generate random ID
 */
function generateRandomId(length = 16) {
    const characters = '0123456789';
    let randomString = '';

    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }

    return randomString;
}

/**
 * Check if tags still exist
 */
function checkTagIds() {
    var tagIds = [];
    $("#nestable-json li[data-model-type='Tag'][data-model-id!='']").each(function() {
        tagIds.push($(this).data('model-id'));
    });

    $.ajax({
        url: window.wncmsMenuRoutes.checkTagsExist,
        type: "POST",
        data: {
            tagIds: tagIds
        },
        success: function(response) {
            var existingTagIds = response.ids;
            $("#nestable-json li[data-model-type='Tag']").each(function() {
                var tagId = $(this).data('model-id');
                if (!tagId || !existingTagIds.includes(tagId)) {
                    var handleName = $(this).find('.dd-handle>.dd-handle-name');
                    handleName.css('color', 'red');
                }
            });
        }
    });
}
