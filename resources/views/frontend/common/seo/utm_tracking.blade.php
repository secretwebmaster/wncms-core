<script>
    console.log('cookie debug');
    // Function to check if a query parameter exists
    function getQueryParam(name) {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.has(name) ? urlParams.get(name) : null;
    }

    // Retrieve values from query parameters or cookies and fill the hidden inputs
    function fillHiddenInputs() {
        // console.log("111" + getQueryParam('utm_source'))
        // console.log("222" + WNCMS.Cookie.Get('utm_source'))

        // console.log("utm_source = " + (getQueryParam('utm_source') || WNCMS.Cookie.Get('utm_source') || ''));
        // console.log("utm_medium = " + (getQueryParam('utm_medium') || WNCMS.Cookie.Get('utm_medium') || ''));
        // console.log("utm_campaign = " + (getQueryParam('utm_campaign') || WNCMS.Cookie.Get('utm_campaign') || ''));
        // console.log("utm_content = " + (getQueryParam('utm_content') || WNCMS.Cookie.Get('utm_content') || ''));
        // console.log("utm_term = " + (getQueryParam('utm_term') || WNCMS.Cookie.Get('utm_term') || ''));

        $('#utm_source').val(getQueryParam('utm_source') || WNCMS.Cookie.Get('utm_source') || '');
        $('#utm_medium').val(getQueryParam('utm_medium') || WNCMS.Cookie.Get('utm_medium') || '');
        $('#utm_campaign').val(getQueryParam('utm_campaign') || WNCMS.Cookie.Get('utm_campaign') || '');
        $('#utm_content').val(getQueryParam('utm_content') || WNCMS.Cookie.Get('utm_content') || '');
        $('#utm_term').val(getQueryParam('utm_term') || WNCMS.Cookie.Get('utm_term') || '');
        
        var currentURL = window.location.href;
        $('[name="current_url"]').val(currentURL)
    }

    // Check if query parameters exist and save them to cookies
    $(document).ready(function () {
        var utmSource = getQueryParam('utm_source');
        var utmMedium = getQueryParam('utm_medium');
        var utmCampaign = getQueryParam('utm_campaign');
        var utmContent = getQueryParam('utm_content');
        var utmTerm = getQueryParam('utm_term');

        // console.log("utmSource =" + utmSource);
        // console.log("utmMedium =" + utmMedium);
        // console.log("utmCampaign =" + utmCampaign);
        // console.log("utmContent =" + utmContent);
        // console.log("utmTerm =" + utmTerm);

        if (utmSource) {
            setCookie('utm_source', utmSource, 30);
        }
        if (utmMedium) {
            setCookie('utm_medium', utmMedium, 30);
        }
        if (utmCampaign) {
            setCookie('utm_campaign', utmCampaign, 30);
        }
        if (utmContent) {
            setCookie('utm_content', utmContent, 30);
        }
        if (utmTerm) {
            setCookie('utm_term', utmTerm, 30);
        }

        // console.log('UTM Source:', utmSource);
        fillHiddenInputs();
    });

</script>