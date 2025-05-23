jQuery(document).ready(function($) {

    // Find all elements needing a check
    var $elementsToCheck = $('.eav-check-visibility');

    // If no elements, do nothing
    if ($elementsToCheck.length === 0) {
        return;
    }

    // Get the post ID and AJAX info from wp_localize_script
    var postId = eavData.post_id;
    var ajaxUrl = eavData.ajax_url;
    var nonce = eavData.nonce;

    // Collect all unique ACF field keys needed
    var requiredFields = [];
    $elementsToCheck.each(function() {
        var field = $(this).data('acf-field');
        if (field && requiredFields.indexOf(field) === -1) {
            requiredFields.push(field);
        }
    });

    // If no fields found, something is wrong, but show elements to be safe
    if (requiredFields.length === 0) {
        $elementsToCheck.addClass('eav-show');
        return;
    }

    // Make the AJAX call to get ACF values
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'get_acf_values', // Must match add_action in PHP
            nonce: nonce,
            post_id: postId,
            fields: requiredFields
        },
        success: function(response) {
            if (response.success && response.data) {
                var acfValues = response.data;
                // Now, check each element
                $elementsToCheck.each(function() {
                    var $el = $(this);
                    var field = $el.data('acf-field');
                    var compare = $el.data('acf-compare');
                    var value = String($el.data('acf-value') || ''); // Ensure it's a string
                    var currentValue = acfValues[field];

                    if (shouldDisplayElement(currentValue, value, compare)) {
                        $el.addClass('eav-show'); // Show the element
                    } else {
                        $el.remove(); // Or hide: $el.hide(); - remove is often cleaner
                    }
                });
            } else {
                // On error, show everything to be safe
                $elementsToCheck.addClass('eav-show');
                console.error('ACF Visibility Check Failed:', response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            // On AJAX error, show everything
            $elementsToCheck.addClass('eav-show');
            console.error('ACF Visibility AJAX Error:', textStatus, errorThrown);
        }
    });

    // --- Helper function to mimic PHP logic ---
    function shouldDisplayElement(currentValue, expectedValue, comparisonType) {
        // Handle boolean-like values
        if (typeof currentValue === 'boolean') {
            currentValue = currentValue ? '1' : '0';
        }
        if (currentValue === null || typeof currentValue === 'undefined') {
            currentValue = '';
        } else {
             currentValue = String(currentValue); // Convert to string for comparisons
        }


        if (expectedValue.toLowerCase() === 'true') expectedValue = '1';
        if (expectedValue.toLowerCase() === 'false') expectedValue = '0';

        switch (comparisonType) {
            case 'equals':
                return (currentValue == expectedValue); // Loose comparison

            case 'not_equals':
                return (currentValue != expectedValue); // Loose comparison

            case 'is_empty':
                return (currentValue === '' || currentValue === '0' || currentValue.length === 0);

            case 'is_not_empty':
                 return (currentValue !== '' && currentValue !== '0' && currentValue.length > 0);

            case 'contains':
                // For arrays, check if expectedValue is one of them.
                // For strings, check if expectedValue is a substring.
                // Note: get_field(..., false) might return array or string.
                // AJAX returns it; we need to see how PHP sends it. JSON sends arrays.
                // We'll assume for JS, 'contains' mostly means string contains or array includes.
                // Let's keep it simple: string contains for now. Adjust if needed.
                if (Array.isArray(currentValue)) {
                    return currentValue.map(String).indexOf(expectedValue) !== -1;
                }
                return (currentValue.indexOf(expectedValue) !== -1);

            case 'not_contains':
                 if (Array.isArray(currentValue)) {
                    return currentValue.map(String).indexOf(expectedValue) === -1;
                }
                return (currentValue.indexOf(expectedValue) === -1);

            default:
                return (currentValue == expectedValue);
        }
    }
});