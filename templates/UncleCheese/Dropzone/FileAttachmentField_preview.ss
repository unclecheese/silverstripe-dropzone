            <li class="$CSSSize" style="<% if $View == 'grid' %>width:{$SelectedThumbnailWidth}px;<% end_if %>height:{$SelectedThumbnailHeight}px;">
                <span class="dropzone-actions">
                    <span data-dz-remove data-detach class="dropzone-action detach">
                        <img src="$resourceURL('unclecheese/dropzone:images/remove.png')" width="16">
                    </span>
                </span>

                <span class="file-icon" style="width:{$SelectedThumbnailWidth}px;height:{$SelectedThumbnailHeight}px;">
                    <img data-dz-thumbnail width="$SelectedThumbnailWidth">
                </span>
                <span class="file-meta file-name truncate" data-dz-name></span>
                <span class="file-meta file-size">
                        <%t Dropzone.ADDEDNOW 'Added just now' %>
                    · <span data-dz-size>$File.Size</span>
                </span>

                <span class="file-progress-holder">
                    <span class="file-progress-wrap">
                        <span class="file-progress" data-dz-uploadprogress></span>
                    </span>
                </span>
                <span class="check-holder">
                    <img src="$resourceURL('unclecheese/dropzone:images/check.png')" width="16" class="check">
                </span>
                <span class="overlay error-overlay">
                    <span>
                        <h5><%t Dropzone.ERROR 'Oh no!' %></h5>
                        <small data-dz-errormessage></small>
                        <span data-dz-remove class="revert"><img src="$resourceURL('unclecheese/dropzone:images/undo_white.png')" width="16"></span>
                    </span>
                </span>
            </li>
