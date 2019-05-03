<div id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %> supported form-group">
    <% if $Title %><label class="left form__field-label" for="$ID">$Title</label><% end_if %>

    <div class="form__field-holder">
        <div id="{$Name}Dropzone" class="dropzone-holder <% if $isCMS %>backend<% end_if %> <% if $CanUpload %>uploadable<% end_if %>" data-config='$ConfigJSON'>
            <p>
                <% if $IsMultiple && $CanUpload %>
                    <%t Dropzone.ATTACHFILESHERE_OR "Attach files by dropping them in here." %>
                <% else_if $CanUpload %>
                    <%t Dropzone.ATTACHFILEHERE_OR "Attach a file by dropping it in here." %>
                <% end_if %>

                <% if $CanUpload && $CanAttach %><br><% end_if %>
                <% if $CanUpload || $CanAttach %>
                    <% if $CanUpload %><%t Dropzone.YOUCANALSO "You can also" %> <% end_if %>
                    <% if $CanUpload %>[<a class="dropzone-select"><%t Dropzone.BROWSEYOURCOMPUTER "browse your computer" %></a>]<% end_if %>
                    <% if $CanUpload && $CanAttach %> <%t Dropzone.OR " or " %> <% end_if %>
                    <% if $CanAttach %>[<a class="dropzone-select-existing"><%t Dropzone.CHOOSEEXISTING "choose from existing files" %></a>]<% end_if %>
                <% end_if %>

            </p>
            <ul data-container data-attachments class="file-attachment-field-previews $View">
                <% if $AttachedFiles %>
                    <% loop $AttachedFiles %>
                        <% include UncleCheese\Dropzone\FileAttachmentField_attachments File=$Me, Scope=$Up %>
                    <% end_loop %>
                <% end_if %>
            </ul>



            <template>
                $PreviewTemplate
            </template>
            <div class="attached-file-inputs" data-input-name="$InputName">
                <% if $AttachedFiles %>
                    <% loop $AttachedFiles %>
                    <input class="input-attached-file" type="hidden" name="$Up.InputName" value="$ID">
                    <% end_loop %>
                <% end_if %>
            </div>
            <div class="attached-file-deletions" data-input-name="$InputName"></div>
            <div style="clear:both;"></div>
            <% if not $AutoProcess %>
                <button class="process" data-auto-process><%t Dropzone.UPLOADFILES "Upload file(s)" %></button>
            <% end_if %>

        </div>

        <div class="unsupported">
            <p><strong><%t Dropzone.NOTSUPPORTED "Your browser does not support HTML5 uploads. Please update to a newer version." %></strong></p>
        </div>

        <% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
    </div>
</div>
