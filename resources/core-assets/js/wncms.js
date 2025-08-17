var WNCMS = {
    Button: {
        Init: function () {
            /**
             * For Illuminate\Foundation\Http\FormRequest
             * Usage:
             *   auth pages
             *   backend model forms
             */

            $("[wncms-btn-loading]").on("click", function () {
                //set disable
                // console.log('buttons loaded');
                var button = $(this);
                var label = button.find(".indicator-label");
                var loading_button = button.find(".indicator-progress");
                var original_text = label.text();
                var success_text = button.data("success-text") || original_text;
                var fail_text = button.data("fail-text") || original_text;

                button.prop("disabled", true);
                label.text("");
                loading_button.show();

                var form = $("#" + button.data("form"));

                if (form.length) {
                    // console.log('form is found');
                    // console.log(form.serialize());

                    // Check if required fields have values
                    var isValid = true;
                    form.find("[required]").each(function () {
                        if ($(this).val() === "") {
                            isValid = false;
                            $(this).addClass("is-invalid");
                            let error_text = $(this).next(".invalid-feedback");
                            // if (!error_text.length) {
                            //     $(this).after("<div class='invalid-feedback'>This field is required.</div>");
                            // }
                        } else {
                            $(this).removeClass("is-invalid");
                            $(this).next(".invalid-feedback").remove();
                        }
                    });

                    if (!isValid) {
                        loading_button.hide();
                        label.text(fail_text);
                        button.prop("disabled", false);
                        return false;
                    }

                    $.ajax({
                        headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                        url: form.data("action"),
                        data: form.serialize(),
                        type: "POST",
                        success: function (response) {
                            // console.log(response);
                            if (response.redirect) {
                                location.href = response.redirect;
                            }

                            if (response.message) {
                                console.log("message: " + response.message);
                            }

                            if (response.restoreBtn) {
                                button.prop("disabled", false);
                            }

                            loading_button.hide();

                            label.text(success_text);
                            if (response.button_text) {
                                label.text(response.button_text);
                            }
                        },
                        error: function (response) {
                            console.log(response);
                            if (response.status == "500") {
                                label.text("Error 500. Refresh");
                                button.prop("disabled", false);
                                return false;
                            } else if (response.status == "419") {
                                label.text("Error 419. Refresh");
                                button.prop("disabled", false);
                                return false;
                            } else if (response.responseJSON) {
                                console.log(response.responseJSON.errors);
                                $.each(response.responseJSON.errors, function (fieldName, error) {
                                    let field = form.find('[name="' + fieldName + '"]');
                                    field.addClass("is-invalid");
                                    let error_text = field.next();
                                    if (error_text.hasClass("invalid-feedback")) {
                                        error_text.text(error);
                                    } else {
                                        field.after("<div class='invalid-feedback'>" + error + "</div>");
                                    }
                                });
                                loading_button.hide();
                                label.text(fail_text);
                                button.prop("disabled", false);
                                return false;
                            }
                        },
                    });
                } else {
                    // Check if required fields have values
                    var isValid = true;
                    form = button.closest("form");
                    form.find("[required]").each(function () {
                        if ($(this).val() === "") {
                            isValid = false;
                            $(this).addClass("is-invalid");
                            let error_text = $(this).next(".invalid-feedback");
                            // if (!error_text.length) {
                            //     $(this).after("<div class='invalid-feedback'>This field is required.</div>");
                            // }
                        } else {
                            $(this).removeClass("is-invalid");
                            $(this).next(".invalid-feedback").remove();
                        }
                    });

                    if (!isValid) {
                        loading_button.hide();
                        label.text(fail_text);
                        button.prop("disabled", false);
                        return false;
                    }

                    form.submit();
                }
            });

            //since v3.0.6
            //!updated v4.2.0
            $("[wncms-btn-ajax]").on("click", function (e) {
                e.preventDefault();
                var button = $(this);

                //get btn text
                var original_text = button.data("original-text");
                var loading_text = button.data("loading-text") || original_text;
                var success_text = button.data("success-text") || original_text;
                var fail_text = button.data("fail-text") || original_text;
                var isSwal = button.is("[wncms-btn-swal]") && button.attr("wncms-btn-swal") !== "false";
                var confirm_text = button.data("confirm-text");
                let isConfirmed = true;

                // if confirm text is set, show confirm dialog
                if (confirm_text) {
                    isConfirmed = confirm(confirm_text);
                }

                if (!isConfirmed) {
                    return; // Exit if user doesn't confirm
                }

                //disabled when submitting
                button.prop("disabled", true);
                button.text(loading_text || original_text);
                // console.log('loading_text = ' + loading_text)

                //get api
                var route = button.data("route");
                var method = button.data("method") || "POST";

                //prepare request data
                var params = {};
                params["is_ajax"] = true;

                //get data attribute
                var dataAttributes = $(this).data();
                // Iterate through dataAttributes and format the keys
                $.each(dataAttributes, function (key, value) {
                    if (key.startsWith("param")) {
                        // Remove "param" prefix
                        var formattedKey = key.replace("param", "");

                        // Convert the formatted key to camel case
                        formattedKey = toCamelCase(formattedKey);

                        //add to param
                        params[formattedKey] = value;
                    }
                });

                //get form
                var form = $("#" + button.data("form"));
                if (form.length) {
                    // TODO: get all required field
                    console.log("checking");
                    var requiredFields = form.find(":required");
                    var allFieldsFilled = true;
                    console.log(requiredFields);

                    // TODO: check if required field is filled
                    requiredFields.each(function () {
                        var errorDiv = $(this).closest(".col-12").find(".form-error-message");
                        if ($(this).is(":radio")) {
                            var radioGroup = $('input[name="' + $(this).attr("name") + '"]:checked');
                            if (radioGroup.length === 0) {
                                allFieldsFilled = false;
                                errorDiv.show();
                            } else {
                                errorDiv.hide();
                            }
                        } else if ($(this).is(":checkbox")) {
                            var checkboxGroup = $('input[name="' + $(this).attr("name") + '"]:checked');
                            if (checkboxGroup.length === 0) {
                                allFieldsFilled = false;
                                errorDiv.show();
                            } else {
                                errorDiv.hide();
                            }
                        } else {
                            var fieldValue = $(this).val();
                            if (!fieldValue || fieldValue.trim() === "") {
                                allFieldsFilled = false;
                                var fieldName = $(this).attr("name");
                                errorDiv.show();
                            } else {
                                errorDiv.hide();
                            }
                        }
                    });

                    if (!allFieldsFilled) {
                        button.prop("disabled", false);
                        button.text(original_text);
                        return;
                    } else {
                        $(".form-error-message").hide();
                    }

                    params["formData"] = form.serialize();
                }

                //get model
                var shouldGetModelIds = button.is("[wncms-get-model-ids]");
                if (shouldGetModelIds) {
                    var model_id = button.data("model-id");
                    if (model_id) {
                        params["model_id"] = model_id;
                    }

                    var model_ids = [];
                    $(".form-check-input:checked").each(function () {
                        var modelId = $(this).attr("data-model-id");
                        if (modelId) {
                            model_ids.push(modelId);
                        }
                    });
                    if (model_ids) {
                        params["model_ids"] = model_ids;
                    }
                }

                //send the ajax request
                $.ajax({
                    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                    url: route,
                    type: method,
                    data: params,
                    success: function (response) {
                        console.log(response);
                        if (response.status == "success") {
                            if (isSwal) {
                                Swal.fire({
                                    icon: "success",
                                    title: response.title,
                                    text: response.message,
                                }).then(function () {
                                    successAction(response, button, success_text);
                                });
                            } else {
                                successAction(response, button, success_text);
                            }
                        } else {
                            if (isSwal) {
                                Swal.fire({
                                    icon: "error",
                                    title: response.title,
                                    text: response.message,
                                }).then(function () {
                                    failAction(response, button, fail_text);
                                    if (typeof customFailAction === "function") {
                                        customFailAction();
                                    }
                                });
                            } else {
                                failAction(response, button, fail_text);
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr);
                        console.log(status);
                        console.log(error);
                        button.prop("disabled", false);
                        button.text(fail_text);
                    },
                });
            });

            function toCamelCase(string) {
                return string
                    .replace(/\s(.)/g, function (a) {
                        return a.toUpperCase();
                    })
                    .replace(/\s/g, "")
                    .replace(/^(.)/, function (b) {
                        return b.toLowerCase();
                    });
            }

            function successAction(response, button, success_text) {
                console.log("successAction");
                console.log("successRedirect = " + response.successRedirect);
                button.text(response.btn_text || success_text);
                if (response.successRedirect) location.href = response.successRedirect;
                if (response.restoreBtn !== false) button.prop("disabled", false);

                // Get the custom success action function name from the button attribute
                const successActionName = button.data("success-action");
                if (successActionName && typeof window[successActionName] === "function") {
                    window[successActionName]();
                } else {
                    console.log("no custom success action is found");
                    console.log(typeof successActionName);
                }

                console.log("end successAction, check if reload");

                if (response.reload) location.reload();
            }

            function failAction(response, button, fail_text) {
                button.text(response.btn_text || fail_text);
                if (response.failRedirect) location.href = response.failRedirect;
                if (response.restoreBtn !== false) button.prop("disabled", false);

                //custom fail action
                if (typeof customFailAction === "function") {
                    customFailAction(response);
                } else {
                    console.log("no custom fail action is found");
                    console.log(typeof customFailAction);
                }

                if (response.reload) location.reload();
            }
        },
    },

    CheckBox: {
        All: function (n) {
            $("input[name='" + n + "']").each(function () {
                this.checked = true;
            });
        },
        Other: function (n) {
            $("input[name='" + n + "']").each(function () {
                this.checked = !this.checked;
            });
        },
        Count: function (n) {
            var res = 0;
            $("input[name='" + n + "']").each(function () {
                if (this.checked) {
                    res++;
                }
            });
            return res;
        },
        Values: function (n) {
            var res = [];
            $("input[name='" + n + "']").each(function () {
                if (this.checked) {
                    res.push(this.value);
                }
            });
            return res.join(",");
        },
        Ids: function (n) {
            var res = [];
            console.log("Checkbox count = " + $("input[data-model-id]").length);
            $("input[data-model-id]").each(function (i, el) {
                if (this.checked) {
                    res.push(this.getAttribute("data-model-id"));
                }
            });
            return res;
        },
    },

    Cookie: {
        Set: function (name, value, seconds) {
            var exp = new Date();
            exp.setTime(exp.getTime() + seconds * 1000);
            var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
            document.cookie = name + "=" + encodeURIComponent(value) + ";path=/;expires=" + exp.toUTCString();
        },
        Get: function (name) {
            var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
            if (arr != null) {
                return decodeURIComponent(arr[2]);
                return null;
            }
        },
        Del: function (name) {
            var exp = new Date();
            exp.setTime(exp.getTime() - 1);
            var cval = this.Get(name);
            if (cval != null) {
                document.cookie = name + "=" + encodeURIComponent(cval) + ";path=/;expires=" + exp.toUTCString();
            }
        },
    },

    Copy: {
        //copy to clipboard
        Init: function () {
            $("[btn-copy-to-clipboard]").on("click", function () {
                console.log("copy to clipboard click");
                var button = $(this);
                var value = button.data("value");
                var copied_text = button.data("copied-text");

                $("[btn-copy-to-clipboard]").text(function () {
                    return $(this).data("original-text");
                });

                button.text(copied_text);

                var $tempInput = $("<input>");
                $("body").append($tempInput);
                $tempInput.val(value).select();
                document.execCommand("copy");
                $tempInput.remove();
            });
        },
    },

    Fav: function (u, s) {
        //add to fav
    },

    Model: {
        Ajax: function (route, data, method) {
            $.ajax({
                headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                url: route,
                data: data,
                type: method || "POST",
                success: function (data) {
                    location.reload();
                },
            });
        },
        Init: function () {
            $("[wncms-click]").on("click", function (e) {
                // console.log('clicked [wncms-click]');
                item = $(this);
                var modelType = item.data("type");
                var modelId = item.data("id");
                var route = item.data("route") || "/api/v1/analytics/record";
                var collection = "click";

                $.ajax({
                    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
                    url: route,
                    data: {
                        modelType: modelType,
                        modelId: modelId,
                        collection: collection,
                    },
                    type: "POST",
                    success: function (response) {
                        // console.log(response);
                    },
                });
            });
        },
    },

    Title: document.title,

    Url: document.URL,

    UserAgent: (function () {
        var ua = navigator.userAgent; //navigator.appVersion
        return {
            mobile: !!ua.match(/AppleWebKit.*Mobile.*/), //是否为移动终端
            ios: !!ua.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
            android: ua.indexOf("Android") > -1 || ua.indexOf("Linux") > -1, //android终端或者uc浏览器
            iPhone: ua.indexOf("iPhone") > -1 || ua.indexOf("Mac") > -1, //是否为iPhone或者QQHD浏览器
            iPad: ua.indexOf("iPad") > -1, //是否iPad
            trident: ua.indexOf("Trident") > -1, //IE内核
            presto: ua.indexOf("Presto") > -1, //opera内核
            webKit: ua.indexOf("AppleWebKit") > -1, //苹果、谷歌内核
            gecko: ua.indexOf("Gecko") > -1 && ua.indexOf("KHTML") == -1, //火狐内核
            weixin: ua.indexOf("MicroMessenger") > -1, //是否微信 ua.match(/MicroMessenger/i) == "micromessenger",
        };
    })(),
};

$(function () {
    console.log("wncms loaded");
    WNCMS.Copy.Init();
    WNCMS.Button.Init();
    WNCMS.Model.Init();
});
