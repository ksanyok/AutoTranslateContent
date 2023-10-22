jQuery(document).ready(function($) {
    $("#auto_translate_content_select_all").click(function() {
        var isChecked = $(this).is(":checked");
        $("input[name^='auto_translate_content_settings[custom_fields]']").prop("checked", isChecked);
    });
});
