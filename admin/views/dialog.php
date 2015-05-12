<script>
function insertTextAtCursor(el, text) {
    var val = el.value, endIndex, range;
    if (typeof el.selectionStart != "undefined" && typeof el.selectionEnd != "undefined") {
        endIndex = el.selectionEnd;
        el.value = val.slice(0, el.selectionStart) + text + val.slice(endIndex);
        el.selectionStart = el.selectionEnd = endIndex + text.length;
    } else if (typeof document.selection != "undefined" && typeof document.selection.createRange != "undefined") {
        el.focus();
        range = document.selection.createRange();
        range.collapse(false);
        range.text = text;
        range.select();
    }
}
jQuery( document ).ready(function( $ ) {
    var oascName = "";
    var oascURL  = "";
    function loadXML(oascName, oascURL) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "get_data",
                type: "article_list",
                format: "json",
                name: oascName,
                url: oascURL
            },
            beforeSend: function( xhr ) {
                jQuery(".media-frame-content").html("<div class='idler'><img src='http://structured.local/images/712.GIF'></div>");
            }
        })
        .done(function( data ) {
            $(".media-frame-content").empty();
            
            var articles = JSON.parse(data);
            articles = articles.article;
            
            $(".media-frame-content").append("<table cellspacing='0' id='dataResponse'><tr><th>ID</th><th>Title</th><th>First Published</th><th>Last Modified</th><th>Actions</th></tr></table>");
            for(var i = 0; i < articles.length; i++) {
                $("#dataResponse").append($("<tr class='" + (i % 2 == 0 ? 'one' : 'two') + "'></tr>")
                    .append($("<td>" + articles[i]['@attributes'].id + "</td>"))
                    .append($("<td style='padding-left: " + articles[i]['@attributes'].depth * 20 + "px;'></td>")
                        .append($("<a href='" + oascURL + "?page_id=" + articles[i]['@attributes'].id + "' target='_blank'>" + articles[i]['Title'].FullTitle + "</a>"))
                        )
                    .append("<td>" + articles[i]['DateFirstPublished'] + "</td>")
                    .append("<td>" + articles[i]['DateLastModified'] + "</td>")
                    .append("<td><a href='#' class='insertCode' oasc-id='" + articles[i]['@attributes'].id + "'>Insert Into Page</a></td>")
                );
            }
            
            $(".insertCode").click(function(e) {
                console.log($(this).attr('oasc-id'));
                insertShortcode($(this).attr('oasc-id'));
                e.preventDefault();
            });
        });
    }
    function insertShortcode(id) {
        tagtext = "[oasc site=\"" + oascURL + "\" article=\"" + id + "\"]";
        
        if(window.tinyMCE.activeEditor) {
            var tmce_ver=window.tinyMCE.majorVersion;
        
            if (tmce_ver>="4") {
                window.tinyMCE.activeEditor.execCommand('mceInsertContent', 0, tagtext);
            } else {
                window.tinyMCE.activeEditor.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
            }
        } else {
            insertTextAtCursor(document.getElementById("content"), tagtext);
        }
        tb_remove();
        jQuery(".media-modal.wp-core-ui").parent().remove();
    }
    $(".repo").click(function(e) {
        e.preventDefault();
        
        wireframe = wp.media.frames.wireframe = wp.media({
            title: 'Structured Content',
            button: {
                text: 'Select Content and Share'
            },
            multiple: false
        });
        
        wireframe.on('select', function() {
            attachment = wireframe.state().get('selection').first().toJSON();
            console.log(attachment);
            $('#input-field-selector').val(attachment.url);
        });
        
        wireframe.open();
        // Clean -- Need to go back to this and port out the Media Modal, but this will work for now...
        $(".media-router a:nth-last-child(2)").html("Article");
        $(".media-router a:last").html("Event");
        $(".media-modal-content .media-frame-content").empty();
        $(".media-router a").click(function(e) {
            $(".media-modal-content .media-frame-content").empty();
            oascName = $(this).attr('oasc-name');
            oascURL  = $(this).attr('oasc-url');
            loadXML(oascName, oascURL);
        });
        // We need to remember to clean our DOM elements when completed with the RSS listing...
        oascName = $(this).attr('oasc-name');
        oascURL  = $(this).attr('oasc-url');
        loadXML(oascName, oascURL);
    });
});
</script>
<style type="text/css">
.repo {
    display: inline-block;
}
.shared {
    text-align: center;
    width: 100px;
    float: left;
    padding: 10px;
}
.media-frame-content ul.page-list {
    margin: 20px;
}
.media-frame-content ul.page-list .children {
    margin-left: 20px;
}
.media-frame-content .idler {
    text-align: center;
    margin-top: 50px;
}
table#dataResponse {
    width: 95%;
    margin: 5px;
    margin-left: 20px;
}
table#dataResponse tr td {
    padding: 4px;
}
table#dataResponse tr th {
    text-align: left;
}
tr.one {
    
}
tr.two {
    background-color: #d6f3ff;
}
</style>
<div id="popup_container" style="display:none;">
    <h2>Welcome to Shared Content!</h2>
    <div>Below are a list of sites that have been added to the Shared Content network. Feel free to select a source to view their Shared Content.</div>
    <?php
    $xmlArray = Structured_Content::getXMLDefinition();
    foreach($xmlArray->SharedContent->Source as $key=>$val): ?>
        <div class='shared'>
            <a class='repo' href="<?php echo $val->URL; ?>" oasc-id="<?php echo $val->id; ?>" oasc-name="<?php echo $val->Name; ?>" oasc-url="<?php echo $val->URL; ?>">
            <img src="<?php echo $val->Logo; ?>" /><br />
            <?php echo $val->Name; ?> (v<?php $attn = $val->attributes(); echo $attn['version']; ?>)
            </a>
        </div>
    <?php endforeach; ?>
</div>