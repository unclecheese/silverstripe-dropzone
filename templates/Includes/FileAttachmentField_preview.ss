            <li <% if $View == 'grid' %>style="width:{$SelectedThumbnailWidth}px;height:{$SelectedThumbnailHeight}px;"<% end_if %>>
                <span data-dz-remove class="remove"></span>
                <span class="file-icon">
                    <img data-dz-thumbnail width="$SelectedThumbnailWidth">                    
                </span>
                <span class="file-name truncate" data-dz-name></span>
                <span class="file-size" data-dz-size></span>                
                <span class="file-progress-holder">
                    <span class="file-progress-wrap">
                        <span class="file-progress" data-dz-uploadprogress></span>
                    </span>
                </span>
                <span class="check-holder">
                    <img src="$DropzoneDir/images/check.png" width="16" class="check">
                </span>
                <div class="file-error" data-dz-errormessage></div>                                    
            </li>            
