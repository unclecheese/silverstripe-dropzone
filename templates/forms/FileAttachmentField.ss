<div id="$Name" class="field<% if $extraClass %> $extraClass<% end_if %>">
    <% if $Title %><label class="left" for="$ID">$Title</label><% end_if %>
        <div class="dropzone-holder" data-config='$ConfigJSON'>
            <p>Attach files by dropping them in here. You can also <a>select files from your computer</a>.</p>
            <ul data-container class="file-attachment-field-previews">
            </ul>
            <template>
                <li>
                    <a data-dz-remove class="remove"></a>
                    <span class="file-icon">
                        <img src="dropzone/images/file-icons/32px/doc.png">
                    </span>
                    <span class="file-name" data-dz-name></span>
                    <span class="file-size" data-dz-size></span>
                    <span class="file-progress-wrap">
                        <span class="file-progress" data-dz-uploadprogress></span>
                    </span>
                    <span class="file-error" data-dz-errormessage></span>                    
                </li>            
            </template>
        </div>
    
</div>



<!--
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-filename">
                            <span data-dz-name></span>
                        </div>
                        <div class="dz-size" data-dz-size></div>
                        <img data-dz-thumbnail />
                    </div>
                    <div class="dz-progress">
                        <span class="dz-upload" data-dz-uploadprogress></span>
                    </div>
                    <div class="dz-success-mark"><span>✔</span></div>
                    <div class="dz-error-mark"><span>✘</span></div>
                    <div class="dz-error-message">
                        <span data-dz-errormessage></span>
                    </div>
                </div>


        <ul class="file-attachment-field-attachments">
            <li>
                <img src="dropzone/images/file-icons/48px/doc.png">
                <span class="attachment-filename">Query letter (revised).doc</span>
                <span class="attachment-filesize">15 MB</span>
                <a class="detach">detach</a><a class="remove">delete</a>
            </li>
            <li>
                <img src="dropzone/images/file-icons/48px/mp4.png">
                <span class="attachment-filename">Couples Therapy S05E01.mp4</span>
                <span class="attachment-filesize">2.2 GB</span>
                <a class="detach">detach</a><a class="remove">delete</a>
            </li>
        </ul>


-->